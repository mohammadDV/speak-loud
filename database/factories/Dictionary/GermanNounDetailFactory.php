<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Models\GermanNounDetail;
use App\Domain\Dictionary\Models\Sense;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GermanNounDetail>
 */
class GermanNounDetailFactory extends Factory
{
    protected $model = GermanNounDetail::class;

    public function definition(): array
    {
        return [
            'sense_id' => Sense::factory(),
            'grammatical_gender' => GrammaticalGender::Feminine,
            'article' => GermanArticle::Die,
            'plural_form' => fake()->word(),
            'grammar_notes' => null,
        ];
    }
}
