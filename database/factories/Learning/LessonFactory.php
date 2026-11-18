<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'code' => 'lesson-'.fake()->unique()->slug(2),
            'position' => fake()->numberBetween(1, 20),
            'status' => LessonContentStatus::Published,
            'cefr_level' => 'A1',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => LessonContentStatus::Draft]);
    }
}
