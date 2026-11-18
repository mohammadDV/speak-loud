<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\UserLessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLessonProgress>
 */
class UserLessonProgressFactory extends Factory
{
    protected $model = UserLessonProgress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_id' => Lesson::factory(),
            'last_completed_at' => now(),
            'best_percentage' => fake()->numberBetween(0, 100),
            'attempts_count' => fake()->numberBetween(1, 10),
        ];
    }
}
