<?php

namespace Database\Factories\Gamification;

use App\Domain\Gamification\Models\ProcessedEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessedEvent>
 */
class ProcessedEventFactory extends Factory
{
    protected $model = ProcessedEvent::class;

    public function definition(): array
    {
        $total = fake()->numberBetween(5, 20);
        $correct = fake()->numberBetween(0, $total);

        return [
            'user_id' => User::factory(),
            'activity_type' => 'article_quiz',
            'source_id' => (string) fake()->unique()->numberBetween(1, 999999),
            'mode' => null,
            'total_questions' => $total,
            'correct_answers' => $correct,
            'is_perfect' => $correct === $total,
            'xp_total' => fake()->numberBetween(0, 50),
            'local_date' => now()->toDateString(),
            'summary' => [],
            'processed_at' => now(),
        ];
    }
}
