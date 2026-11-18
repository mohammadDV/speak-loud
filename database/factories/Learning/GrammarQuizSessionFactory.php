<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrammarQuizSession>
 */
class GrammarQuizSessionFactory extends Factory
{
    protected $model = GrammarQuizSession::class;

    public function definition(): array
    {
        $wordCount = fake()->numberBetween(5, 20);
        $correct = fake()->numberBetween(0, $wordCount);

        return [
            'user_id' => User::factory(),
            'lesson_ids' => [1],
            'word_count' => $wordCount,
            'quiz_type' => GrammarQuizType::MultipleChoice,
            'translation_language_code' => 'en',
            'correct_count' => $correct,
            'incorrect_count' => $wordCount - $correct,
            'percentage' => (int) round(($correct / $wordCount) * 100),
            'question_ids' => [],
            'completed_at' => now(),
        ];
    }
}
