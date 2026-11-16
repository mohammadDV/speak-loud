<?php

namespace App\Domain\Learning\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UserSenseFlagListService
{
    public const DEFAULT_LIMIT = 100;

    /**
     * @return list<array{
     *     sense_id: int,
     *     lemma: string,
     *     article: string|null,
     *     translation: string|null,
     *     is_important: bool,
     *     is_learned: bool
     * }>
     */
    public function important(
        User $user,
        string $translationLanguageCode,
        SenseFlagContext $context = SenseFlagContext::Article,
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        return $this->list($user, 'important', $translationLanguageCode, $context, $limit);
    }

    /**
     * @return list<array{
     *     sense_id: int,
     *     lemma: string,
     *     article: string|null,
     *     translation: string|null,
     *     is_important: bool,
     *     is_learned: bool
     * }>
     */
    public function learned(
        User $user,
        string $translationLanguageCode,
        SenseFlagContext $context = SenseFlagContext::Article,
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        return $this->list($user, 'learned', $translationLanguageCode, $context, $limit);
    }

    /**
     * @return list<array{
     *     sense_id: int,
     *     lemma: string,
     *     article: string|null,
     *     translation: string|null,
     *     is_important: bool,
     *     is_learned: bool
     * }>
     */
    private function list(
        User $user,
        string $flag,
        string $translationLanguageCode,
        SenseFlagContext $context,
        int $limit,
    ): array {
        if (! in_array($flag, ['important', 'learned'], true)) {
            throw new InvalidArgumentException('Flag must be important or learned.');
        }

        $limit = min(max($limit, 1), self::DEFAULT_LIMIT);
        $flagColumn = $flag === 'important' ? 'is_important' : 'is_learned';

        $languageId = Language::query()
            ->where('code', $translationLanguageCode)
            ->where('is_active', true)
            ->value('id');

        $query = DB::table('user_sense_flags')
            ->select([
                'user_sense_flags.sense_id',
                'user_sense_flags.is_important',
                'user_sense_flags.is_learned',
                'lexemes.lemma',
                'sense_translations.translation_text as translation',
            ])
            ->join('senses', 'senses.id', '=', 'user_sense_flags.sense_id')
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->leftJoin('sense_translations', function ($join) use ($languageId): void {
                $join->on('sense_translations.sense_id', '=', 'senses.id')
                    ->where('sense_translations.status', ContentStatus::Published->value);

                if ($languageId !== null) {
                    $join->where('sense_translations.target_language_id', $languageId);
                }
            })
            ->where('user_sense_flags.user_id', $user->id)
            ->where('user_sense_flags.context', $context->value)
            ->where('user_sense_flags.'.$flagColumn, true)
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at');

        if ($context === SenseFlagContext::Article) {
            $query->addSelect('german_noun_details.article')
                ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
                ->whereNotNull('german_noun_details.article');
        } else {
            $query->whereExists(function ($exists): void {
                $exists->selectRaw('1')
                    ->from('sense_translations as published_translations')
                    ->whereColumn('published_translations.sense_id', 'senses.id')
                    ->where('published_translations.status', ContentStatus::Published->value);
            });
        }

        $rows = $query
            ->orderByDesc('user_sense_flags.updated_at')
            ->orderBy('user_sense_flags.sense_id')
            ->orderBy('sense_translations.sort_order')
            ->limit($limit * 3)
            ->get();

        $words = [];
        $seen = [];

        foreach ($rows as $row) {
            $senseId = (int) $row->sense_id;
            if (isset($seen[$senseId])) {
                continue;
            }

            $article = null;
            if ($context === SenseFlagContext::Article) {
                $article = strtolower((string) $row->article);
                if (! GermanArticle::isValid($article)) {
                    continue;
                }
            }

            $seen[$senseId] = true;
            $words[] = [
                'sense_id' => $senseId,
                'lemma' => (string) $row->lemma,
                'article' => $article,
                'translation' => $row->translation !== null ? (string) $row->translation : null,
                'is_important' => (bool) $row->is_important,
                'is_learned' => (bool) $row->is_learned,
            ];

            if (count($words) >= $limit) {
                break;
            }
        }

        return $words;
    }
}
