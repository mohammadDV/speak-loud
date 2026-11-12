<?php

namespace App\Domain\Dictionary\Actions;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\PhraseType;
use App\Domain\Dictionary\Enums\TranslationType;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateSense
{
    public function __construct(
        private readonly ContentPublishingService $publishing,
    ) {}

    /**
     * @param  array{
     *     part_of_speech_id?: int|null,
     *     definition?: string|null,
     *     definition_language_id?: int|null,
     *     usage_label?: string|null,
     *     domain?: string|null,
     *     sort_order?: int,
     *     difficulty_level_id?: int|null,
     *     translations?: list<array{target_language_id: int, translation_text: string, translation_type?: string}>,
     *     examples?: list<array{language_id: int, sentence: string, translations?: list<array{language_id: int, translation: string}>}>,
     *     collocations?: list<array{phrase: string, phrase_type?: string, translations?: list<array{language_id: int, translation: string}>}>,
     *     german_noun?: array{article?: string, grammatical_gender?: string, plural_form?: string}|null
     * }  $data
     */
    public function execute(Lexeme $lexeme, array $data, User $actor): Sense
    {
        return DB::transaction(function () use ($lexeme, $data, $actor): Sense {
            $sense = $lexeme->senses()->create([
                'part_of_speech_id' => $data['part_of_speech_id'] ?? null,
                'definition' => $data['definition'] ?? null,
                'definition_language_id' => $data['definition_language_id'] ?? null,
                'usage_label' => $data['usage_label'] ?? null,
                'domain' => $data['domain'] ?? null,
                'sort_order' => $data['sort_order'] ?? 0,
                'status' => ContentStatus::Draft,
            ]);

            if (! empty($data['difficulty_level_id'])) {
                $sense->levels()->create([
                    'difficulty_level_id' => $data['difficulty_level_id'],
                    'is_primary' => true,
                    'source' => 'editorial',
                    'reviewed_by' => $actor->id,
                    'assessed_at' => now(),
                ]);
            }

            foreach ($data['translations'] ?? [] as $i => $translation) {
                $sense->translations()->create([
                    'target_language_id' => $translation['target_language_id'],
                    'translation_text' => $translation['translation_text'],
                    'translation_type' => $translation['translation_type'] ?? TranslationType::Equivalent->value,
                    'sort_order' => $i,
                    'status' => ContentStatus::Draft,
                ]);
            }

            foreach ($data['examples'] ?? [] as $i => $exampleData) {
                $example = $sense->examples()->create([
                    'language_id' => $exampleData['language_id'],
                    'sentence' => $exampleData['sentence'],
                    'sort_order' => $i,
                    'status' => ContentStatus::Draft,
                ]);

                foreach ($exampleData['translations'] ?? [] as $exampleTranslation) {
                    $example->translations()->create([
                        'language_id' => $exampleTranslation['language_id'],
                        'translation' => $exampleTranslation['translation'],
                        'status' => ContentStatus::Draft,
                    ]);
                }
            }

            foreach ($data['collocations'] ?? [] as $i => $collocationData) {
                $collocation = $sense->collocations()->create([
                    'phrase' => $collocationData['phrase'],
                    'phrase_type' => $collocationData['phrase_type'] ?? PhraseType::Collocation->value,
                    'sort_order' => $i,
                    'status' => ContentStatus::Draft,
                ]);

                foreach ($collocationData['translations'] ?? [] as $collocationTranslation) {
                    $collocation->translations()->create([
                        'language_id' => $collocationTranslation['language_id'],
                        'translation' => $collocationTranslation['translation'],
                        'status' => ContentStatus::Draft,
                    ]);
                }
            }

            if (! empty($data['german_noun'])) {
                $this->upsertGermanNoun($sense, $data['german_noun']);
            }

            $lexeme->update(['updated_by' => $actor->id]);
            $this->publishing->demotePublishedAfterEdit($lexeme->fresh() ?? $lexeme, $actor, 'Sense created');

            return $sense->fresh([
                'translations',
                'examples.translations',
                'collocations.translations',
                'germanNounDetail',
                'levels',
                'partOfSpeech',
            ]) ?? $sense;
        });
    }

    /**
     * @param  array{article?: string, grammatical_gender?: string, plural_form?: string}  $data
     */
    private function upsertGermanNoun(Sense $sense, array $data): void
    {
        $article = isset($data['article']) ? mb_strtolower($data['article']) : null;

        if ($article !== null && ! GermanArticle::isValid($article)) {
            throw ValidationException::withMessages([
                'german_noun.article' => 'German article must be der, die, or das.',
            ]);
        }

        $sense->germanNounDetail()->updateOrCreate(
            ['sense_id' => $sense->id],
            [
                'article' => $article,
                'grammatical_gender' => $data['grammatical_gender'] ?? null,
                'plural_form' => $data['plural_form'] ?? null,
            ],
        );
    }
}
