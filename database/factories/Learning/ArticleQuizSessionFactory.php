<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\ArticleQuizSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleQuizSession>
 */
class ArticleQuizSessionFactory extends Factory
{
    protected $model = ArticleQuizSession::class;

    public function definition(): array
    {
        $wordCount = fake()->numberBetween(5, 20);
        $correct = fake()->numberBetween(0, $wordCount);

        return [
            'user_id' => User::factory(),
            'level_codes' => fake()->optional()->randomElement([['A1'], ['A1', 'A2'], ['B1']]),
            'category_codes' => fake()->optional()->randomElement([['family'], ['family', 'food'], ['general']]),
            'word_count' => $wordCount,
            'translation_language_code' => 'en',
            'correct_count' => $correct,
            'incorrect_count' => $wordCount - $correct,
            'percentage' => (int) round(($correct / $wordCount) * 100),
            'sense_ids' => [],
            'completed_at' => now(),
        ];
    }
}
