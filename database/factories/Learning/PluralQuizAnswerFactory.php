<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\PluralQuizAnswer;
use App\Domain\Learning\Models\PluralQuizSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PluralQuizAnswer>
 */
class PluralQuizAnswerFactory extends Factory
{
    protected $model = PluralQuizAnswer::class;

    public function definition(): array
    {
        $plural = fake()->word().'e';

        return [
            'plural_quiz_session_id' => PluralQuizSession::factory(),
            'sense_id' => fake()->numberBetween(1, 9999),
            'lemma' => fake()->word(),
            'correct_plural' => $plural,
            'selected_plural' => $plural,
            'is_correct' => true,
            'match_score' => 100,
            'position' => 0,
        ];
    }
}
