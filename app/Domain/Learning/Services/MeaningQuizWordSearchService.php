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

class MeaningQuizWordSearchService
{
    public const MIN_QUERY_LENGTH = 2;

    public const MAX_QUERY_LENGTH = 64;

    public const MAX_SUGGESTIONS = 8;

    public const SUGGEST_RATE_LIMIT = 30;

    public const DETAILS_RATE_LIMIT = 60;

    public const SUGGEST_RATE_DECAY_SECONDS = 60;

    public const DETAILS_RATE_DECAY_SECONDS = 60;

    public function __construct(
        private readonly MeaningQuizCache $quizCache,
        private readonly MeaningQuizService $quizService,
    ) {}

    /**
     * Lightweight typeahead for published German vocabulary with a target-language translation.
     *
     * @return list<array{sense_id: int, lemma: string, article: string|null, translation: string|null}>
     */
    public function suggest(string $query, string $translationLanguageCode, int $limit = self::MAX_SUGGESTIONS): array
    {
        $query = $this->normalizeQuery($query);
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
     * Full word card for the details sheet (meaning, examples, collocations).
     *
     * @return array{
     *     sense_id: int,
     *     lemma: string,
     *     article: string|null,
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
        $this->assertWithinDetailsRateLimit();

        $row = DB::table('senses')
            ->select([
                'senses.id as sense_id',
                'lexemes.lemma',
                'german_noun_details.article',
            ])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->leftJoin('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->where('senses.id', $senseId)
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at')
            ->whereExists(function ($query) use ($translationLanguageCode): void {
                $query->selectRaw('1')
                    ->from('sense_translations')
                    ->join('languages as target_languages', 'target_languages.id', '=', 'sense_translations.target_language_id')
                    ->whereColumn('sense_translations.sense_id', 'senses.id')
                    ->where('sense_translations.status', ContentStatus::Published->value)
                    ->where('target_languages.code', $translationLanguageCode);
            })
            ->first();

        if ($row === null) {
            return null;
        }

        $cards = $this->quizService->hydrateCards([(int) $row->sense_id], $translationLanguageCode, $user, withExtras: true);
        $card = $cards[0] ?? null;
        if ($card === null) {
            return null;
        }

        $article = $row->article !== null ? strtolower((string) $row->article) : null;
        if ($article !== null && ! GermanArticle::isValid($article)) {
            $article = null;
        }

        return [
            'sense_id' => (int) $card['sense_id'],
            'lemma' => (string) $card['lemma'],
            'article' => $article,
            'translation' => $card['translation'],
            'translations' => $card['translations'],
            'examples' => $card['examples'],
            'collocations' => $card['collocations'],
            'is_important' => $card['is_important'],
            'is_learned' => $card['is_learned'],
        ];
    }

    /**
     * @return list<array{sense_id: int, lemma: string, article: string|null, translation: string|null}>
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

        if ($languageId === null) {
            return [];
        }

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
            ->join('sense_translations', function ($join) use ($languageId): void {
                $join->on('sense_translations.sense_id', '=', 'senses.id')
                    ->where('sense_translations.target_language_id', '=', $languageId)
                    ->where('sense_translations.status', ContentStatus::Published->value);
            })
            ->leftJoin('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
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

            $article = $row->article !== null ? strtolower((string) $row->article) : null;
            if ($article !== null && ! GermanArticle::isValid($article)) {
                $article = null;
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

    private function likePrefix(string $value): string
    {
        return str_replace(
            ['!', '%', '_'],
            ['!!', '!%', '!_'],
            $value,
        ).'%';
    }

    private function normalizeQuery(string $query): string
    {
        $query = trim($query);

        if (mb_strlen($query) > self::MAX_QUERY_LENGTH) {
            return mb_substr($query, 0, self::MAX_QUERY_LENGTH);
        }

        return $query;
    }

    private function assertWithinSuggestRateLimit(): void
    {
        $this->assertWithinRateLimit(
            'meaning-quiz-suggest|'.(auth()->id() ?? request()->ip()),
            self::SUGGEST_RATE_LIMIT,
            self::SUGGEST_RATE_DECAY_SECONDS,
            'search_query',
            'Too many searches. Please wait a moment and try again.',
        );
    }

    private function assertWithinDetailsRateLimit(): void
    {
        $this->assertWithinRateLimit(
            'meaning-quiz-details|'.(auth()->id() ?? request()->ip()),
            self::DETAILS_RATE_LIMIT,
            self::DETAILS_RATE_DECAY_SECONDS,
            'search_query',
            'Too many word lookups. Please wait a moment and try again.',
        );
    }

    private function assertWithinRateLimit(
        string $key,
        int $maxAttempts,
        int $decaySeconds,
        string $field,
        string $message,
    ): void {
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
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
