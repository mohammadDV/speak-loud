<?php

namespace App\Domain\Gamification\Queries;

use App\Domain\Gamification\DTOs\QuizGamificationResultData;
use App\Domain\Gamification\Models\ProcessedEvent;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Services\DailyGoalService;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Domain\Gamification\Services\NextStepRecommendationService;
use App\Domain\Gamification\Services\StreakService;
use App\Models\User;

final class GetQuizGamificationResult
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly StreakService $streaks,
        private readonly DailyGoalService $goals,
        private readonly NextStepRecommendationService $recommendations,
    ) {}

    public function execute(string $activityType, string $sourceId, ?User $user): QuizGamificationResultData
    {
        if ($user === null) {
            return new QuizGamificationResultData(state: 'guest');
        }

        $processed = ProcessedEvent::query()
            ->where('user_id', $user->id)
            ->where('activity_type', $activityType)
            ->where('source_id', $sourceId)
            ->first();

        if ($processed === null) {
            if (config('gamification.processing.mode') === 'queue') {
                return new QuizGamificationResultData(state: 'pending');
            }

            return new QuizGamificationResultData(state: 'missing');
        }

        /** @var array<string, mixed> $summary */
        $summary = $processed->summary ?? [];
        /** @var list<array{reason: string, amount: int, capped?: bool}> $breakdown */
        $breakdown = $summary['breakdown'] ?? [];
        $profile = $this->profiles->forUser($user);
        $streak = $this->streaks->status($profile);
        $daily = UserDailyActivity::query()
            ->where('user_id', $user->id)
            ->whereDate('activity_date', $processed->local_date->toDateString())
            ->first();
        $goal = $this->goals->progress($daily, $profile);
        $nextStep = null;
        if (config('gamification.recommendations.enabled', true)) {
            $next = $this->recommendations->forUser($user);
            $nextStep = [
                'activity_type' => $next->activityType,
                'title' => $next->title,
                'reason' => $next->reason,
                'url' => $next->url,
                'estimated_minutes' => $next->estimatedMinutes,
            ];
        }

        return new QuizGamificationResultData(
            state: 'rewarded',
            xpTotal: $processed->xp_total,
            breakdown: $breakdown,
            streak: [
                'current' => $streak->current,
                'longest' => $streak->longest,
                'completed_today' => $streak->completedToday,
                'at_risk' => $streak->atRisk,
            ],
            dailyGoal: [
                'target' => $goal->target,
                'earned' => $goal->earned,
                'completed' => $goal->completed,
                'remaining' => $goal->remaining,
            ],
            nextStep: $nextStep,
            capped: (bool) ($summary['capped'] ?? false),
            belowMinimum: (bool) ($summary['below_minimum'] ?? false),
        );
    }
}
