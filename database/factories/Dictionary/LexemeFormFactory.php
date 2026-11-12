<?php

namespace Database\Factories\Dictionary;

use App\Domain\Dictionary\Enums\FormType;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\LexemeForm;
use App\Domain\Dictionary\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LexemeForm>
 */
class LexemeFormFactory extends Factory
{
    protected $model = LexemeForm::class;

    public function definition(): array
    {
        $form = fake()->word();

        return [
            'lexeme_id' => Lexeme::factory(),
            'form' => $form,
            'normalized_form' => TextNormalizer::normalize($form, 'de'),
            'form_type' => FormType::Inflected,
            'grammatical_features' => null,
            'is_primary' => false,
        ];
    }
}
