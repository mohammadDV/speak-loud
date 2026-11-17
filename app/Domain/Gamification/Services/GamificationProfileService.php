<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Models\User;

final class GamificationProfileService
{
    public function forUser(User|int $user): UserGamificationProfile
    {
        $userId = $user instanceof User ? $user->id : $user;

        return UserGamificationProfile::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'total_xp' => 0,
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
                'daily_goal' => (int) config('gamification.daily_goal.default', 20),
                'daily_goal_chosen_at' => null,
                'timezone' => (string) config('gamification.default_user_timezone', 'Europe/Berlin'),
                'show_on_leaderboard' => (bool) config('gamification.leaderboard.default_visible', false),
                'leaderboard_display_name' => null,
            ],
        );
    }

    public function lockForUser(int $userId): UserGamificationProfile
    {
        $profile = $this->forUser($userId);

        /** @var UserGamificationProfile $locked */
        $locked = UserGamificationProfile::query()
            ->whereKey($profile->id)
            ->lockForUpdate()
            ->firstOrFail();

        return $locked;
    }
}
