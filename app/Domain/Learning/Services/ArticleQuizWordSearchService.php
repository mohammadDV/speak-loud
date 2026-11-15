<?php

namespace App\Domain\Learning\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ArticleQuizWordSearchService
{
    public const MIN_QUERY_LENGTH = 2;

    public const MAX_SUGGESTIONS = 8;

    public const SUGGEST_RATE_LIMIT = 30;

    public const SUGGEST_RATE_DECAY_SECONDS = 60;

    public function __construct(
        private readonly ArticleQuizCache $quizCache,
        private readonly ArticleQuizService $quizService,
    ) {}

    /**
     * Lightweight typeahead for published German nouns that have an article.
     *
     * @return list<array{sense_id: int, lemma: string, article: string, translation: string|null}>
     */
    public function suggest(string $query, string $translationLanguageCode, int $limit = self::MAX_SUGGESTIONS): array
    {
        $query = trim($query);
        $limit = min(max($limit, 1), self::MAX_SUGGESTIONS);

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $this->assertTranslationLanguage($translationLanguageCode);
        $this->assertWithinSuggestRateLimit();

        $normalized = TextNormalizer::normalize($query, 'de');

        return $this->quizCache->rememberSuggestions($normalized, $translationLanguageCode, function () use ($query, $normalized, $translationLanguageCode, $limit) {
            return $this->querySuggestions($query, $normalized, $translationLanguageCode, $limit);
        });
    }

    /**
     * Full word card for the details sheet (article, meaning, examples, collocations).
     *
     * @return array{
     *     sense_id: int,
     *     lemma: string,
     *     article: string,
     *     gender: string|null,
     *     plural_form: string|null,
     *     translation: string|null,
     *     translations: list<string>,
     *     examples: list<array{sentence: string, translation: string|null}>,
     *     collocations: list<array{phrase: string, translation: string|null}>,
     *     is_important: bool,
     *     is_learned: bool
     * }|null
     */
    public function details(int $senseId, string $translationLanguageCode, ?User $user = null): ?array
    {
        $this->assertTranslationLanguage($translationLanguageCode);

        $row = DB::table('senses')
            ->select([
                'senses.id as sense_id',
                'lexemes.lemma',
                'german_noun_details.article',
            ])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->where('senses.id', $senseId)
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNotNull('german_noun_details.article')
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at')
            ->first();

        if ($row === null || ! GermanArticle::isValid(strtolower((string) $row->article))) {
            return null;
        }

        $cards = $this->quizService->hydrateCards([(int) $row->sense_id], $translationLanguageCode, $user);

        return $cards[0] ?? null;
    }

    /**
     * @return list<array{sense_id: int, lemma: string, article: string, translation: string|null}>
     */
    private function querySuggestions(
        string $query,
        string $normalized,
        string $translationLanguageCode,
        int $limit,
    ): array {
        $languageId = Language::query()
            ->where('code', $translationLanguageCode)
            ->where('is_active', true)
            ->value('id');

        $normalizedPrefix = $this->likePrefix($normalized);
        $lemmaPrefix = $this->likePrefix($query);

        $rows = DB::table('senses')
            ->select([
                'senses.id as sense_id',
                'lexemes.lemma',
                'german_noun_details.article',
                'sense_translations.translation_text as translation',
            ])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->leftJoin('sense_translations', function ($join) use ($languageId): void {
                $join->on('sense_translations.sense_id', '=', 'senses.id')
                    ->where('sense_translations.status', ContentStatus::Published->value);

                if ($languageId !== null) {
                    $join->where('sense_translations.target_language_id', $languageId);
                }
            })
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNotNull('german_noun_details.article')
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at')
            ->where(function ($outer) use ($query, $normalized, $normalizedPrefix, $lemmaPrefix): void {
                $outer->where('lexemes.normalized_lemma', $normalized)
                    ->orWhereRaw("lexemes.normalized_lemma LIKE ? ESCAPE '!'", [$normalizedPrefix])
                    ->orWhere('lexemes.lemma', $query)
                    ->orWhereRaw("lexemes.lemma LIKE ? ESCAPE '!'", [$lemmaPrefix]);
            })
            ->orderByRaw(
                "CASE
                    WHEN lexemes.normalized_lemma = ? THEN 0
                    WHEN lexemes.lemma = ? THEN 1
                    WHEN lexemes.normalized_lemma LIKE ? ESCAPE '!' THEN 2
                    ELSE 3
                END",
                [$normalized, $query, $normalizedPrefix],
            )
            ->orderBy('lexemes.lemma')
            ->orderBy('senses.id')
            ->orderBy('sense_translations.sort_order')
            ->limit($limit * 3)
            ->get();

        $suggestions = [];
        $seenSenseIds = [];

        foreach ($rows as $row) {
            $senseId = (int) $row->sense_id;
            if (isset($seenSenseIds[$senseId])) {
                continue;
            }

            $article = strtolower((string) $row->article);
            if (! GermanArticle::isValid($article)) {
                continue;
            }

            $seenSenseIds[$senseId] = true;
            $suggestions[] = [
                'sense_id' => $senseId,
                'lemma' => (string) $row->lemma,
                'article' => $article,
                'translation' => $row->translation !== null ? (string) $row->translation : null,
            ];

            if (count($suggestions) >= $limit) {
                break;
            }
        }

        return $suggestions;
    }

    /**
     * Build a prefix LIKE pattern with an uncommon escape character so %/_ are literal.
     */
    private function likePrefix(string $value): string
    {
        return str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value,
        ).'%';
    }

    private function assertWithinSuggestRateLimit(): void
    {
        $key = 'article-quiz-suggest|'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, self::SUGGEST_RATE_LIMIT)) {
            throw ValidationException::withMessages([
                'search_query' => 'Too many searches. Please wait a moment and try again.',
            ]);
        }

        RateLimiter::hit($key, self::SUGGEST_RATE_DECAY_SECONDS);
    }

    private function assertTranslationLanguage(string $code): void
    {
        $exists = Language::query()
            ->where('is_active', true)
            ->where('code', $code)
            ->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'translation_language' => 'Choose a translation language.',
            ]);
        }
    }
}
