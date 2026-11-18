<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\LessonQuestion;
use App\Domain\Learning\Models\LessonQuestionTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuestionTranslation>
 */
class LessonQuestionTranslationFactory extends Factory
{
    protected $model = LessonQuestionTranslation::class;

    public function definition(): array
    {
        return [
            'lesson_question_id' => LessonQuestion::factory(),
            'language_code' => 'en',
            'prompt' => 'Conjugate "kommen" for du: du ___.',
            'hint' => null,
            'explanation' => null,
        ];
    }
}
