<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\DTOs\DailyGoalProgress;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;

final class DailyGoalService
{
    public function progress(?UserDailyActivity $daily, UserGamificationProfile $profile): DailyGoalProgress
    {
        $target = $daily !== null ? $daily->daily_goal : $profile->daily_goal;
        $earned = $daily !== null ? $daily->xp_earned : 0;
        $completed = $daily !== null && $daily->goal_completed_at !== null;
        $remaining = max(0, $target - $earned);
        $percent = $target > 0 ? min(100, (int) floor(($earned / $target) * 100)) : 0;

        return new DailyGoalProgress(
            target: $target,
            earned: $earned,
            remaining: $remaining,
            percent: $percent,
            completed: $completed || $earned >= $target,
        );
    }
}
