<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuestion>
 */
class LessonQuestionFactory extends Factory
{
    protected $model = LessonQuestion::class;

    public function definition(): array
    {
        $answer = fake()->randomElement(['komme', 'kommst', 'kommt', 'kommen']);

        return [
            'lesson_id' => Lesson::factory(),
            'import_key' => 'q:'.fake()->unique()->uuid(),
            'topic' => fake()->randomElement([
                'pronouns',
                'regular_conjugation',
                'irregular_verbs',
                'stem_changing',
            ]),
            'answer' => $answer,
            'distractors' => array_values(array_diff(['komme', 'kommst', 'kommt', 'kommen'], [$answer])),
            'blank_token' => '___',
            'source_lemma' => 'kommen',
            'sense_import_key' => null,
            'meta' => ['pronoun' => 'du'],
            'status' => LessonContentStatus::Published,
        ];
    }
}
