<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\ArticleQuizAnswer;
use App\Domain\Learning\Models\ArticleQuizSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticleQuizAnswer>
 */
class ArticleQuizAnswerFactory extends Factory
{
    protected $model = ArticleQuizAnswer::class;

    public function definition(): array
    {
        $article = fake()->randomElement(['der', 'die', 'das']);

        return [
            'article_quiz_session_id' => ArticleQuizSession::factory(),
            'sense_id' => fake()->numberBetween(1, 9999),
            'lemma' => fake()->word(),
            'correct_article' => $article,
            'selected_article' => $article,
            'is_correct' => true,
            'position' => 0,
        ];
    }
}
