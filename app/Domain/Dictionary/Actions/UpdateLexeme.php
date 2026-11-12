<?php

namespace App\Domain\Dictionary\Actions;

use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateLexeme
{
    public function __construct(
        private readonly ContentPublishingService $publishing,
    ) {}

    /**
     * @param  array{
     *     lemma?: string,
     *     slug?: string|null,
     *     pronunciation?: string|null,
     *     disambiguation?: string|null
     * }  $data
     */
    public function execute(Lexeme $lexeme, array $data, User $actor): Lexeme
    {
        return DB::transaction(function () use ($lexeme, $data, $actor): Lexeme {
            $lexeme->loadMissing('language');

            $updates = [
                'updated_by' => $actor->id,
            ];

            if (array_key_exists('lemma', $data)) {
                $lemma = trim((string) $data['lemma']);
                $updates['lemma'] = $lemma;
                $updates['normalized_lemma'] = TextNormalizer::normalize($lemma, $lexeme->language?->code);
            }

            foreach (['slug', 'pronunciation', 'disambiguation'] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            $lexeme->update($updates);
            $lexeme = $this->publishing->demotePublishedAfterEdit($lexeme->fresh() ?? $lexeme, $actor, 'Lexeme edited');

            return $lexeme->fresh(['forms', 'language', 'senses']) ?? $lexeme;
        });
    }
}
