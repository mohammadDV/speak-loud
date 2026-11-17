<?php

use App\Domain\Learning\Actions\SaveMeaningQuizResult;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Services\LearningInsightsService;
use App\Domain\Learning\Services\MeaningQuizService;
use App\Domain\Learning\Services\MeaningQuizWordSearchService;
use App\Support\Seo;
use App\Support\SpeechLocale;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{state, mount, title, updated};

state([
    'level_codes' => [],
    'category_codes' => [],
    'word_count' => 10,
    'translation_language' => 'en',
    'quiz_type' => MeaningQuizType::MultipleChoice->value,
    'levels' => [],
    'categories' => [],
    'languages' => [],
    'answers' => [],
    'results' => [],
    'correct_count' => 0,
    'incorrect_count' => 0,
    'percentage' => 0,
    'saved_session_id' => null,
    'details_sense_id' => null,
    'search_detail' => null,
    'search_query' => '',
    'search_suggestions' => [],
    'show_search_suggestions' => false,
    'skip_search_refresh' => false,
    'error_message' => null,
    'show_error_modal' => false,
    'scroll_to_id' => null,
    'show_feature_guide' => false,
    'focus' => null,
    'fill_notice' => null,
]);

state([
    'phase' => 'setup',
    'cards' => [],
    'quiz_sense_ids' => [],
    'session_quiz_type' => null,
    'session_translation_language' => null,
])->locked();

mount(function (MeaningQuizService $quiz) {
    Seo::share([
        'seoTitle' => 'Vocabulary meaning quiz',
        'seoDescription' => 'Practice German vocabulary meanings. Choose multiple-choice or type the meaning with fuzzy checking.',
        'seoUrl' => route('learn.vocabulary-meaning'),
    ]);

    $this->levels = $quiz->levels()->map(fn ($level) => [
        'code' => $level->code,
        'name' => $level->name,
    ])->all();

    $this->categories = $quiz->categories()->map(fn ($category) => [
        'code' => $category->code,
        'display_name' => $category->display_name,
    ])->all();

    $this->languages = $quiz->translationLanguages()->map(fn (array $language) => [
        'code' => $language['code'],
        'name_en' => $language['name_en'],
        'name_native' => $language['name_native'],
    ])->all();

    $availableCodes = collect($this->languages)->pluck('code');

    if (auth()->check()) {
        $learning = auth()->user()->languages()
            ->where('type', 'learning')
            ->with('language')
            ->first();

        $code = $learning?->language?->code;
        if ($code && $availableCodes->contains($code)) {
            $this->translation_language = $code;
        }
    }

    if (! $availableCodes->contains($this->translation_language)) {
        $this->translation_language = (string) ($availableCodes->first() ?? '');
    }

    $count = request()->integer('count');
    if ($count >= 5 && $count <= 50) {
        $this->word_count = $count;
    }

    $focus = request()->query('focus');
    $this->focus = in_array($focus, ['important', 'difficult'], true) ? $focus : null;
});

title(fn () => Seo::pageTitle('Vocabulary meaning quiz'));

updated([
    'search_query' => function () {
        if ($this->skip_search_refresh) {
            $this->skip_search_refresh = false;

            return;
        }

        $this->refreshWordSuggestions();
    },
    'translation_language' => function () {
        if (trim((string) $this->search_query) !== '') {
            $this->refreshWordSuggestions();
        }
    },
]);

$refreshWordSuggestions = function () {
    if ($this->phase === 'quiz') {
        $this->search_suggestions = [];
        $this->show_search_suggestions = false;

        return;
    }

    $query = trim((string) $this->search_query);
    if (mb_strlen($query) > MeaningQuizWordSearchService::MAX_QUERY_LENGTH) {
        $query = mb_substr($query, 0, MeaningQuizWordSearchService::MAX_QUERY_LENGTH);
        $this->skip_search_refresh = true;
        $this->search_query = $query;
    }

    if (mb_strlen($query) < MeaningQuizWordSearchService::MIN_QUERY_LENGTH) {
        $this->search_suggestions = [];
        $this->show_search_suggestions = false;

        return;
    }

    if ($this->translation_language === '') {
        $this->search_suggestions = [];
        $this->show_search_suggestions = false;

        return;
    }

    try {
        $this->search_suggestions = app(MeaningQuizWordSearchService::class)->suggest(
            $query,
            $this->translation_language,
        );
        $this->show_search_suggestions = true;
    } catch (ValidationException $e) {
        $this->search_suggestions = [];
        $this->show_search_suggestions = false;
        $message = collect($e->errors())->flatten()->first()
            ?? 'Too many searches. Please wait a moment and try again.';
        $this->showError($message, 'word-search');
    }
};

$selectSearchSuggestion = function (int $senseId) {
    if ($this->phase === 'quiz') {
        return;
    }

    try {
        $detail = app(MeaningQuizWordSearchService::class)->details(
            $senseId,
            $this->translation_language,
            auth()->user(),
        );
    } catch (ValidationException $e) {
        $message = collect($e->errors())->flatten()->first()
            ?? 'Too many word lookups. Please wait a moment and try again.';
        $this->showError($message, 'word-search');

        return;
    }

    if ($detail === null) {
        $this->showError('That word is not available right now.');

        return;
    }

    $this->search_detail = $detail;
    $this->details_sense_id = $senseId;
    $this->search_suggestions = [];
    $this->show_search_suggestions = false;
    $this->skip_search_refresh = true;
    $this->search_query = (string) $detail['lemma'];
};

$clearSearch = function () {
    $this->search_query = '';
    $this->search_suggestions = [];
    $this->show_search_suggestions = false;
};

$showError = function (string $message, ?string $scrollToId = null) {
    $this->error_message = $message;
    $this->show_error_modal = true;
    $this->scroll_to_id = $scrollToId;
};

$scrollToField = function (?string $elementId = null) {
    $elementId = $elementId ?: $this->scroll_to_id;
    if (! filled($elementId)) {
        return;
    }

    $safeId = json_encode((string) $elementId);
    $this->js(<<<JS
        const id = {$safeId};
        const el = document.getElementById(id);
        if (! el) {
            return;
        }

        el.scrollIntoView({ behavior: 'smooth', block: 'center' });

        const focusable = el.matches('input, select, textarea, button')
            ? el
            : el.querySelector('input:not([type="hidden"]), select, textarea, button');

        if (focusable) {
            focusable.focus({ preventScroll: true });
        }
    JS);
};

$dismissError = function () {
    $target = $this->scroll_to_id;
    $this->show_error_modal = false;
    $this->error_message = null;
    $this->scroll_to_id = null;
    $this->scrollToField($target);
};

$resolvedQuizType = function (): MeaningQuizType {
    return MeaningQuizType::tryFrom((string) $this->quiz_type) ?? MeaningQuizType::MultipleChoice;
};

$loadCards = function (MeaningQuizService $quiz, LearningInsightsService $insights) {
    $this->error_message = null;
    $this->show_error_modal = false;
    $this->fill_notice = null;

    try {
        $this->validate([
            'word_count' => ['required', 'integer', 'min:5', 'max:50'],
            'level_codes' => ['array'],
            'level_codes.*' => ['string', 'max:8'],
            'category_codes' => ['array'],
            'category_codes.*' => ['string', 'max:64'],
            'translation_language' => ['required', 'string', 'max:16'],
            'quiz_type' => ['required', 'in:multiple_choice,write_in'],
        ], [
            'word_count.required' => 'Choose between 5 and 50 words.',
            'word_count.min' => 'Choose between 5 and 50 words.',
            'word_count.max' => 'Choose between 5 and 50 words.',
            'translation_language.required' => 'Choose a translation language.',
            'quiz_type.required' => 'Choose a quiz type.',
            'quiz_type.in' => 'Choose a quiz type.',
        ]);

        $focusedIds = [];
        $user = auth()->user();
        if ($user !== null && $this->focus === 'important') {
            $focusedIds = $insights->importantUnlearnedSenseIds($user, SenseFlagContext::Meaning);
        } elseif ($user !== null && $this->focus === 'difficult') {
            $focusedIds = $insights->difficultSenseIds($user, 'meaning_quiz');
        }

        $this->cards = $quiz->buildQuiz(
            (int) $this->word_count,
            array_values($this->level_codes),
            array_values($this->category_codes),
            $this->translation_language,
            $this->resolvedQuizType(),
            $user,
            $this->focus,
            $focusedIds,
        );
        $this->fill_notice = $quiz->lastFillNotice();
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Something went wrong. Please check your choices and try again.';
        $this->cards = [];

        $firstKey = array_key_first($e->errors());
        $scrollTo = match (true) {
            $firstKey === 'word_count' => 'quiz-field-word-count',
            $firstKey === 'translation_language' => 'quiz-field-translation',
            $firstKey === 'quiz_type' => 'quiz-field-type',
            str_starts_with((string) $firstKey, 'level_codes') => 'quiz-field-levels',
            str_starts_with((string) $firstKey, 'category_codes') => 'quiz-field-categories',
            default => 'quiz-setup',
        };

        $this->showError($message, $scrollTo);

        return false;
    }

    $this->quiz_sense_ids = [];
    $this->answers = [];
    foreach ($this->cards as $card) {
        $this->answers[(string) $card['sense_id']] = '';
    }

    $this->results = [];
    $this->saved_session_id = null;
    $this->details_sense_id = null;

    return true;
};

$startQuiz = function (MeaningQuizService $quiz, LearningInsightsService $insights) {
    $startThrottleKey = 'meaning-quiz-start|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($startThrottleKey, 20)) {
        $seconds = RateLimiter::availableIn($startThrottleKey);
        $this->showError('Too many quiz starts. Please try again in '.$seconds.' seconds.');

        return;
    }

    if ($this->phase !== 'setup' || $this->cards === []) {
        if (! $this->loadCards($quiz, $insights)) {
            return;
        }
    }

    RateLimiter::hit($startThrottleKey, 60);

    $quizType = $this->resolvedQuizType();
    $this->session_quiz_type = $quizType->value;
    $this->session_translation_language = $this->translation_language;
    $this->cards = $quiz->toQuizPromptCards($this->cards, $quizType);
    $this->quiz_sense_ids = array_map(
        static fn (array $card): int => (int) $card['sense_id'],
        $this->cards,
    );

    $this->answers = [];
    foreach ($this->quiz_sense_ids as $senseId) {
        $this->answers[(string) $senseId] = '';
    }

    $this->phase = 'quiz';
    $this->details_sense_id = null;
    $this->search_detail = null;
    $this->search_suggestions = [];
    $this->show_search_suggestions = false;
    $this->skip_search_refresh = true;
    $this->search_query = '';
};

$backToSetup = function () {
    $this->phase = 'setup';
    $this->cards = [];
    $this->quiz_sense_ids = [];
    $this->session_quiz_type = null;
    $this->session_translation_language = null;
    $this->answers = [];
    $this->results = [];
    $this->error_message = null;
    $this->show_error_modal = false;
    $this->scroll_to_id = null;
    $this->details_sense_id = null;
    $this->search_detail = null;
};

$submitQuiz = function (SaveMeaningQuizResult $save, MeaningQuizService $quiz) {
    if ($this->phase !== 'quiz') {
        $this->showError('Start the quiz before submitting answers.');

        return;
    }

    $senseIds = array_values(array_map(
        static fn (mixed $id): int => (int) $id,
        $this->quiz_sense_ids,
    ));

    if ($senseIds === []) {
        $this->showError('This quiz session is no longer valid. Please start again.');
        $this->phase = 'setup';
        $this->cards = [];
        $this->quiz_sense_ids = [];
        $this->session_quiz_type = null;
        $this->session_translation_language = null;

        return;
    }

    $throttleKey = 'meaning-quiz-submit|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->showError('Too many quiz submissions. Please try again in '.$seconds.' seconds.');

        return;
    }

    $quizType = MeaningQuizType::tryFrom((string) $this->session_quiz_type)
        ?? $this->resolvedQuizType();
    $translationLanguage = is_string($this->session_translation_language) && $this->session_translation_language !== ''
        ? $this->session_translation_language
        : $this->translation_language;

    $optionsBySenseId = [];
    foreach ($this->cards as $card) {
        $optionsBySenseId[(int) $card['sense_id']] = array_values(array_map(
            static fn (mixed $option): string => (string) $option,
            $card['options'] ?? [],
        ));
    }

    try {
        $this->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'max:255'],
        ], [
            'answers.*.required' => $quizType === MeaningQuizType::MultipleChoice
                ? 'Choose one meaning for every word.'
                : 'Type a meaning for every word.',
        ]);

        if ($quizType === MeaningQuizType::MultipleChoice) {
            foreach ($senseIds as $senseId) {
                $selected = trim((string) ($this->answers[(string) $senseId] ?? ''));
                $options = $optionsBySenseId[$senseId] ?? [];
                if ($options === [] || ! in_array($selected, $options, true)) {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Choose one of the shown meanings for every word.',
                    ]);
                }
            }
        }
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Answer every word before submitting.';

        $scrollTo = null;
        foreach ($senseIds as $senseId) {
            $senseKey = (string) $senseId;
            $selected = trim((string) ($this->answers[$senseKey] ?? ''));
            $options = $optionsBySenseId[$senseId] ?? [];
            if ($selected === '' || ($quizType === MeaningQuizType::MultipleChoice && ! in_array($selected, $options, true))) {
                $scrollTo = 'quiz-word-'.$senseKey;
                break;
            }
        }

        $this->showError($message, $scrollTo);

        return;
    }

    $answersForGrading = [];
    foreach ($senseIds as $senseId) {
        $answersForGrading[(string) $senseId] = (string) ($this->answers[(string) $senseId] ?? '');
    }

    try {
        $graded = $quiz->gradeAnswers(
            $senseIds,
            $answersForGrading,
            $translationLanguage,
            $quizType,
            auth()->user(),
            $optionsBySenseId,
        );
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Something went wrong grading this quiz. Please try again.';
        $this->showError($message);

        return;
    }

    RateLimiter::hit($throttleKey, 60);

    $this->results = $graded['results'];
    $this->correct_count = $graded['correct_count'];
    $this->incorrect_count = $graded['incorrect_count'];
    $this->percentage = $graded['percentage'];
    $this->cards = [];
    $this->quiz_sense_ids = [];
    $this->phase = 'results';

    if (auth()->check()) {
        $session = $save->execute(auth()->user(), [
            'level_codes' => array_values($this->level_codes),
            'category_codes' => array_values($this->category_codes),
            'word_count' => count($graded['results']),
            'translation_language_code' => $translationLanguage,
            'quiz_type' => $quizType->value,
            'options_by_sense_id' => $optionsBySenseId,
            'answers' => array_map(fn (array $row): array => [
                'sense_id' => $row['sense_id'],
                'selected_meaning' => $row['selected_meaning'],
                'position' => $row['position'],
            ], $graded['results']),
        ]);

        $this->saved_session_id = $session->id;
    }
};

$toggleFlag = function (int $senseId, string $flag, ToggleUserSenseFlag $toggle) {
    if (! auth()->check()) {
        session([
            'pending_learn_return' => route('learn.vocabulary-meaning'),
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    try {
        $record = $toggle->execute(auth()->user(), $senseId, $flag, SenseFlagContext::Meaning);
    } catch (\InvalidArgumentException $e) {
        $this->showError($e->getMessage());

        return;
    }

    $this->results = collect($this->results)->map(function (array $row) use ($senseId, $record) {
        if ((int) $row['sense_id'] !== $senseId) {
            return $row;
        }

        $row['is_important'] = (bool) $record->is_important;
        $row['is_learned'] = (bool) $record->is_learned;

        return $row;
    })->all();
};

$openDetails = function (int $senseId, MeaningQuizService $quiz) {
    if ($this->details_sense_id === $senseId && $this->search_detail === null) {
        $this->details_sense_id = null;

        return;
    }

    $this->search_detail = null;

    $language = is_string($this->session_translation_language) && $this->session_translation_language !== ''
        ? $this->session_translation_language
        : $this->translation_language;

    $detail = $quiz->senseDetails($senseId, $language, auth()->user());
    if ($detail !== null) {
        $this->results = collect($this->results)->map(function (array $row) use ($senseId, $detail) {
            if ((int) $row['sense_id'] !== $senseId) {
                return $row;
            }

            $row['translations'] = $detail['translations'];
            $row['examples'] = $detail['examples'];
            $row['collocations'] = $detail['collocations'];

            return $row;
        })->all();
    }

    $this->details_sense_id = $senseId;
};

$closeDetails = function () {
    $this->details_sense_id = null;
    $this->search_detail = null;
};

$toggleFeatureGuide = function () {
    $this->show_feature_guide = ! $this->show_feature_guide;
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10">
    <div class="mb-6 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <a
                href="{{ route('learn.index') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 rounded-xl bg-[#FFF0E0] px-3.5 py-2 text-sm font-bold text-[#3D2B1F] hover:bg-[#FFE4C8] transition"
            >
                <span aria-hidden="true">←</span>
                Back to Learn
            </a>
            @auth
                <a
                    href="{{ route('learn.vocabulary-meaning.history') }}"
                    wire:navigate
                    class="inline-flex items-center gap-2 rounded-xl bg-[#FF8C42] px-3.5 py-2 text-sm font-bold text-white hover:bg-[#f07d33] shadow-sm transition"
                >
                    View progress
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>

        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">German practice</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Vocabulary meaning</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">
                See a German word and choose or type its meaning in your language.
            </p>
            <button
                type="button"
                wire:click="toggleFeatureGuide"
                class="mt-2 text-sm font-semibold text-[#FF8C42] hover:underline"
                aria-expanded="{{ $show_feature_guide ? 'true' : 'false' }}"
            >
                {{ $show_feature_guide ? 'Show less' : 'Show more' }}
            </button>

            @if ($show_feature_guide)
                <div class="mt-3 rounded-2xl bg-[#FFF0E0] px-4 py-3 text-sm text-[#3D2B1F]/75 space-y-2">
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Look up a word:</span>
                        type at least two letters to search published German vocabulary and open details for meanings, examples, and collocations.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Set up a quiz:</span>
                        pick CEFR levels, categories, how many words (5–50), the language for meanings, and the quiz type.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Choose one of four:</span>
                        pick the correct meaning from four options. Distractors come from the same category when possible.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Type the meaning:</span>
                        write the meaning yourself. Answers are checked with fuzzy matching after normalization (typos and small differences can still count as correct).
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">With an account:</span>
                        results are saved to History. Mark words as Important or Learned for meaning practice only — separate from der/die/das lists. Important words you have not learned yet are prioritized in later meaning quizzes.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Your progress:</span>
                        open View progress for History / Important / Learned to review past meaning quizzes and manage marked words.
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if ($phase !== 'quiz')
    <div class="mb-6" x-data="{ open: @entangle('show_search_suggestions') }" @click.outside="open = false">
        <label for="meaning-word-search" class="block text-sm font-medium text-[#3D2B1F] mb-1">Look up a word</label>
        <p class="text-xs text-[#3D2B1F]/45 mb-2">Search any German word to see its meaning.</p>
        <div class="relative">
            <div class="flex gap-2">
                <input
                    id="meaning-word-search"
                    type="search"
                    wire:model.live.debounce.300ms="search_query"
                    autocomplete="off"
                    placeholder="Type at least 2 letters…"
                    class="w-full rounded-xl border border-[#3D2B1F]/10 bg-white px-4 py-3 text-sm text-[#3D2B1F] placeholder:text-[#3D2B1F]/35 focus:border-[#FF8C42] focus:outline-none focus:ring-2 focus:ring-[#FF8C42]/30"
                    @focus="open = {{ count($search_suggestions) > 0 ? 'true' : 'false' }}"
                >
                @if ($search_query !== '')
                    <button
                        type="button"
                        wire:click="clearSearch"
                        class="shrink-0 rounded-xl bg-[#FFF0E0] px-3 text-sm font-semibold text-[#3D2B1F]/70 hover:text-[#3D2B1F]"
                    >
                        Clear
                    </button>
                @endif
            </div>

            <div wire:loading wire:target="search_query" class="absolute right-3 top-3 text-xs text-[#3D2B1F]/40">
                Searching…
            </div>

            <div
                x-show="open"
                x-cloak
                class="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-[#3D2B1F]/10 bg-white shadow-lg"
            >
                @if ($search_suggestions === [] && mb_strlen(trim($search_query)) >= 2)
                    <p class="px-4 py-3 text-sm text-[#3D2B1F]/55">No matching German words found.</p>
                @elseif ($search_suggestions !== [])
                    <ul class="max-h-72 overflow-y-auto py-1" role="listbox">
                        @foreach ($search_suggestions as $suggestion)
                            <li class="flex items-stretch gap-1 px-2 py-1 hover:bg-[#FFF0E0]">
                                <button
                                    type="button"
                                    wire:click="selectSearchSuggestion({{ $suggestion['sense_id'] }})"
                                    class="min-w-0 flex-1 px-2 py-1.5 text-left"
                                    role="option"
                                >
                                    <span class="font-semibold text-[#3D2B1F]">
                                        @if (! empty($suggestion['article']))
                                            <span class="text-[#FF8C42]">{{ $suggestion['article'] }}</span>
                                        @endif
                                        {{ $suggestion['lemma'] }}
                                    </span>
                                    @if (! empty($suggestion['translation']))
                                        <span class="mt-0.5 block truncate text-xs text-[#3D2B1F]/55">{{ $suggestion['translation'] }}</span>
                                    @endif
                                    <span class="mt-1 block text-xs font-semibold text-[#FF8C42]">Details</span>
                                </button>
                                <div class="flex shrink-0 items-center gap-0.5 pr-1">
                                    <x-speak-word
                                        :text="trim(($suggestion['article'] ?? '').' '.$suggestion['lemma'])"
                                        :lang="SpeechLocale::german()"
                                    />
                                    @if (! empty($suggestion['translation']))
                                        <x-speak-word
                                            :text="$suggestion['translation']"
                                            :lang="SpeechLocale::for($translation_language)"
                                            label="Hear translation"
                                        />
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if ($phase === 'setup')
        <div id="quiz-setup" class="bg-[#FFF0E0] rounded-2xl p-4 md:p-6 space-y-4">
            <div class="space-y-4">
                <div id="quiz-field-type">
                    <flux:label>Quiz type</flux:label>
                    <p class="text-xs text-[#3D2B1F]/45 mt-0.5 mb-2">How you will answer each word.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ([
                            MeaningQuizType::MultipleChoice->value => MeaningQuizType::MultipleChoice->label(),
                            MeaningQuizType::WriteIn->value => MeaningQuizType::WriteIn->label(),
                        ] as $typeValue => $typeLabel)
                            <label class="cursor-pointer rounded-xl border-2 px-3 py-3 text-sm font-semibold transition
                                border-transparent bg-white/70 text-[#3D2B1F]/80
                                has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/20 has-[:checked]:text-[#3D2B1F]">
                                <input type="radio" class="sr-only" wire:model="quiz_type" value="{{ $typeValue }}">
                                {{ $typeLabel }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="quiz-field-levels">
                    <flux:label>Levels</flux:label>
                    <p class="text-xs text-[#3D2B1F]/45 mt-0.5 mb-2">Leave empty for all levels. Tap to select more than one.</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($levels as $level)
                            <label class="cursor-pointer rounded-xl border-2 px-3 py-2 text-sm font-semibold transition
                                border-transparent bg-white/70 text-[#3D2B1F]/80
                                has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/20 has-[:checked]:text-[#3D2B1F]">
                                <input type="checkbox" class="sr-only" wire:model="level_codes" value="{{ $level['code'] }}">
                                {{ $level['code'] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="quiz-field-categories">
                    <flux:label>Categories</flux:label>
                    <p class="text-xs text-[#3D2B1F]/45 mt-0.5 mb-2">Leave empty for all categories. Tap to select more than one.</p>
                    <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto">
                        @foreach ($categories as $category)
                            <label class="cursor-pointer rounded-xl border-2 px-3 py-2 text-sm font-semibold transition
                                border-transparent bg-white/70 text-[#3D2B1F]/80
                                has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/20 has-[:checked]:text-[#3D2B1F]">
                                <input type="checkbox" class="sr-only" wire:model="category_codes" value="{{ $category['code'] }}">
                                {{ $category['display_name'] }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div id="quiz-field-word-count">
                        <flux:label>Number of words (5–50)</flux:label>
                        <flux:input type="number" wire:model="word_count" min="5" max="50" class="mt-1" />
                    </div>
                    <div id="quiz-field-translation">
                        <flux:label>Meaning language</flux:label>
                        @if ($languages === [])
                            <p class="mt-2 text-sm text-[#3D2B1F]/55">No translation languages available yet.</p>
                        @else
                            <flux:select wire:model="translation_language" class="mt-1">
                                @foreach ($languages as $language)
                                    <option value="{{ $language['code'] }}">{{ $language['name_en'] }} ({{ $language['name_native'] }})</option>
                                @endforeach
                            </flux:select>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                <flux:button wire:click="startQuiz" variant="primary" class="w-full sm:w-auto justify-center bg-[#FF8C42] hover:bg-[#e67a35]" :disabled="$languages === []">
                    Start quiz
                </flux:button>
            </div>
        </div>

        @guest
            <p class="mt-4 text-sm text-[#3D2B1F]/55">
                You can practice without an account.
                <a href="{{ route('register') }}" wire:navigate class="text-[#FF8C42] font-semibold hover:underline">Sign up</a>
                to save scores and mark important words.
            </p>
        @endguest
    @endif

    @if ($phase === 'quiz')
        <form wire:submit="submitQuiz" class="space-y-4">
            <div class="flex items-center justify-between gap-3 mb-2">
                <h2 class="text-lg font-semibold text-[#3D2B1F]">
                    {{ ($session_quiz_type ?? $quiz_type) === MeaningQuizType::WriteIn->value ? 'Type the meaning' : 'Choose the meaning' }}
                </h2>
                <span class="text-sm text-[#3D2B1F]/50">{{ count($cards) }} words</span>
            </div>

            @foreach ($cards as $index => $card)
                @php $senseKey = (string) $card['sense_id']; @endphp
                <fieldset id="quiz-word-{{ $senseKey }}" class="rounded-2xl bg-[#FFF0E0] p-4">
                    <legend class="sr-only">Word {{ $index + 1 }}</legend>
                    <p class="text-xl font-bold text-[#3D2B1F] mb-3 flex items-center gap-1.5 flex-wrap">
                        <span>{{ $card['lemma'] }}</span>
                        <x-speak-word
                            :text="$card['lemma']"
                            :lang="SpeechLocale::german()"
                        />
                    </p>

                    @if (($session_quiz_type ?? $quiz_type) === MeaningQuizType::MultipleChoice->value)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach (($card['options'] ?? []) as $option)
                                <label
                                    class="cursor-pointer rounded-xl border-2 px-3 py-3 text-sm font-semibold transition
                                        border-transparent bg-white/70 text-[#3D2B1F]/80
                                        has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/20 has-[:checked]:text-[#3D2B1F] has-[:checked]:shadow-sm"
                                >
                                    <input
                                        type="radio"
                                        class="sr-only"
                                        wire:model="answers.{{ $senseKey }}"
                                        name="answer-{{ $senseKey }}"
                                        value="{{ $option }}"
                                    >
                                    {{ $option }}
                                </label>
                            @endforeach
                        </div>
                    @else
                        <flux:input
                            type="text"
                            wire:model="answers.{{ $senseKey }}"
                            placeholder="Type the meaning…"
                            autocomplete="off"
                            class="w-full"
                        />
                    @endif
                </fieldset>
            @endforeach

            <div class="sticky bottom-20 md:bottom-4 z-10 pt-2">
                <flux:button type="submit" variant="primary" class="w-full justify-center bg-[#FF8C42] hover:bg-[#e67a35] py-3 text-base">
                    Submit answers
                </flux:button>
            </div>
        </form>
    @endif

    @if ($phase === 'results')
        <div class="rounded-2xl bg-[#FFF0E0] p-5 mb-6 text-center">
            <p class="text-sm text-[#3D2B1F]/55 mb-1">Your score</p>
            <p class="text-4xl font-bold text-[#3D2B1F]">{{ $percentage }}%</p>
            <p class="text-sm text-[#3D2B1F]/70 mt-2">
                <span class="text-emerald-700 font-semibold">{{ $correct_count }} correct</span>
                ·
                <span class="text-red-700 font-semibold">{{ $incorrect_count }} incorrect</span>
            </p>
            <livewire:learn.components.quiz-gamification-result
                activity-type="meaning_quiz"
                :source-id="$saved_session_id"
            />
            @auth
                @if ($saved_session_id)
                    <p class="text-xs text-[#3D2B1F]/45 mt-2">Saved to your history.</p>
                @endif
            @else
                <div class="mt-4 rounded-xl bg-white/70 px-4 py-3 text-sm text-[#3D2B1F]/70">
                    Sign up to save this result and mark words as important or learned.
                    <div class="mt-2 flex justify-center gap-3">
                        <a href="{{ route('register') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">Create account</a>
                        <a href="{{ route('login') }}" wire:navigate class="font-semibold text-[#3D2B1F]/60 hover:underline">Sign in</a>
                    </div>
                </div>
            @endauth
        </div>

        <div class="space-y-3 mb-6">
            @foreach ($results as $row)
                @php $senseId = (int) $row['sense_id']; @endphp
                <div class="rounded-xl border {{ $row['is_correct'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-red-200 bg-red-50/60' }} px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                                <span>{{ $row['lemma'] }}</span>
                                <x-speak-word
                                    :text="$row['lemma']"
                                    :lang="SpeechLocale::german()"
                                />
                            </p>
                            <p class="text-sm text-[#3D2B1F]/70 mt-1 flex items-center gap-1 flex-wrap">
                                <span>{{ $row['correct_meaning'] }}</span>
                                <x-speak-word
                                    :text="$row['correct_meaning']"
                                    :lang="SpeechLocale::for($session_translation_language ?: $translation_language)"
                                    label="Hear meaning"
                                />
                            </p>
                            <p class="text-xs mt-1 {{ $row['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                                @if ($row['is_correct'])
                                    Correct — you answered “{{ $row['selected_meaning'] }}”
                                @else
                                    Incorrect — you answered “{{ $row['selected_meaning'] }}”
                                @endif
                            </p>
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wide {{ $row['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $row['is_correct'] ? 'True' : 'False' }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="openDetails({{ $senseId }})" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/80 text-[#3D2B1F]">
                            More details
                        </button>
                        @auth
                            <button type="button" wire:click="toggleFlag({{ $senseId }}, 'important')" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg {{ ! empty($row['is_important']) ? 'bg-[#FF8C42] text-white' : 'bg-white/80 text-[#3D2B1F]' }}">
                                {{ ! empty($row['is_important']) ? 'Important ✓' : 'Important' }}
                            </button>
                            <button type="button" wire:click="toggleFlag({{ $senseId }}, 'learned')" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg {{ ! empty($row['is_learned']) ? 'bg-[#3D2B1F] text-white' : 'bg-white/80 text-[#3D2B1F]' }}">
                                {{ ! empty($row['is_learned']) ? 'Learned ✓' : 'Learned' }}
                            </button>
                        @endauth
                    </div>

                    @if ($details_sense_id === $senseId)
                        <div class="mt-3 rounded-xl bg-white/70 px-3 py-3 text-sm text-[#3D2B1F]/75 space-y-2">
                            @if (! empty($row['translations']) && count($row['translations']) > 1)
                                <p>
                                    <span class="font-semibold text-[#3D2B1F]">Meanings:</span>
                                    {{ implode(' · ', $row['translations']) }}
                                </p>
                            @endif
                            @foreach ($row['examples'] ?? [] as $example)
                                <p>
                                    <span class="font-semibold text-[#3D2B1F]">Example:</span>
                                    {{ $example['sentence'] }}
                                    @if (! empty($example['translation']))
                                        <span class="text-[#3D2B1F]/55">— {{ $example['translation'] }}</span>
                                    @endif
                                </p>
                            @endforeach
                            @foreach ($row['collocations'] ?? [] as $collocation)
                                <p>
                                    <span class="font-semibold text-[#3D2B1F]">Collocation:</span>
                                    {{ $collocation['phrase'] }}
                                    @if (! empty($collocation['translation']))
                                        <span class="text-[#3D2B1F]/55">— {{ $collocation['translation'] }}</span>
                                    @endif
                                </p>
                            @endforeach
                            <button type="button" wire:click="closeDetails" class="text-xs font-semibold text-[#FF8C42] hover:underline">
                                Close details
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="backToSetup" variant="primary" class="w-full sm:w-auto justify-center bg-[#FF8C42] hover:bg-[#e67a35]">
                New quiz
            </flux:button>
            @auth
                <a href="{{ route('learn.vocabulary-meaning.history') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white bg-[#FF8C42] hover:bg-[#f07d33] transition">
                    View progress
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>
    @endif

    @if ($show_error_modal && $error_message)
        <div class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4" role="alertdialog" aria-modal="true" aria-labelledby="meaning-quiz-error-title">
            <div class="absolute inset-0 bg-[#3D2B1F]/45" wire:click="dismissError"></div>
            <div class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-lg">
                <div class="mb-4">
                    <h2 id="meaning-quiz-error-title" class="text-lg font-bold text-[#3D2B1F]">Check your answers</h2>
                    <p class="mt-1 text-sm text-[#3D2B1F]/75">{{ $error_message }}</p>
                </div>
                <flux:button type="button" wire:click="dismissError" variant="primary" class="w-full justify-center bg-[#FF8C42] hover:bg-[#e67a35]">
                    OK
                </flux:button>
            </div>
        </div>
    @endif

    @if (is_array($search_detail) && $details_sense_id)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeDetails"></div>
            <div class="relative w-full sm:max-w-lg max-h-[85dvh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                @php
                    $detailArticle = (string) ($search_detail['article'] ?? '');
                    $detailSpeakGerman = $detailArticle !== ''
                        ? trim($detailArticle.' '.$search_detail['lemma'])
                        : (string) $search_detail['lemma'];
                @endphp
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-[#3D2B1F] flex items-center gap-1.5 flex-wrap">
                            <span>
                                @if ($detailArticle !== '')
                                    <span class="text-[#FF8C42]">{{ $detailArticle }}</span>
                                @endif
                                {{ $search_detail['lemma'] }}
                            </span>
                            <x-speak-word
                                :text="$detailSpeakGerman"
                                :lang="SpeechLocale::german()"
                            />
                        </p>
                    </div>
                    <button type="button" wire:click="closeDetails" class="text-[#3D2B1F]/50 text-xl leading-none">&times;</button>
                </div>

                @if (! empty($search_detail['translations']))
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-1">Meanings</h3>
                        <ul class="space-y-1.5">
                            @foreach ($search_detail['translations'] as $translationText)
                                <li class="text-sm text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                                    <span>{{ $translationText }}</span>
                                    <x-speak-word
                                        :text="$translationText"
                                        :lang="SpeechLocale::for($translation_language)"
                                        label="Hear meaning"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($search_detail['examples']))
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-2">Examples</h3>
                        <ul class="space-y-2">
                            @foreach ($search_detail['examples'] as $example)
                                <li class="text-sm text-[#3D2B1F]">
                                    <span class="font-medium">{{ $example['sentence'] }}</span>
                                    @if (! empty($example['translation']))
                                        <span class="block text-[#3D2B1F]/55">{{ $example['translation'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($search_detail['collocations']))
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-2">Collocations</h3>
                        <ul class="space-y-2">
                            @foreach ($search_detail['collocations'] as $collocation)
                                <li class="text-sm text-[#3D2B1F]">
                                    <span class="font-medium">{{ $collocation['phrase'] }}</span>
                                    @if (! empty($collocation['translation']))
                                        <span class="block text-[#3D2B1F]/55">{{ $collocation['translation'] }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
