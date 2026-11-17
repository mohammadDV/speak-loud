<?php

namespace Database\Factories\Gamification;

use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGamificationProfile>
 */
class UserGamificationProfileFactory extends Factory
{
    protected $model = UserGamificationProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'total_xp' => 0,
            'current_streak' => 0,
            'longest_streak' => 0,
            'last_activity_date' => null,
            'daily_goal' => (int) config('gamification.daily_goal.default', 20),
            'daily_goal_chosen_at' => now(),
            'timezone' => (string) config('gamification.default_user_timezone', 'Europe/Berlin'),
            'show_on_leaderboard' => (bool) config('gamification.leaderboard.default_visible', false),
            'leaderboard_display_name' => null,
        ];
    }

    public function needsGoalSetup(): static
    {
        return $this->state(fn (): array => [
            'daily_goal_chosen_at' => null,
        ]);
    }
}
