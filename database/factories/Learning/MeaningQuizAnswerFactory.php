<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\MeaningQuizAnswer;
use App\Domain\Learning\Models\MeaningQuizSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeaningQuizAnswer>
 */
class MeaningQuizAnswerFactory extends Factory
{
    protected $model = MeaningQuizAnswer::class;

    public function definition(): array
    {
        $meaning = fake()->word();

        return [
            'meaning_quiz_session_id' => MeaningQuizSession::factory(),
            'sense_id' => fake()->numberBetween(1, 9999),
            'lemma' => fake()->word(),
            'correct_meaning' => $meaning,
            'selected_meaning' => $meaning,
            'is_correct' => true,
            'match_score' => 100,
            'position' => 0,
        ];
    }
}
