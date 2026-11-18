<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonTranslation>
 */
class LessonTranslationFactory extends Factory
{
    protected $model = LessonTranslation::class;

    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'language_code' => 'en',
            'title' => fake()->sentence(4),
            'body_markdown' => '# '.fake()->sentence(3)."\n\n".fake()->paragraph(),
        ];
    }
}
