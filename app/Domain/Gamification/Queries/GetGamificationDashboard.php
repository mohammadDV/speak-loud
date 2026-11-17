<?php

namespace App\Domain\Gamification\Queries;

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Services\DailyGoalService;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Domain\Gamification\Services\LeaderboardService;
use App\Domain\Gamification\Services\NextStepRecommendationService;
use App\Domain\Gamification\Services\StreakService;
use App\Domain\Gamification\Support\CompetitionPeriod;
use App\Models\User;

final class GetGamificationDashboard
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly StreakService $streaks,
        private readonly DailyGoalService $goals,
        private readonly LeaderboardService $leaderboard,
        private readonly NextStepRecommendationService $recommendations,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $profile = $this->profiles->forUser($user);
        $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
        $today = now($timezone)->toDateString();
        $daily = UserDailyActivity::query()
            ->where('user_id', $user->id)
            ->whereDate('activity_date', $today)
            ->first();
        $period = CompetitionPeriod::forDate();
        $weeklyXp = (int) XpTransaction::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $period->startUtc)
            ->where('created_at', '<', $period->endUtc)
            ->sum('amount');
        $streak = $this->streaks->status($profile);
        $goal = $this->goals->progress($daily, $profile);
        $nextStep = null;
        if (config('gamification.recommendations.enabled', true)) {
            $next = $this->recommendations->forUser($user);
            $nextStep = [
                'activity_type' => $next->activityType,
                'title' => $next->title,
                'description' => $next->description,
                'reason' => $next->reason,
                'url' => $next->url,
                'estimated_minutes' => $next->estimatedMinutes,
            ];
        }

        $board = config('gamification.leaderboard.enabled', true)
            ? $this->leaderboard->forPeriod($period, $user, (int) config('gamification.leaderboard.preview_limit', 10))
            : null;

        return [
            'profile' => [
                'total_xp' => $profile->total_xp,
                'daily_goal' => $profile->daily_goal,
                'needs_goal_setup' => $profile->needsDailyGoalSetup(),
                'timezone' => $profile->timezone,
                'show_on_leaderboard' => $profile->show_on_leaderboard,
                'leaderboard_display_name' => $profile->leaderboard_display_name,
                'display_name' => $profile->resolvedDisplayName(),
            ],
            'streak' => [
                'current' => $streak->current,
                'longest' => $streak->longest,
                'completed_today' => $streak->completedToday,
                'at_risk' => $streak->atRisk,
                'next_target' => $streak->nextTarget,
            ],
            'daily_goal' => [
                'target' => $goal->target,
                'earned' => $goal->earned,
                'remaining' => $goal->remaining,
                'percent' => $goal->percent,
                'completed' => $goal->completed,
            ],
            'weekly_xp' => $weeklyXp,
            'leaderboard' => $board,
            'next_step' => $nextStep,
            'achievements' => null,
        ];
    }
}
