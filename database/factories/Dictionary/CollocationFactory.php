<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\PhraseType;
use App\Domain\Dictionary\Models\Collocation;
use App\Domain\Dictionary\Models\Sense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Collocation>
 */
class CollocationFactory extends Factory
{
    protected $model = Collocation::class;

    public function definition(): array
    {
        return [
            'sense_id' => Sense::factory(),
            'phrase' => fake()->words(3, true),
            'pattern_type' => 'verb+noun',
            'phrase_type' => PhraseType::Collocation,
            'explanation' => fake()->sentence(),
            'frequency_label' => 'common',
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
