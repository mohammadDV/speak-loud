<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Lexeme>
 */
class LexemeFactory extends Factory
{
    protected $model = Lexeme::class;

    public function definition(): array
    {
        $lemma = fake()->unique()->word();

        return [
            'language_id' => Language::query()->value('id') ?? Language::factory(),
            'lemma' => $lemma,
            'normalized_lemma' => TextNormalizer::normalize($lemma, 'de'),
            'slug' => Str::slug($lemma).'-'.fake()->unique()->numerify('###'),
            'pronunciation' => null,
            'disambiguation' => null,
            'status' => ContentStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ContentStatus::Published,
        ]);
    }

    public function forLanguage(Language $language): static
    {
        return $this->state(fn (array $attributes) => [
            'language_id' => $language->id,
            'normalized_lemma' => TextNormalizer::normalize(
                $attributes['lemma'] ?? fake()->word(),
                $language->code,
            ),
        ]);
    }
}
