<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\ExampleTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExampleTranslation>
 */
class ExampleTranslationFactory extends Factory
{
    protected $model = ExampleTranslation::class;

    public function definition(): array
    {
        return [
            'example_id' => Example::factory(),
            'language_id' => Language::query()->value('id') ?? 1,
            'translation' => fake()->sentence(),
            'notes' => null,
            'status' => ContentStatus::Draft,
        ];
    }
}
