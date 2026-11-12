<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\TranslationType;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SenseTranslation>
 */
class SenseTranslationFactory extends Factory
{
    protected $model = SenseTranslation::class;

    public function definition(): array
    {
        return [
            'sense_id' => Sense::factory(),
            'target_language_id' => Language::query()->value('id') ?? 1,
            'translation_text' => fake()->word(),
            'translation_type' => TranslationType::Equivalent,
            'notes' => null,
            'register' => null,
            'sort_order' => 0,
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
        ]);
    }
}
