<?php

namespace App\Domain\Dictionary\Actions;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\FormType;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateLexeme
{
    /**
     * @param  array{
     *     language_id: int,
     *     lemma: string,
     *     slug?: string|null,
     *     pronunciation?: string|null,
     *     disambiguation?: string|null,
     *     forms?: list<array{form: string, form_type?: string, is_primary?: bool}>
     * }  $data
     */
    public function execute(array $data, User $actor): Lexeme
    {
        return DB::transaction(function () use ($data, $actor): Lexeme {
            /** @var Language $language */
            $language = Language::query()->findOrFail($data['language_id']);
            $lemma = trim($data['lemma']);

            $lexeme = Lexeme::query()->create([
                'language_id' => $language->id,
                'lemma' => $lemma,
                'normalized_lemma' => TextNormalizer::normalize($lemma, $language->code),
                'slug' => $data['slug'] ?? Str::slug($lemma).'-'.$language->code.'-'.Str::lower(Str::random(4)),
                'pronunciation' => $data['pronunciation'] ?? null,
                'disambiguation' => $data['disambiguation'] ?? null,
                'status' => ContentStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            foreach ($data['forms'] ?? [] as $formData) {
                $form = trim($formData['form']);
                $lexeme->forms()->create([
                    'form' => $form,
                    'normalized_form' => TextNormalizer::normalize($form, $language->code),
                    'form_type' => $formData['form_type'] ?? FormType::Inflected->value,
                    'is_primary' => (bool) ($formData['is_primary'] ?? false),
                ]);
            }

            return $lexeme->fresh(['forms', 'language']) ?? $lexeme;
        });
    }
}
