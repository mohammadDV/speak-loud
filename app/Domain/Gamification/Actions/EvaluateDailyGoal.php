<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;

final class EvaluateDailyGoal
{
    public function __construct(
        private readonly CompleteDailyGoal $completeDailyGoal,
    ) {}

    /**
     * @return array{completed: bool, bonus: int, local_date: string}
     */
    public function execute(UserGamificationProfile $profile, UserDailyActivity $daily): array
    {
        $localDate = $daily->activity_date->toDateString();

        if ($daily->goal_completed_at !== null) {
            return [
                'completed' => false,
                'bonus' => 0,
                'local_date' => $localDate,
            ];
        }

        if ($daily->xp_earned < $daily->daily_goal) {
            return [
                'completed' => false,
                'bonus' => 0,
                'local_date' => $localDate,
            ];
        }

        return $this->completeDailyGoal->execute($profile, $daily);
    }
}
