<?php

namespace App\Domain\Learning\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Services\DictionaryCache;
use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MeaningQuizService
{
    public const MAX_EXAMPLES_PER_CARD = 5;

    public const MAX_COLLOCATIONS_PER_CARD = 5;

    public const CHOICE_COUNT = 4;

    /** Cap distractor source size so MCQ start stays O(selected) + O(cap), not O(full dictionary). */
    public const MAX_DISTRACTOR_SOURCE = 400;

    public function __construct(
        private readonly MeaningQuizCache $quizCache,
        private readonly DictionaryCache $dictionaryCache,
        private readonly MeaningAnswerChecker $answerChecker,
        private readonly ArticleQuizService $articleQuizService,
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
     * Languages that have at least one published translation for a published German sense.
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
                        ->whereColumn('sense_translations.target_language_id', 'languages.id')
                        ->where('sense_translations.status', ContentStatus::Published->value)
                        ->where('senses.status', ContentStatus::Published->value)
                        ->where('lexemes.status', ContentStatus::Published->value)
                        ->where('source_languages.code', 'de')
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
     *     translation: string|null,
     *     translations: list<string>,
     *     options?: list<string>,
     *     category_codes: list<string>,
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
        MeaningQuizType $quizType,
        ?User $user = null,
    ): array {
        $levelCodes = $this->normalizeLevelCodes($levelCodes);
        $categoryCodes = $this->normalizeCategoryCodes($categoryCodes);

        $this->assertWordCount($wordCount);
        $this->assertLevels($levelCodes);
        $this->assertCategories($categoryCodes);
        $this->assertTranslationLanguage($translationLanguageCode);

        $pool = $this->quizCache->rememberPool(
            $levelCodes,
            $categoryCodes,
            $translationLanguageCode,
            fn (): array => $this->queryPool($levelCodes, $categoryCodes, $translationLanguageCode),
        );

        if (count($pool) < 5) {
            throw ValidationException::withMessages([
                'word_count' => 'Not enough vocabulary matches these filters. Try other levels, categories, or languages.',
            ]);
        }

        $wordCount = min($wordCount, count($pool));
        $selectedIds = $this->articleQuizService->selectFromPool(
            $pool,
            $wordCount,
            $user,
            SenseFlagContext::Meaning,
        );
        $cards = $this->hydrateCards($selectedIds, $translationLanguageCode, $user, withExtras: false);

        if (count($cards) < 5) {
            throw ValidationException::withMessages([
                'word_count' => 'Not enough vocabulary matches these filters. Try other levels, categories, or languages.',
            ]);
        }

        if (count($cards) !== count($selectedIds)) {
            throw ValidationException::withMessages([
                'word_count' => 'Some quiz words became unavailable. Please try again.',
            ]);
        }

        if ($quizType === MeaningQuizType::MultipleChoice) {
            $cards = $this->attachMultipleChoiceOptions($cards, $pool, $translationLanguageCode);
        }

        return $cards;
    }

    /**
     * Strip answer fields so Livewire quiz state cannot leak correct meanings.
     *
     * @param  list<array<string, mixed>>  $cards
     * @return list<array{sense_id: int, lemma: string, options?: list<string>}>
     */
    public function toQuizPromptCards(array $cards, MeaningQuizType $quizType): array
    {
        return array_map(static function (array $card) use ($quizType): array {
            $prompt = [
                'sense_id' => (int) $card['sense_id'],
                'lemma' => (string) $card['lemma'],
            ];

            if ($quizType === MeaningQuizType::MultipleChoice) {
                $prompt['options'] = array_values(array_map(
                    static fn (mixed $option): string => (string) $option,
                    $card['options'] ?? [],
                ));
            }

            return $prompt;
        }, $cards);
    }

    /**
     * @param  list<int>  $senseIdsInOrder
     * @param  array<string|int, string>  $selectedAnswersBySenseId
     * @param  array<int|string, list<string>>  $optionsBySenseId  Locked MCQ options from Livewire cards
     * @return array{
     *     results: list<array{
     *         sense_id: int,
     *         lemma: string,
     *         correct_meaning: string,
     *         selected_meaning: string,
     *         is_correct: bool,
     *         match_score: int,
     *         position: int,
     *         translations: list<string>,
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
        array $selectedAnswersBySenseId,
        string $translationLanguageCode,
        MeaningQuizType $quizType,
        ?User $user = null,
        array $optionsBySenseId = [],
    ): array {
        $senseIdsInOrder = array_map(static fn (mixed $id): int => (int) $id, $senseIdsInOrder);

        if ($senseIdsInOrder === []) {
            throw ValidationException::withMessages([
                'answers' => 'Answer every word before submitting.',
            ]);
        }

        $cards = $this->hydrateCards($senseIdsInOrder, $translationLanguageCode, $user, withExtras: false);
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

            $selected = trim((string) ($selectedAnswersBySenseId[$senseId] ?? $selectedAnswersBySenseId[(string) $senseId] ?? ''));
            $accepted = $card['translations'];

            if ($accepted === []) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz words are missing translations. Please start a new quiz.',
                ]);
            }

            $primary = (string) ($card['translation'] ?? $accepted[0]);

            if ($quizType === MeaningQuizType::MultipleChoice) {
                if ($selected === '') {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Choose one meaning for every word.',
                    ]);
                }

                $options = $optionsBySenseId[$senseId] ?? $optionsBySenseId[(string) $senseId] ?? [];
                if ($options === [] || ! in_array($selected, $options, true)) {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Choose one of the shown meanings for every word.',
                    ]);
                }

                $isCorrect = $selected === $primary;
                $matchScore = $isCorrect ? 100 : 0;
            } else {
                if ($selected === '') {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Type a meaning for every word.',
                    ]);
                }

                $check = $this->answerChecker->check($selected, $accepted, $translationLanguageCode);
                $isCorrect = $check['is_correct'];
                $matchScore = $check['match_score'];
            }

            if ($isCorrect) {
                $correct++;
            }

            $results[] = [
                'sense_id' => $senseId,
                'lemma' => (string) $card['lemma'],
                'correct_meaning' => $primary,
                'selected_meaning' => $selected,
                'is_correct' => $isCorrect,
                'match_score' => $matchScore,
                'position' => $index,
                'translations' => $accepted,
                'examples' => [],
                'collocations' => [],
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
     * Lazy-load examples/collocations for a results-row details panel.
     *
     * @return array{
     *     translations: list<string>,
     *     examples: list<array{sentence: string, translation: string|null}>,
     *     collocations: list<array{phrase: string, translation: string|null}>
     * }|null
     */
    public function senseDetails(int $senseId, string $translationLanguageCode, ?User $user = null): ?array
    {
        $cards = $this->hydrateCards([$senseId], $translationLanguageCode, $user, withExtras: true);

        if ($cards === []) {
            return null;
        }

        $card = $cards[0];

        return [
            'translations' => $card['translations'],
            'examples' => $card['examples'],
            'collocations' => $card['collocations'],
        ];
    }

    /**
     * @param  list<int>  $senseIds
     * @return array<int, array{lemma: string, translation: string, translations: list<string>}>
     */
    public function resolveMeaningsBySenseId(array $senseIds, string $translationLanguageCode): array
    {
        $senseIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $senseIds)));

        if ($senseIds === []) {
            return [];
        }

        $languageId = Language::query()->where('code', $translationLanguageCode)->value('id');
        if ($languageId === null) {
            return [];
        }

        $rows = Sense::query()
            ->select([
                'senses.id as sense_id',
                'lexemes.lemma',
                'sense_translations.translation_text',
                'sense_translations.sort_order',
            ])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('sense_translations', function ($join) use ($languageId): void {
                $join->on('sense_translations.sense_id', '=', 'senses.id')
                    ->where('sense_translations.target_language_id', '=', $languageId)
                    ->where('sense_translations.status', '=', ContentStatus::Published->value);
            })
            ->whereIn('senses.id', $senseIds)
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
            ->whereNull('lexemes.deleted_at')
            ->whereNull('senses.deleted_at')
            ->orderBy('senses.id')
            ->orderBy('sense_translations.sort_order')
            ->toBase()
            ->get();

        $resolved = [];
        foreach ($rows as $row) {
            $senseId = (int) $row->sense_id;
            if (! isset($resolved[$senseId])) {
                $resolved[$senseId] = [
                    'lemma' => (string) $row->lemma,
                    'translation' => (string) $row->translation_text,
                    'translations' => [],
                ];
            }

            $text = (string) $row->translation_text;
            if ($text !== '' && ! in_array($text, $resolved[$senseId]['translations'], true)) {
                $resolved[$senseId]['translations'][] = $text;
            }
        }

        return $resolved;
    }

    /**
     * @param  list<string>  $levelCodes
     * @param  list<string>  $categoryCodes
     * @return list<int>
     */
    public function queryPool(array $levelCodes, array $categoryCodes, string $translationLanguageCode): array
    {
        $levelCodes = $this->normalizeLevelCodes($levelCodes);
        $categoryCodes = $this->normalizeCategoryCodes($categoryCodes);

        $languageId = Language::query()->where('code', $translationLanguageCode)->value('id');
        if ($languageId === null) {
            return [];
        }

        $query = Sense::query()
            ->select(['senses.id'])
            ->join('lexemes', 'lexemes.id', '=', 'senses.lexeme_id')
            ->join('languages', 'languages.id', '=', 'lexemes.language_id')
            ->join('sense_translations', function ($join) use ($languageId): void {
                $join->on('sense_translations.sense_id', '=', 'senses.id')
                    ->where('sense_translations.target_language_id', '=', $languageId)
                    ->where('sense_translations.status', '=', ContentStatus::Published->value);
            })
            ->where('languages.code', 'de')
            ->where('lexemes.status', ContentStatus::Published->value)
            ->where('senses.status', ContentStatus::Published->value)
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
     *     translation: string|null,
     *     translations: list<string>,
     *     category_codes: list<string>,
     *     examples: list<array{sentence: string, translation: string|null}>,
     *     collocations: list<array{phrase: string, translation: string|null}>,
     *     is_important: bool,
     *     is_learned: bool
     * }>
     */
    public function hydrateCards(
        array $senseIds,
        string $translationLanguageCode,
        ?User $user = null,
        bool $withExtras = true,
    ): array {
        $senseIds = array_map(static fn (mixed $id): int => (int) $id, $senseIds);

        if ($senseIds === []) {
            return [];
        }

        $flagsBySense = [];
        if ($user !== null) {
            $flagsBySense = UserSenseFlag::query()
                ->where('user_id', $user->id)
                ->where('context', SenseFlagContext::Meaning)
                ->whereIn('sense_id', $senseIds)
                ->get()
                ->keyBy('sense_id')
                ->all();
        }

        $language = Language::query()->where('code', $translationLanguageCode)->first();
        $languageId = $language?->id;

        $with = [
            'lexeme:id,lemma',
            'lexeme.categories:id,code',
            'translations' => fn ($q) => $q
                ->select(['id', 'sense_id', 'target_language_id', 'translation_text', 'sort_order', 'status'])
                ->when($languageId, fn ($q) => $q->where('target_language_id', $languageId))
                ->where('status', ContentStatus::Published->value)
                ->orderBy('sort_order'),
        ];

        if ($withExtras) {
            $with['examples'] = fn ($q) => $q
                ->select(['id', 'sense_id', 'sentence', 'sort_order', 'status'])
                ->where('status', ContentStatus::Published->value)
                ->orderBy('sort_order')
                ->limit(self::MAX_EXAMPLES_PER_CARD);
            $with['examples.translations'] = fn ($q) => $q
                ->select(['id', 'example_id', 'language_id', 'translation'])
                ->when($languageId, fn ($q) => $q->where('language_id', $languageId));
            $with['collocations'] = fn ($q) => $q
                ->select(['id', 'sense_id', 'phrase', 'sort_order', 'status'])
                ->where('status', ContentStatus::Published->value)
                ->orderBy('sort_order')
                ->limit(self::MAX_COLLOCATIONS_PER_CARD);
            $with['collocations.translations'] = fn ($q) => $q
                ->select(['id', 'collocation_id', 'language_id', 'translation'])
                ->when($languageId, fn ($q) => $q->where('language_id', $languageId));
        }

        $senses = Sense::query()
            ->with($with)
            ->whereIn('id', $senseIds)
            ->get()
            ->keyBy('id');

        $cards = [];
        foreach ($senseIds as $senseId) {
            $sense = $senses->get($senseId);
            if ($sense === null) {
                continue;
            }

            $cacheKeyLanguage = $withExtras
                ? $translationLanguageCode
                : $translationLanguageCode.':slim';

            $card = $this->quizCache->rememberCard($senseId, $cacheKeyLanguage, function () use ($sense, $senseId, $withExtras): array {
                $translations = $sense->translations
                    ->pluck('translation_text')
                    ->filter()
                    ->map(static fn (mixed $text): string => (string) $text)
                    ->unique()
                    ->values()
                    ->all();

                $examples = [];
                $collocations = [];
                if ($withExtras) {
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
                }

                $categoryCodes = $sense->lexeme?->categories
                    ->pluck('code')
                    ->map(static fn (mixed $code): string => strtolower((string) $code))
                    ->values()
                    ->all() ?? [];

                return [
                    'sense_id' => $senseId,
                    'lemma' => (string) $sense->lexeme?->lemma,
                    'translation' => $translations[0] ?? null,
                    'translations' => $translations,
                    'category_codes' => $categoryCodes,
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

    /**
     * Lightweight distractor index: primary translation + category codes only.
     *
     * @param  list<int>  $senseIds
     * @return list<array{sense_id: int, translation: string, translations: list<string>, category_codes: list<string>}>
     */
    public function hydrateDistractorIndex(array $senseIds, string $translationLanguageCode): array
    {
        $senseIds = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $senseIds)));
        if ($senseIds === []) {
            return [];
        }

        if (count($senseIds) > self::MAX_DISTRACTOR_SOURCE) {
            shuffle($senseIds);
            $senseIds = array_slice($senseIds, 0, self::MAX_DISTRACTOR_SOURCE);
        }

        $languageId = Language::query()->where('code', $translationLanguageCode)->value('id');
        if ($languageId === null) {
            return [];
        }

        $senses = Sense::query()
            ->with([
                'lexeme:id',
                'lexeme.categories:id,code',
                'translations' => fn ($q) => $q
                    ->select(['id', 'sense_id', 'target_language_id', 'translation_text', 'sort_order', 'status'])
                    ->where('target_language_id', $languageId)
                    ->where('status', ContentStatus::Published->value)
                    ->orderBy('sort_order'),
            ])
            ->whereIn('id', $senseIds)
            ->get(['id', 'lexeme_id']);

        $index = [];
        foreach ($senses as $sense) {
            $translations = $sense->translations
                ->pluck('translation_text')
                ->filter()
                ->map(static fn (mixed $text): string => (string) $text)
                ->unique()
                ->values()
                ->all();

            if ($translations === []) {
                continue;
            }

            $index[] = [
                'sense_id' => (int) $sense->id,
                'translation' => $translations[0],
                'translations' => $translations,
                'category_codes' => $sense->lexeme?->categories
                    ->pluck('code')
                    ->map(static fn (mixed $code): string => strtolower((string) $code))
                    ->values()
                    ->all() ?? [],
            ];
        }

        return $index;
    }

    /**
     * @param  list<array<string, mixed>>  $cards
     * @param  list<int>  $pool
     * @return list<array<string, mixed>>
     */
    private function attachMultipleChoiceOptions(array $cards, array $pool, string $translationLanguageCode): array
    {
        $distractorSourceIds = array_values(array_unique([
            ...array_map(static fn (array $card): int => (int) $card['sense_id'], $cards),
            ...array_map(static fn (mixed $id): int => (int) $id, $pool),
        ]));

        $sources = $this->hydrateDistractorIndex($distractorSourceIds, $translationLanguageCode);

        $byCategory = [];
        foreach ($sources as $source) {
            $text = $source['translation'];
            foreach ($source['category_codes'] as $code) {
                $byCategory[$code][] = [
                    'sense_id' => $source['sense_id'],
                    'translation' => $text,
                ];
            }
        }

        $globalPool = array_map(static fn (array $source): array => [
            'sense_id' => $source['sense_id'],
            'translation' => $source['translation'],
        ], $sources);

        foreach ($cards as &$card) {
            $correct = (string) ($card['translation'] ?? '');
            if ($correct === '') {
                throw ValidationException::withMessages([
                    'word_count' => 'Some quiz words became unavailable. Please try again.',
                ]);
            }

            $senseId = (int) $card['sense_id'];
            $categoryCodes = $card['category_codes'] ?? [];
            $blocked = [];
            foreach ($card['translations'] ?? [$correct] as $gloss) {
                $blocked[mb_strtolower((string) $gloss)] = true;
            }

            $sameCategory = [];
            foreach ($categoryCodes as $code) {
                foreach ($byCategory[$code] ?? [] as $candidate) {
                    if ($candidate['sense_id'] === $senseId) {
                        continue;
                    }
                    if (isset($blocked[mb_strtolower($candidate['translation'])])) {
                        continue;
                    }
                    $sameCategory[$candidate['translation']] = $candidate['translation'];
                }
            }

            $distractors = array_values($sameCategory);
            shuffle($distractors);
            $distractors = array_slice($distractors, 0, self::CHOICE_COUNT - 1);

            if (count($distractors) < self::CHOICE_COUNT - 1) {
                $needed = (self::CHOICE_COUNT - 1) - count($distractors);
                $fallbacks = [];
                foreach ($globalPool as $candidate) {
                    if ($candidate['sense_id'] === $senseId) {
                        continue;
                    }
                    if (isset($blocked[mb_strtolower($candidate['translation'])])) {
                        continue;
                    }
                    if (in_array($candidate['translation'], $distractors, true)) {
                        continue;
                    }
                    $fallbacks[$candidate['translation']] = $candidate['translation'];
                }
                $fallbackList = array_values($fallbacks);
                shuffle($fallbackList);
                $distractors = [...$distractors, ...array_slice($fallbackList, 0, $needed)];
            }

            if (count($distractors) < self::CHOICE_COUNT - 1) {
                throw ValidationException::withMessages([
                    'word_count' => 'Not enough distinct meanings for a multiple-choice quiz. Try broader filters.',
                ]);
            }

            $options = [$correct, ...array_slice($distractors, 0, self::CHOICE_COUNT - 1)];
            shuffle($options);
            $card['options'] = $options;
        }
        unset($card);

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
