<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\DTOs\NextStepRecommendation;
use App\Domain\Gamification\Models\ProcessedEvent;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Support\ActivityRegistry;
use App\Domain\Learning\Services\LearningInsightsService;
use App\Models\User;
use Carbon\CarbonImmutable;

final class NextStepRecommendationService
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly StreakService $streaks,
        private readonly DailyGoalService $goals,
        private readonly ActivityRegistry $registry,
        private readonly LearningInsightsService $insights,
    ) {}

    public function forUser(User $user): NextStepRecommendation
    {
        $profile = $this->profiles->forUser($user);
        $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
        $today = CarbonImmutable::now($timezone)->toDateString();
        $daily = UserDailyActivity::query()
            ->where('user_id', $user->id)
            ->whereDate('activity_date', $today)
            ->first();
        $goal = $this->goals->progress($daily, $profile);
        $streak = $this->streaks->status($profile);

        $hasActivityToday = $daily !== null && $daily->questions_answered > 0;
        $qualifyingToday = $daily !== null && $daily->qualifying_activity_count > 0;
        $everCompleted = ProcessedEvent::query()->where('user_id', $user->id)->exists();
        $welcomeBackDays = (int) config('gamification.recommendations.welcome_back_after_days', 2);
        $returning = $profile->last_activity_date !== null
            && CarbonImmutable::parse($profile->last_activity_date->toDateString(), $timezone)
                ->diffInDays(CarbonImmutable::parse($today, $timezone)) >= $welcomeBackDays;

        $short = (int) config('gamification.recommendations.short_quiz_questions', 5);
        $defaultQuestions = (int) config('gamification.recommendations.default_questions', 10);
        $perAnswer = (int) config('gamification.xp.correct_answer', 2);
        $streakMin = (int) config('gamification.streak.minimum_questions', 5);

        $stageAPriority = 5;
        $reason = 'Great day! Keep your momentum.';
        $targetQuestions = $defaultQuestions;

        if (! $goal->completed && $hasActivityToday) {
            $stageAPriority = 1;
            $reason = "You are {$goal->remaining} XP away from your daily goal.";
            $needed = $perAnswer > 0 ? (int) ceil($goal->remaining / $perAnswer) : $short;
            $targetQuestions = max($streakMin, min(20, $needed));
        } elseif (! $qualifyingToday && $streak->atRisk) {
            $stageAPriority = 2;
            $reason = "Keep your {$streak->current}-day streak alive.";
            $targetQuestions = $short;
        } elseif (! $qualifyingToday && $returning) {
            $stageAPriority = 3;
            $reason = 'Welcome back! Start with a short session.';
            $targetQuestions = $short;
        } elseif (! $qualifyingToday && ! $everCompleted) {
            $stageAPriority = 4;
            $reason = 'Start your first quiz.';
            $targetQuestions = $short;
        } elseif ($goal->completed) {
            $reason = 'Great day! Keep your momentum.';
            $targetQuestions = $defaultQuestions;
        }

        $importantMin = (int) config('gamification.recommendations.important_words_min', 5);
        $difficultMin = (int) config('gamification.recommendations.difficult_words_min', 3);
        $focusMax = (int) config('gamification.recommendations.focus_questions_max', 10);
        $important = $this->insights->importantUnlearnedCounts($user);
        $difficult = $this->insights->difficultWordCounts($user);

        $focus = 'default';
        $activityType = $this->mostRecentActivityType($user) ?? ($this->registry->keys()[0] ?? 'article_quiz');
        $detail = '';

        if (max($important['article'], $important['meaning']) >= $importantMin) {
            $focus = 'important';
            if ($important['article'] === $important['meaning']) {
                $activityType = $this->tieBreakActivity($user, ['article_quiz', 'meaning_quiz']);
            } else {
                $activityType = $important['article'] > $important['meaning'] ? 'article_quiz' : 'meaning_quiz';
            }
            $count = $activityType === 'article_quiz' ? $important['article'] : $important['meaning'];
            $detail = "You have {$count} important words waiting for practice.";
            $targetQuestions = min($targetQuestions, $focusMax);
        } elseif (max($difficult['article'], $difficult['meaning']) >= $difficultMin) {
            $focus = 'difficult';
            if ($difficult['article'] === $difficult['meaning']) {
                $activityType = $this->tieBreakActivity($user, ['article_quiz', 'meaning_quiz']);
            } else {
                $activityType = $difficult['article'] > $difficult['meaning'] ? 'article_quiz' : 'meaning_quiz';
            }
            $count = $activityType === 'article_quiz' ? $difficult['article'] : $difficult['meaning'];
            $detail = "Review {$count} words you got wrong recently.";
            $targetQuestions = min($targetQuestions, $focusMax);
        } else {
            if ($stageAPriority === 5) {
                $activityType = $this->leastUsedActivity($user);
                $label = $this->registry->get($activityType)['label'] ?? $activityType;
                $detail = "Try {$label}.";
                $reason = 'Try something different.';
            }
        }

        $activity = $this->registry->get($activityType) ?? [
            'label' => 'Practice',
            'url' => '/learn',
            'flag_context' => '',
            'seconds_per_question' => 18,
            'qualifies_for_streak' => true,
            'xp' => [],
            'modes' => [],
        ];

        $parameters = ['count' => $targetQuestions];
        if ($focus !== 'default') {
            $parameters['focus'] = $focus;
        }

        $query = http_build_query($parameters);
        $url = rtrim((string) $activity['url'], '?').($query !== '' ? '?'.$query : '');
        $seconds = (int) $activity['seconds_per_question'];
        $minutes = (int) max(1, (int) ceil(($targetQuestions * $seconds) / 60));
        $combinedReason = trim($reason.($detail !== '' ? ' '.$detail : ''));

        return new NextStepRecommendation(
            activityType: $activityType,
            title: (string) $activity['label'],
            description: "{$targetQuestions} questions · Approximately {$minutes} minutes",
            reason: $combinedReason,
            url: $url,
            estimatedMinutes: $minutes,
            parameters: $parameters,
        );
    }

    private function mostRecentActivityType(User $user): ?string
    {
        return ProcessedEvent::query()
            ->where('user_id', $user->id)
            ->orderByDesc('processed_at')
            ->value('activity_type');
    }

    /**
     * @param  list<string>  $candidates
     */
    private function tieBreakActivity(User $user, array $candidates): string
    {
        $recent = $this->mostRecentActivityType($user);
        if ($recent !== null && in_array($recent, $candidates, true)) {
            return $recent;
        }

        foreach ($this->registry->keys() as $key) {
            if (in_array($key, $candidates, true)) {
                return $key;
            }
        }

        return $candidates[0];
    }

    private function leastUsedActivity(User $user): string
    {
        $window = (int) config('gamification.recommendations.activity_usage_window_days', 30);
        $since = now()->subDays($window);
        $keys = $this->registry->keys();
        $counts = ProcessedEvent::query()
            ->where('user_id', $user->id)
            ->where('processed_at', '>=', $since)
            ->selectRaw('activity_type, COUNT(*) as total')
            ->groupBy('activity_type')
            ->pluck('total', 'activity_type');

        $best = $keys[0] ?? 'article_quiz';
        $bestCount = PHP_INT_MAX;
        foreach ($keys as $key) {
            $count = (int) ($counts[$key] ?? 0);
            if ($count < $bestCount) {
                $best = $key;
                $bestCount = $count;
            }
        }

        return $best;
    }
}
