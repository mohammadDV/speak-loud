<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'code' => 'course-'.fake()->unique()->slug(2),
            'cefr_level' => 'A1',
            'status' => LessonContentStatus::Published,
            'position' => fake()->numberBetween(1, 20),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => LessonContentStatus::Draft]);
    }
}
