<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Collocation;
use App\Domain\Dictionary\Models\CollocationTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CollocationTranslation>
 */
class CollocationTranslationFactory extends Factory
{
    protected $model = CollocationTranslation::class;

    public function definition(): array
    {
        return [
            'collocation_id' => Collocation::factory(),
            'language_id' => Language::query()->value('id') ?? 1,
            'translation' => fake()->words(3, true),
            'notes' => null,
            'status' => ContentStatus::Draft,
        ];
    }
}
