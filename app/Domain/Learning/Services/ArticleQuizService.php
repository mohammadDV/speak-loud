<?php

namespace App\Domain\Learning\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Services\DictionaryCache;
use App\Domain\Dictionary\Support\EnumValue;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ArticleQuizService
{
    public const MAX_EXAMPLES_PER_CARD = 5;

    public const MAX_COLLOCATIONS_PER_CARD = 5;

    public function __construct(
        private readonly ArticleQuizCache $quizCache,
        private readonly DictionaryCache $dictionaryCache,
    ) {}

    /**
     * @return Collection<int, DifficultyLevel>
     */
    public function levels(): Collection
    {
        return DifficultyLevel::query()
            ->orderBy('sort_order')
            ->get(['id', 'code', 'name', 'sort_order']);
    }

    /**
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        return $this->dictionaryCache->activeCategories();
    }

    /**
     * Languages that have at least one published translation for a published German noun with an article.
     *
     * @return Collection<int, array{id: int, code: string, name_en: string, name_native: string}>
     */
    public function translationLanguages(): Collection
    {
        $rows = $this->quizCache->rememberTranslationLanguages(function (): array {
            return Language::query()
                ->select(['languages.id', 'languages.code', 'languages.name_en', 'languages.name_native'])
                ->where('languages.is_active', true)
                ->where('languages.code', '!=', 'de')
                ->whereExists(function ($query): void {
                    $query->selectRaw('1')
                        ->from('sense_translations')
                        ->join('senses', 'senses.id', '=', 'sense_translations.sense_id')
                        ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
                        ->join('languages as source_languages', 'source_languages.id', '=', 'lexemes.language_id')
                        ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
                        ->whereColumn('sense_translations.target_language_id', 'languages.id')
                        ->where('sense_translations.status', ContentStatus::Published->value)
                        ->where('senses.status', ContentStatus::Published->value)
                        ->where('lexemes.status', ContentStatus::Published->value)
                        ->where('source_languages.code', 'de')
                        ->whereNotNull('german_noun_details.article')
                        ->whereNull('senses.deleted_at')
                        ->whereNull('lexemes.deleted_at');
                })
                ->orderBy('languages.name_en')
                ->get()
                ->map(fn (Language $language): array => [
                    'id' => (int) $language->id,
                    'code' => (string) $language->code,
                    'name_en' => (string) $language->name_en,
                    'name_native' => (string) $language->name_native,
                ])
                ->all();
        });

        return collect($rows)->values();
    }

    /**
     * @param  list<string>  $levelCodes
     * @param  list<string>  $categoryCodes
     * @return list<array{
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
     * }>
     */
    public function buildQuiz(
        int $wordCount,
        array $levelCodes,
        array $categoryCodes,
        string $translationLanguageCode,
        ?User $user = null,
    ): array {
        $levelCodes = $this->normalizeLevelCodes($levelCodes);
        $categoryCodes = $this->normalizeCategoryCodes($categoryCodes);

        $this->assertWordCount($wordCount);
        $this->assertLevels($levelCodes);
        $this->assertCategories($categoryCodes);
        $this->assertTranslationLanguage($translationLanguageCode);

        $pool = $this->quizCache->rememberPool($levelCodes, $categoryCodes, function () use ($levelCodes, $categoryCodes) {
            return $this->queryPool($levelCodes, $categoryCodes);
        });

        if (count($pool) < 5) {
            throw ValidationException::withMessages([
                'word_count' => 'Not enough German nouns match these filters. Try other levels or categories.',
            ]);
        }

        $wordCount = min($wordCount, count($pool));
        $selectedIds = $this->selectFromPool($pool, $wordCount, $user);

        return $this->hydrateCards($selectedIds, $translationLanguageCode, $user);
    }

    /**
     * Strip answer fields so Livewire quiz state cannot leak articles.
     *
     * @param  list<array<string, mixed>>  $cards
     * @return list<array{sense_id: int, lemma: string}>
     */
    public function toQuizPromptCards(array $cards): array
    {
        return array_map(static fn (array $card): array => [
            'sense_id' => (int) $card['sense_id'],
            'lemma' => (string) $card['lemma'],
        ], $cards);
    }

    /**
     * Grade selected answers using articles loaded from the database (never trust the client).
     *
     * @param  list<int>  $senseIdsInOrder
     * @param  array<string|int, string>  $selectedArticlesBySenseId
     * @return array{
     *     results: list<array{
     *         sense_id: int,
     *         lemma: string,
     *         correct_article: string,
     *         selected_article: string,
     *         is_correct: bool,
     *         position: int,
     *         translation: string|null,
     *         translations: list<string>,
     *         gender: string|null,
     *         plural_form: string|null,
     *         examples: list<array{sentence: string, translation: string|null}>,
     *         collocations: list<array{phrase: string, translation: string|null}>,
     *         is_important: bool,
     *         is_learned: bool
     *     }>,
     *     correct_count: int,
     *     incorrect_count: int,
     *     percentage: int
     * }
     */
    public function gradeAnswers(
        array $senseIdsInOrder,
        array $selectedArticlesBySenseId,
        string $translationLanguageCode,
        ?User $user = null,
    ): array {
        $senseIdsInOrder = array_map(static fn (mixed $id): int => (int) $id, $senseIdsInOrder);

        if ($senseIdsInOrder === []) {
            throw ValidationException::withMessages([
                'answers' => 'Pick der, die, or das for every word.',
            ]);
        }

        $cards = $this->hydrateCards($senseIdsInOrder, $translationLanguageCode, $user);
        $cardsById = collect($cards)->keyBy('sense_id');

        $results = [];
        $correct = 0;

        foreach ($senseIdsInOrder as $index => $senseId) {
            $card = $cardsById->get($senseId);
            if ($card === null) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz words are no longer available. Please start a new quiz.',
                ]);
            }

            $selected = strtolower(trim((string) ($selectedArticlesBySenseId[$senseId] ?? $selectedArticlesBySenseId[(string) $senseId] ?? '')));
            if (! GermanArticle::isValid($selected)) {
                throw ValidationException::withMessages([
                    'answers.'.$senseId => 'Pick der, die, or das for every word.',
                ]);
            }

            $article = strtolower((string) $card['article']);
            $isCorrect = $selected === $article;

            if ($isCorrect) {
                $correct++;
            }

            $results[] = [
                'sense_id' => $senseId,
                'lemma' => (string) $card['lemma'],
                'correct_article' => $article,
                'selected_article' => $selected,
                'is_correct' => $isCorrect,
                'position' => $index,
                'translation' => $card['translation'],
                'translations' => $card['translations'],
                'gender' => $card['gender'],
                'plural_form' => $card['plural_form'],
                'examples' => $card['examples'],
                'collocations' => $card['collocations'],
                'is_important' => $card['is_important'],
                'is_learned' => $card['is_learned'],
            ];
        }

        $total = count($results);

        return [
            'results' => $results,
            'correct_count' => $correct,
            'incorrect_count' => $total - $correct,
            'percentage' => (int) round(($correct / $total) * 100),
        ];
    }

    /**
     * @param  list<int>  $senseIds
     * @return array<int, array{article: string, lemma: string}>
     */
    public function resolveArticlesBySenseId(array $senseIds): array
    {
        $senseIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $senseIds)));

        if ($senseIds === []) {
            return [];
        }

        $rows = Sense::query()
            ->select([
                'senses.id as sense_id',
                'lexemes.lemma',
                'german_noun_details.article',
            ])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->whereIn('senses.id', $senseIds)
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNotNull('german_noun_details.article')
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at')
            ->toBase()
            ->get();

        $resolved = [];
        foreach ($rows as $row) {
            $article = strtolower((string) $row->article);
            if (! GermanArticle::isValid($article)) {
                continue;
            }

            $resolved[(int) $row->sense_id] = [
                'article' => $article,
                'lemma' => (string) $row->lemma,
            ];
        }

        return $resolved;
    }

    /**
     * @param  list<int>  $pool
     * @return list<int>
     */
    public function selectFromPool(array $pool, int $wordCount, ?User $user = null): array
    {
        $ids = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $pool)));
        $idSet = array_fill_keys($ids, true);
        $selectedIds = [];

        if ($user !== null) {
            $flags = UserSenseFlag::query()
                ->where('user_id', $user->id)
                ->whereIn('sense_id', $ids)
                ->get(['sense_id', 'is_important', 'is_learned']);

            $importantIds = $flags->where('is_important', true)->where('is_learned', false)->pluck('sense_id')->all();
            $learnedIds = $flags->where('is_learned', true)->pluck('sense_id')->all();

            $importantQuota = (int) max(1, (int) floor($wordCount * 0.4));
            $importantPick = collect($importantIds)->shuffle()->take($importantQuota)->all();
            $selectedIds = array_map(static fn (mixed $id): int => (int) $id, $importantPick);

            $remaining = $wordCount - count($selectedIds);
            if ($remaining > 0) {
                $nonLearned = array_values(array_diff($ids, $learnedIds, $selectedIds));
                shuffle($nonLearned);
                $fill = array_slice($nonLearned, 0, $remaining);
                $selectedIds = [...$selectedIds, ...$fill];
                $remaining = $wordCount - count($selectedIds);
            }

            if ($remaining > 0) {
                $rest = array_values(array_diff($ids, $selectedIds));
                shuffle($rest);
                $selectedIds = [...$selectedIds, ...array_slice($rest, 0, $remaining)];
            }
        } else {
            shuffle($ids);
            $selectedIds = array_slice($ids, 0, $wordCount);
        }

        return array_values(array_filter(
            $selectedIds,
            static fn (int $id): bool => isset($idSet[$id]),
        ));
    }

    /**
     * @param  list<string>  $levelCodes
     * @param  list<string>  $categoryCodes
     * @return list<int>
     */
    public function queryPool(array $levelCodes = [], array $categoryCodes = []): array
    {
        $levelCodes = $this->normalizeLevelCodes($levelCodes);
        $categoryCodes = $this->normalizeCategoryCodes($categoryCodes);

        $query = Sense::query()
            ->select(['senses.id'])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('german_noun_details', 'german_noun_details.sense_id', '=', 'senses.id')
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNotNull('german_noun_details.article')
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at');

        if ($levelCodes !== []) {
            $query->join('sense_levels', 'sense_levels.sense_id', '=', 'senses.id')
                ->join('difficulty_levels', 'difficulty_levels.id', '=', 'sense_levels.difficulty_level_id')
                ->whereIn('difficulty_levels.code', $levelCodes);
        }

        if ($categoryCodes !== []) {
            $query->join('category_lexeme', 'category_lexeme.lexeme_id', '=', 'lexemes.id')
                ->join('categories', 'categories.id', '=', 'category_lexeme.category_id')
                ->whereIn('categories.code', $categoryCodes)
                ->where('categories.is_active', true);
        }

        return $query
            ->distinct()
            ->orderBy('senses.id')
            ->pluck('senses.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  list<int>  $senseIds
     * @return list<array{
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
     * }>
     */
    public function hydrateCards(array $senseIds, string $translationLanguageCode, ?User $user = null): array
    {
        $senseIds = array_map(static fn (mixed $id): int => (int) $id, $senseIds);

        if ($senseIds === []) {
            return [];
        }

        $flagsBySense = [];
        if ($user !== null) {
            $flagsBySense = UserSenseFlag::query()
                ->where('user_id', $user->id)
                ->whereIn('sense_id', $senseIds)
                ->get()
                ->keyBy('sense_id')
                ->all();
        }

        $language = Language::query()->where('code', $translationLanguageCode)->first();
        $languageId = $language?->id;

        $senses = Sense::query()
            ->with([
                'lexeme:id,lemma',
                'germanNounDetail:id,sense_id,article,grammatical_gender,plural_form',
                'translations' => fn ($q) => $q
                    ->select(['id', 'sense_id', 'target_language_id', 'translation_text', 'sort_order', 'status'])
                    ->when($languageId, fn ($q) => $q->where('target_language_id', $languageId))
                    ->where('status', ContentStatus::Published->value)
                    ->orderBy('sort_order'),
                'examples' => fn ($q) => $q
                    ->select(['id', 'sense_id', 'sentence', 'sort_order', 'status'])
                    ->where('status', ContentStatus::Published->value)
                    ->orderBy('sort_order')
                    ->limit(self::MAX_EXAMPLES_PER_CARD),
                'examples.translations' => fn ($q) => $q
                    ->select(['id', 'example_id', 'language_id', 'translation'])
                    ->when($languageId, fn ($q) => $q->where('language_id', $languageId)),
                'collocations' => fn ($q) => $q
                    ->select(['id', 'sense_id', 'phrase', 'sort_order', 'status'])
                    ->where('status', ContentStatus::Published->value)
                    ->orderBy('sort_order')
                    ->limit(self::MAX_COLLOCATIONS_PER_CARD),
                'collocations.translations' => fn ($q) => $q
                    ->select(['id', 'collocation_id', 'language_id', 'translation'])
                    ->when($languageId, fn ($q) => $q->where('language_id', $languageId)),
            ])
            ->whereIn('id', $senseIds)
            ->get()
            ->keyBy('id');

        $cards = [];
        foreach ($senseIds as $senseId) {
            $sense = $senses->get($senseId);
            if ($sense === null) {
                continue;
            }

            $card = $this->quizCache->rememberCard($senseId, $translationLanguageCode, function () use ($sense, $senseId) {
                $translations = $sense->translations
                    ->pluck('translation_text')
                    ->filter()
                    ->values()
                    ->all();

                $examples = $sense->examples
                    ->take(self::MAX_EXAMPLES_PER_CARD)
                    ->map(fn ($example): array => [
                        'sentence' => (string) $example->sentence,
                        'translation' => $example->translations->first()?->translation,
                    ])->values()->all();

                $collocations = $sense->collocations
                    ->take(self::MAX_COLLOCATIONS_PER_CARD)
                    ->map(fn ($collocation): array => [
                        'phrase' => (string) $collocation->phrase,
                        'translation' => $collocation->translations->first()?->translation,
                    ])->values()->all();

                $article = strtolower((string) EnumValue::string($sense->germanNounDetail?->article));

                return [
                    'sense_id' => $senseId,
                    'lemma' => (string) $sense->lexeme?->lemma,
                    'article' => $article,
                    'gender' => EnumValue::string($sense->germanNounDetail?->grammatical_gender),
                    'plural_form' => $sense->germanNounDetail?->plural_form,
                    'translation' => $translations[0] ?? null,
                    'translations' => $translations,
                    'examples' => $examples,
                    'collocations' => $collocations,
                ];
            });

            $flag = $flagsBySense[$senseId] ?? null;
            $card['is_important'] = $flag !== null ? (bool) $flag->is_important : false;
            $card['is_learned'] = $flag !== null ? (bool) $flag->is_learned : false;
            $cards[] = $card;
        }

        return $cards;
    }

    private function assertWordCount(int $wordCount): void
    {
        if ($wordCount < 5 || $wordCount > 50) {
            throw ValidationException::withMessages([
                'word_count' => 'Choose between 5 and 50 words.',
            ]);
        }
    }

    /**
     * @param  list<string>  $levelCodes
     * @return list<string>
     */
    private function normalizeLevelCodes(array $levelCodes): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $code): string => strtoupper(trim((string) $code)),
            $levelCodes,
        ), static fn (string $code): bool => $code !== '')));

        sort($normalized);

        return $normalized;
    }

    /**
     * @param  list<string>  $categoryCodes
     * @return list<string>
     */
    private function normalizeCategoryCodes(array $categoryCodes): array
    {
        $normalized = array_values(array_unique(array_filter(array_map(
            static fn (mixed $code): string => strtolower(trim((string) $code)),
            $categoryCodes,
        ), static fn (string $code): bool => $code !== '')));

        sort($normalized);

        return $normalized;
    }

    /**
     * @param  list<string>  $levelCodes
     */
    private function assertLevels(array $levelCodes): void
    {
        if ($levelCodes === []) {
            return;
        }

        $found = DifficultyLevel::query()
            ->whereIn('code', $levelCodes)
            ->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->all();

        if (count($found) !== count($levelCodes)) {
            throw ValidationException::withMessages([
                'level_codes' => 'One or more CEFR levels are invalid.',
            ]);
        }
    }

    /**
     * @param  list<string>  $categoryCodes
     */
    private function assertCategories(array $categoryCodes): void
    {
        if ($categoryCodes === []) {
            return;
        }

        $found = Category::query()
            ->active()
            ->whereIn('code', $categoryCodes)
            ->pluck('code')
            ->map(fn ($code) => strtolower((string) $code))
            ->all();

        if (count($found) !== count($categoryCodes)) {
            throw ValidationException::withMessages([
                'category_codes' => 'One or more categories are invalid.',
            ]);
        }
    }

    private function assertTranslationLanguage(string $code): void
    {
        $exists = $this->translationLanguages()
            ->contains(fn (array $language): bool => $language['code'] === $code);

        if (! $exists) {
            throw ValidationException::withMessages([
                'translation_language' => 'Choose a language that has translations for these words.',
            ]);
        }
    }
}
