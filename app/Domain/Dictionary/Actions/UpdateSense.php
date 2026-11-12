<?php

namespace App\Domain\Dictionary\Actions;

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateSense
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
     *     german_noun?: array{article?: string, grammatical_gender?: string, plural_form?: string}|null
     * }  $data
     */
    public function execute(Sense $sense, array $data, User $actor): Sense
    {
        return DB::transaction(function () use ($sense, $data, $actor): Sense {
            $updates = [];

            foreach (['part_of_speech_id', 'definition', 'definition_language_id', 'usage_label', 'domain', 'sort_order'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($updates !== []) {
                $sense->update($updates);
            }

            if (array_key_exists('difficulty_level_id', $data)) {
                if (blank($data['difficulty_level_id'])) {
                    $sense->levels()->where('is_primary', true)->delete();
                } else {
                    $sense->levels()->updateOrCreate(
                        ['is_primary' => true],
                        [
                            'difficulty_level_id' => $data['difficulty_level_id'],
                            'source' => 'editorial',
                            'reviewed_by' => $actor->id,
                            'assessed_at' => now(),
                        ],
                    );
                }
            }

            if (array_key_exists('german_noun', $data) && is_array($data['german_noun'])) {
                $article = isset($data['german_noun']['article'])
                    ? mb_strtolower((string) $data['german_noun']['article'])
                    : null;

                if ($article !== null && $article !== '' && ! GermanArticle::isValid($article)) {
                    throw ValidationException::withMessages([
                        'german_noun.article' => 'German article must be der, die, or das.',
                    ]);
                }

                if ($article === null && blank($data['german_noun']['plural_form'] ?? null) && blank($data['german_noun']['grammatical_gender'] ?? null)) {
                    $sense->germanNounDetail()->delete();
                } else {
                    $sense->germanNounDetail()->updateOrCreate(
                        ['sense_id' => $sense->id],
                        [
                            'article' => $article ?: null,
                            'grammatical_gender' => $data['german_noun']['grammatical_gender'] ?? null,
                            'plural_form' => $data['german_noun']['plural_form'] ?? null,
                        ],
                    );
                }
            }

            $lexeme = $sense->lexeme;
            if ($lexeme !== null) {
                $lexeme->update(['updated_by' => $actor->id]);
                $this->publishing->demotePublishedAfterEdit($lexeme, $actor, 'Sense edited');
            }

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
}
