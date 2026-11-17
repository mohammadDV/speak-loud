<?php

namespace Database\Factories\Gamification;

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserDailyActivity>
 */
class UserDailyActivityFactory extends Factory
{
    protected $model = UserDailyActivity::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'activity_date' => now()->toDateString(),
            'xp_earned' => 0,
            'bonus_xp_earned' => 0,
            'quizzes_completed' => 0,
            'questions_answered' => 0,
            'qualifying_activity_count' => 0,
            'daily_goal' => (int) config('gamification.daily_goal.default', 20),
            'goal_completed_at' => null,
        ];
    }
}
