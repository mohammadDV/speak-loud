<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sense>
 */
class SenseFactory extends Factory
{
    protected $model = Sense::class;

    public function definition(): array
    {
        return [
            'lexeme_id' => Lexeme::factory(),
            'part_of_speech_id' => PartOfSpeech::query()->value('id'),
            'definition' => fake()->sentence(),
            'definition_language_id' => null,
            'usage_label' => null,
            'domain' => null,
            'frequency_rank' => null,
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
