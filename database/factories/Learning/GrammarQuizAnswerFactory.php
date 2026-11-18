<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\GrammarQuizAnswer;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Domain\Learning\Models\LessonQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GrammarQuizAnswer>
 */
class GrammarQuizAnswerFactory extends Factory
{
    protected $model = GrammarQuizAnswer::class;

    public function definition(): array
    {
        $correct = fake()->boolean();

        return [
            'grammar_quiz_session_id' => GrammarQuizSession::factory(),
            'lesson_question_id' => LessonQuestion::factory(),
            'prompt' => 'Conjugate "kommen" for du: du ___.',
            'correct_answer' => 'kommst',
            'selected_answer' => $correct ? 'kommst' : 'kommt',
            'is_correct' => $correct,
            'match_score' => $correct ? 100 : 0,
            'position' => fake()->numberBetween(1, 20),
        ];
    }
}
