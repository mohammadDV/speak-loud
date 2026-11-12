<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\Sense;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Example>
 */
class ExampleFactory extends Factory
{
    protected $model = Example::class;

    public function definition(): array
    {
        return [
            'sense_id' => Sense::factory(),
            'language_id' => Language::query()->value('id') ?? 1,
            'sentence' => fake()->sentence(),
            'usage_note' => null,
            'difficulty_level_id' => null,
            'status' => ContentStatus::Draft,
            'sort_order' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
        ]);
    }
}
