<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Events\DailyGoalCompleted;
use App\Domain\Gamification\Events\StreakUpdated;
use App\Domain\Gamification\Events\XpAwarded;
use App\Domain\Gamification\Models\ProcessedEvent;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Domain\Gamification\Services\XpCalculationService;
use App\Domain\Gamification\Services\XpTransactionService;
use App\Domain\Gamification\Support\ActivityRegistry;
use App\Domain\Learning\Events\QuizCompleted;
use App\Domain\Learning\Services\QuizCompletionVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class ProcessQuizCompletion
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly XpCalculationService $calculation,
        private readonly XpTransactionService $transactions,
        private readonly AwardXp $awardXp,
        private readonly UpdateDailyActivity $updateDailyActivity,
        private readonly UpdateUserStreak $updateUserStreak,
        private readonly EvaluateDailyGoal $evaluateDailyGoal,
        private readonly ActivityRegistry $registry,
        private readonly QuizCompletionVerifier $quizCompletionVerifier,
    ) {}

    public function execute(QuizCompleted $event): ?ProcessedEvent
    {
        if (! config('gamification.enabled', true)) {
            return null;
        }

        if (! $this->registry->has($event->activityType)) {
            Log::error('Unknown gamification activity type ignored.', [
                'activity_type' => $event->activityType,
                'source_id' => $event->sourceId,
            ]);

            return null;
        }

        $existing = ProcessedEvent::query()
            ->where('activity_type', $event->activityType)
            ->where('source_id', $event->sourceId)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            $event = $this->quizCompletionVerifier->verified($event);
        } catch (InvalidArgumentException $e) {
            Log::error('Rejected unverified QuizCompleted event.', [
                'activity_type' => $event->activityType,
                'source_id' => $event->sourceId,
                'user_id' => $event->userId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        try {
            /**
             * @var array{
             *     processed: ProcessedEvent,
             *     xp_awarded: array<string, int>,
             *     streak: array{old: int, new: int, longest: int, changed: bool}|null,
             *     goal: array{completed: bool, bonus: int, local_date: string}|null,
             *     already_processed: bool
             * } $result
             */
            $result = DB::transaction(function () use ($event): array {
                $profile = $this->profiles->lockForUser($event->userId);

                try {
                    $processed = ProcessedEvent::query()->create([
                        'user_id' => $event->userId,
                        'activity_type' => $event->activityType,
                        'source_id' => $event->sourceId,
                        'mode' => $event->mode,
                        'total_questions' => $event->totalQuestions,
                        'correct_answers' => $event->correctAnswers,
                        'is_perfect' => false,
                        'xp_total' => 0,
                        'local_date' => $event->completedAt->toDateString(),
                        'summary' => [],
                        'processed_at' => now(),
                    ]);
                } catch (QueryException $e) {
                    if ($this->isUniqueViolation($e)) {
                        $existing = ProcessedEvent::query()
                            ->where('activity_type', $event->activityType)
                            ->where('source_id', $event->sourceId)
                            ->first();

                        if ($existing !== null) {
                            return [
                                'processed' => $existing,
                                'xp_awarded' => [],
                                'streak' => null,
                                'goal' => null,
                                'already_processed' => true,
                            ];
                        }

                        throw $e;
                    }

                    throw $e;
                }

                $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
                $localDate = $event->completedAt->timezone($timezone)->toDateString();

                if ($profile->last_activity_date !== null
                    && $localDate < $profile->last_activity_date->toDateString()) {
                    $localDate = $profile->last_activity_date->toDateString();
                }

                $cap = (int) config('gamification.rewards.daily_activity_xp_cap', 500);
                $earnedToday = $this->transactions->activityXpForDate($event->userId, $localDate);
                $remainingCap = max(0, $cap - $earnedToday);

                $rewards = $this->calculation->calculateQuizRewards(
                    $event->activityType,
                    $event->totalQuestions,
                    $event->correctAnswers,
                    $remainingCap,
                    $event->mode,
                );

                $breakdown = [];
                $activityXp = 0;
                $amountsByReason = [];

                foreach ($rewards as $reward) {
                    /** @var XpReason $reason */
                    $reason = $reward['reason'];
                    $key = $this->transactions->idempotencyKey(
                        $event->userId,
                        $event->activityType,
                        $event->sourceId,
                        $reason,
                    );

                    $tx = $this->awardXp->execute(
                        profile: $profile,
                        amount: $reward['amount'],
                        reason: $reason,
                        sourceType: $event->activityType,
                        sourceId: $event->sourceId,
                        idempotencyKey: $key,
                        localDate: $localDate,
                        metadata: [
                            'correct_answers' => $event->correctAnswers,
                            'total_questions' => $event->totalQuestions,
                            'mode' => $event->mode,
                            'capped' => $reward['capped'],
                        ],
                    );

                    if ($tx !== null) {
                        $activityXp += $reward['amount'];
                        $breakdown[] = [
                            'reason' => $reason->value,
                            'amount' => $reward['amount'],
                            'capped' => $reward['capped'],
                        ];
                        $amountsByReason[$reason->value] = $reward['amount'];
                    }
                }

                $minimum = (int) config('gamification.rewards.minimum_questions', 5);
                $streakMinimum = (int) config('gamification.streak.minimum_questions', 5);
                $activity = $this->registry->get($event->activityType);
                $qualifiesForStreak = ($activity['qualifies_for_streak'] ?? true)
                    && $event->totalQuestions >= $streakMinimum;
                $countsAsCompletedQuiz = $event->totalQuestions >= $minimum;
                $isPerfect = $countsAsCompletedQuiz
                    && $event->correctAnswers === $event->totalQuestions
                    && $event->totalQuestions > 0;

                $daily = $this->updateDailyActivity->execute(
                    profile: $profile,
                    activityDate: $localDate,
                    activityXp: $activityXp,
                    bonusXp: 0,
                    questionsAnswered: $event->totalQuestions,
                    countsAsCompletedQuiz: $countsAsCompletedQuiz,
                    qualifiesForStreak: $qualifiesForStreak,
                );

                $streakResult = null;
                if ($qualifiesForStreak) {
                    $streakResult = $this->updateUserStreak->execute($profile, $localDate);
                }

                $goalResult = $this->evaluateDailyGoal->execute($profile, $daily);

                $bonusXp = (int) $goalResult['bonus'];
                $totalXpThisRun = $activityXp + $bonusXp;
                $profile->total_xp += $totalXpThisRun;
                $profile->save();

                $summary = [
                    'breakdown' => $breakdown,
                    'capped' => collect($breakdown)->contains(fn (array $row): bool => (bool) $row['capped']),
                    'below_minimum' => ! $countsAsCompletedQuiz,
                    'streak' => [
                        'current' => $profile->current_streak,
                        'longest' => $profile->longest_streak,
                    ],
                    'daily_goal' => [
                        'target' => $daily->daily_goal,
                        'earned' => $daily->xp_earned,
                        'completed' => $daily->goal_completed_at !== null,
                        'bonus' => $bonusXp,
                    ],
                    'unlocked_achievements' => [],
                ];

                $processed->forceFill([
                    'is_perfect' => $isPerfect,
                    'xp_total' => $totalXpThisRun,
                    'local_date' => $localDate,
                    'summary' => $summary,
                    'processed_at' => now(),
                ])->save();

                /** @var ProcessedEvent $fresh */
                $fresh = $processed->fresh();

                return [
                    'processed' => $fresh,
                    'xp_awarded' => $amountsByReason + ($bonusXp > 0 ? [XpReason::DailyGoalCompleted->value => $bonusXp] : []),
                    'streak' => $streakResult,
                    'goal' => $goalResult,
                    'already_processed' => false,
                ];
            });
        } catch (Throwable $e) {
            report($e);

            return ProcessedEvent::query()
                ->where('activity_type', $event->activityType)
                ->where('source_id', $event->sourceId)
                ->first();
        }

        if ($result['already_processed'] === true) {
            return $result['processed'];
        }

        $totalAwarded = array_sum($result['xp_awarded']);
        if ($totalAwarded > 0) {
            XpAwarded::dispatch(
                $event->userId,
                $totalAwarded,
                $result['xp_awarded'],
                $event->activityType,
                $event->sourceId,
            );
        }

        if ($result['streak'] !== null && $result['streak']['changed'] === true) {
            StreakUpdated::dispatch(
                $event->userId,
                $result['streak']['old'],
                $result['streak']['new'],
                $result['streak']['longest'],
            );
        }

        if ($result['goal'] !== null && $result['goal']['completed'] === true) {
            DailyGoalCompleted::dispatch(
                $event->userId,
                $result['goal']['local_date'],
                $result['goal']['bonus'],
            );
        }

        return $result['processed'];
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;

        return $sqlState === '23000' || $sqlState === '23505';
    }
}
