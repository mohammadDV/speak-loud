<?php

namespace Database\Factories\Learning;

use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\CourseTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseTranslation>
 */
class CourseTranslationFactory extends Factory
{
    protected $model = CourseTranslation::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'language_code' => 'en',
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(8),
        ];
    }
}
