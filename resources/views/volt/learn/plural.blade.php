<?php

use App\Domain\Learning\Actions\SavePluralQuizResult;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Services\PluralQuizService;
use App\Domain\Learning\Services\PluralQuizWordSearchService;
use App\Domain\Learning\Services\LearningInsightsService;
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
    'show_translation_for' => [],
    'error_message' => null,
    'show_error_modal' => false,
    'scroll_to_id' => null,
    'search_query' => '',
    'search_suggestions' => [],
    'show_search_suggestions' => false,
    'skip_search_refresh' => false,
    'show_feature_guide' => false,
    'focus' => null,
    'fill_notice' => null,
]);

// Client cannot tamper with quiz phase, deck, or graded sense IDs.
state([
    'phase' => 'setup',
    'cards' => [],
    'quiz_sense_ids' => [],
])->locked();

mount(function (PluralQuizService $quiz) {
    Seo::share([
        'seoTitle' => 'Noun Plurals quiz',
        'seoDescription' => 'Practice German noun plurals. Filter by CEFR level and category, then type each plural form.',
        'seoUrl' => route('learn.plurals'),
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

title(fn () => Seo::pageTitle('Noun Plurals quiz'));

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
    if (mb_strlen($query) > PluralQuizWordSearchService::MAX_QUERY_LENGTH) {
        $query = mb_substr($query, 0, PluralQuizWordSearchService::MAX_QUERY_LENGTH);
        $this->skip_search_refresh = true;
        $this->search_query = $query;
    }

    if (mb_strlen($query) < PluralQuizWordSearchService::MIN_QUERY_LENGTH) {
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
        $this->search_suggestions = app(PluralQuizWordSearchService::class)->suggest(
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
        $detail = app(PluralQuizWordSearchService::class)->details(
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

$loadCards = function (PluralQuizService $quiz, LearningInsightsService $insights) {
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
        ], [
            'word_count.required' => 'Choose between 5 and 50 words.',
            'word_count.min' => 'Choose between 5 and 50 words.',
            'word_count.max' => 'Choose between 5 and 50 words.',
            'translation_language.required' => 'Choose a translation language.',
        ]);

        $focusedIds = [];
        $user = auth()->user();
        if ($user !== null && $this->focus === 'important') {
            $focusedIds = $insights->importantUnlearnedSenseIds($user, SenseFlagContext::Plural);
        } elseif ($user !== null && $this->focus === 'difficult') {
            $focusedIds = $insights->difficultSenseIds($user, 'plural_quiz');
        }

        $this->cards = $quiz->buildQuiz(
            (int) $this->word_count,
            array_values($this->level_codes),
            array_values($this->category_codes),
            $this->translation_language,
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
    $this->show_translation_for = [];

    return true;
};

$previewPlurals = function (PluralQuizService $quiz, LearningInsightsService $insights) {
    if (! $this->loadCards($quiz, $insights)) {
        return;
    }

    $this->quiz_sense_ids = [];
    $this->phase = 'preview';
};

$startQuiz = function (PluralQuizService $quiz, LearningInsightsService $insights) {
    $startThrottleKey = 'plural-quiz-start|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($startThrottleKey, 20)) {
        $seconds = RateLimiter::availableIn($startThrottleKey);
        $this->showError('Too many quiz starts. Please try again in '.$seconds.' seconds.');

        return;
    }

    if ($this->phase !== 'preview' || $this->cards === []) {
        if (! $this->loadCards($quiz, $insights)) {
            return;
        }
    }

    RateLimiter::hit($startThrottleKey, 60);

    // Drop answer fields from Livewire state before the quiz phase.
    $this->cards = $quiz->toQuizPromptCards($this->cards);
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
    $this->answers = [];
    $this->results = [];
    $this->error_message = null;
    $this->show_error_modal = false;
    $this->scroll_to_id = null;
    $this->details_sense_id = null;
    $this->search_detail = null;
};

$submitQuiz = function (SavePluralQuizResult $save, PluralQuizService $quiz) {
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

        return;
    }

    $throttleKey = 'plural-quiz-submit|'.(auth()->id() ?? request()->ip());
    if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->showError('Too many quiz submissions. Please try again in '.$seconds.' seconds.');

        return;
    }

    // Count every submit attempt (including validation/grading failures) against the limit.
    RateLimiter::hit($throttleKey, 60);

    try {
        $this->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'string', 'min:1', 'max:120'],
        ], [
            'answers.*.required' => 'Type the plural for every word.',
            'answers.*.min' => 'Type the plural for every word.',
        ]);
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Type the plural for every word.';

        $scrollTo = null;
        foreach ($senseIds as $senseId) {
            $senseKey = (string) $senseId;
            $answer = trim((string) ($this->answers[$senseKey] ?? ''));
            if ($answer === '') {
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
            $this->translation_language,
            auth()->user(),
        );
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Something went wrong grading this quiz. Please try again.';
        $this->showError($message);

        return;
    }

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
            'translation_language_code' => $this->translation_language,
            'answers' => array_map(fn (array $row): array => [
                'sense_id' => $row['sense_id'],
                'selected_plural' => $row['selected_plural'],
                'position' => $row['position'],
            ], $graded['results']),
        ]);

        $this->saved_session_id = $session->id;
    }
};

$toggleFlag = function (int $senseId, string $flag, ToggleUserSenseFlag $toggle) {
    if (! auth()->check()) {
        session([
            'pending_learn_return' => route('learn.plurals'),
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    try {
        $record = $toggle->execute(auth()->user(), $senseId, $flag, SenseFlagContext::Plural);
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

    $this->cards = collect($this->cards)->map(function (array $row) use ($senseId, $record) {
        if ((int) $row['sense_id'] !== $senseId) {
            return $row;
        }

        $row['is_important'] = (bool) $record->is_important;
        $row['is_learned'] = (bool) $record->is_learned;

        return $row;
    })->all();
};

$toggleTranslation = function (int $senseId) {
    $key = (string) $senseId;
    $current = $this->show_translation_for;
    if (isset($current[$key])) {
        unset($current[$key]);
    } else {
        $current[$key] = true;
    }
    $this->show_translation_for = $current;
};

$openDetails = function (int $senseId) {
    $this->search_detail = null;
    $this->details_sense_id = $this->details_sense_id === $senseId ? null : $senseId;
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
                    href="{{ route('learn.plurals.history') }}"
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
            <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Noun Plurals</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">
                Practice German noun plurals with filters, a word lookup, and a scored quiz.
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
                        type at least two letters to search published German nouns, open details for the plural, meaning, examples, and collocations.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Set up a quiz:</span>
                        optionally pick one or more CEFR levels and categories, choose how many words (5–50), and pick the language for translations.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Show plurals:</span>
                        preview the selected nouns with their correct plural form before you start.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Start quiz:</span>
                        type the plural for every word, then submit to see your score. Open translations or more details on the results.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Without an account:</span>
                        you can practice freely; your score stays on this page and is not saved.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">With an account:</span>
                        results are saved to History. Mark words as Important or Learned; important words you have not learned yet are prioritized in later quizzes.
                    </p>
                    <p>
                        <span class="font-semibold text-[#3D2B1F]">Your progress:</span>
                        open View progress for History / Important / Learned to review past quizzes and manage marked words.
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if ($phase !== 'quiz')
    <div class="mb-6" x-data="{ open: @entangle('show_search_suggestions') }" @click.outside="open = false">
        <label for="word-search" class="block text-sm font-medium text-[#3D2B1F] mb-1">Look up a word</label>
        <p class="text-xs text-[#3D2B1F]/45 mb-2">Search any German noun to see its plural and meaning.</p>
        <div class="relative">
            <div class="flex gap-2">
                <input
                    id="word-search"
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
                    <p class="px-4 py-3 text-sm text-[#3D2B1F]/55">No matching German nouns found.</p>
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
                                        <span class="text-[#3D2B1F]/55">{{ $suggestion['article'] }}</span>
                                        {{ $suggestion['lemma'] }}
                                        @if (! empty($suggestion['plural_form']))
                                            <span class="text-[#3D2B1F]/40">→</span>
                                            <span class="text-[#FF8C42]">{{ $suggestion['plural_form'] }}</span>
                                        @endif
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
                                    @if (! empty($suggestion['plural_form']))
                                        <x-speak-word
                                            :text="$suggestion['plural_form']"
                                            :lang="SpeechLocale::german()"
                                            label="Hear plural"
                                        />
                                    @endif
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
                    @if ($fill_notice)
                        <p class="mb-3 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $fill_notice }}</p>
                    @endif

                    <div id="quiz-field-word-count">
                        <flux:label>Number of words (5–50)</flux:label>
                        <flux:input type="number" wire:model="word_count" min="5" max="50" class="mt-1" />
                    </div>
                    <div id="quiz-field-translation">
                        <flux:label>Translate to</flux:label>
                        @if ($languages === [])
                            <p class="mt-2 text-sm text-[#3D2B1F]/55">No translation languages available yet for German nouns.</p>
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
                <flux:button wire:click="previewPlurals" variant="ghost" class="w-full sm:w-auto justify-center" :disabled="$languages === []">
                    Show plurals
                </flux:button>
                <flux:button wire:click="startQuiz" variant="primary" class="w-full sm:w-auto justify-center bg-[#FF8C42] hover:bg-[#e67a35]" :disabled="$languages === []">
                    Start quiz
                </flux:button>
            </div>
        </div>

        @guest
            <p class="mt-4 text-sm text-[#3D2B1F]/55">
                You can practice without an account.
                <a href="{{ route('register') }}" wire:navigate class="text-[#FF8C42] font-semibold hover:underline">Sign up</a>
                to save scores, mark important words, and prioritize them next time.
            </p>
        @endguest
    @endif

    @if ($phase === 'preview')
        @if ($fill_notice)
            <p class="mb-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">{{ $fill_notice }}</p>
        @endif

        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-[#3D2B1F]">Preview with plurals</h2>
            <button type="button" wire:click="backToSetup" class="text-sm text-[#3D2B1F]/60 hover:text-[#3D2B1F]">Change filters</button>
        </div>

        <div class="space-y-3 mb-6">
            @foreach ($cards as $card)
                <div class="rounded-xl bg-[#FFF0E0] px-4 py-3 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                            <span>
                                @if (! empty($card['article']))
                                    <span class="text-[#3D2B1F]/55">{{ $card['article'] }}</span>
                                @endif
                                {{ $card['lemma'] }}
                                @if (! empty($card['plural_form']))
                                    <span class="text-[#3D2B1F]/40">→</span>
                                    <span class="text-[#FF8C42]">{{ $card['plural_form'] }}</span>
                                @endif
                            </span>
                            <x-speak-word
                                :text="trim(($card['article'] ?? '').' '.$card['lemma'])"
                                :lang="SpeechLocale::german()"
                            />
                            @if (! empty($card['plural_form']))
                                <x-speak-word
                                    :text="$card['plural_form']"
                                    :lang="SpeechLocale::german()"
                                    label="Hear plural"
                                />
                            @endif
                        </p>
                        @if (! empty($card['translation']))
                            <p class="text-sm text-[#3D2B1F]/55 flex items-center gap-1 flex-wrap mt-0.5">
                                <span>{{ $card['translation'] }}</span>
                                <x-speak-word
                                    :text="$card['translation']"
                                    :lang="SpeechLocale::for($translation_language)"
                                    label="Hear translation"
                                />
                            </p>
                        @endif
                    </div>
                    <button
                        type="button"
                        wire:click="openDetails({{ $card['sense_id'] }})"
                        class="text-xs font-semibold text-[#FF8C42] shrink-0"
                    >
                        More details
                    </button>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3 sticky bottom-20 md:bottom-4 z-10">
            <flux:button wire:click="startQuiz" variant="primary" class="w-full justify-center bg-[#FF8C42] hover:bg-[#e67a35]">
                Start quiz
            </flux:button>
        </div>
    @endif

    @if ($phase === 'quiz')
        <form wire:submit="submitQuiz" class="space-y-4">
            <div class="flex items-center justify-between gap-3 mb-2">
                <h2 class="text-lg font-semibold text-[#3D2B1F]">Type the plural</h2>
                <span class="text-sm text-[#3D2B1F]/50">{{ count($cards) }} words</span>
            </div>

            @foreach ($cards as $index => $card)
                @php $senseKey = (string) $card['sense_id']; @endphp
                <fieldset id="quiz-word-{{ $senseKey }}" class="rounded-2xl bg-[#FFF0E0] p-4">
                    <legend class="sr-only">Word {{ $index + 1 }}</legend>
                    <p class="text-xl font-bold text-[#3D2B1F] mb-3 flex items-center gap-1.5 flex-wrap">
                        @if (! empty($card['article']))
                            <span class="text-[#FF8C42]">{{ $card['article'] }}</span>
                        @endif
                        <span>{{ $card['lemma'] }}</span>
                        <x-speak-word
                            :text="$card['lemma']"
                            :lang="SpeechLocale::german()"
                        />
                    </p>
                    <label class="block text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/55 mb-1" for="plural-answer-{{ $senseKey }}">
                        Plural
                    </label>
                    <input
                        id="plural-answer-{{ $senseKey }}"
                        type="text"
                        autocomplete="off"
                        autocorrect="off"
                        spellcheck="false"
                        wire:model="answers.{{ $senseKey }}"
                        placeholder="e.g. Väter"
                        class="w-full rounded-xl border border-[#3D2B1F]/10 bg-white px-4 py-3 text-sm font-semibold text-[#3D2B1F] placeholder:text-[#3D2B1F]/30 focus:border-[#FF8C42] focus:outline-none focus:ring-2 focus:ring-[#FF8C42]/30"
                    >
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
                activity-type="plural_quiz"
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
                @php
                    $senseId = (int) $row['sense_id'];
                    $showingTranslation = isset($show_translation_for[(string) $senseId]);
                @endphp
                <div class="rounded-xl border {{ $row['is_correct'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-red-200 bg-red-50/60' }} px-4 py-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                                <span>
                                    @if (! empty($row['article']))
                                        <span class="text-[#3D2B1F]/55">{{ $row['article'] }}</span>
                                    @endif
                                    {{ $row['lemma'] }}
                                    <span class="text-[#3D2B1F]/40">→</span>
                                    <span class="text-[#FF8C42]">{{ $row['correct_plural'] }}</span>
                                </span>
                                <x-speak-word
                                    :text="trim(($row['article'] ?? '').' '.$row['lemma'])"
                                    :lang="SpeechLocale::german()"
                                />
                                <x-speak-word
                                    :text="$row['correct_plural']"
                                    :lang="SpeechLocale::german()"
                                    label="Hear plural"
                                />
                            </p>
                            <p class="text-xs mt-1 {{ $row['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                                @if ($row['is_correct'])
                                    Correct — you wrote {{ $row['selected_plural'] }}
                                @else
                                    Incorrect — you wrote {{ $row['selected_plural'] }}, answer is {{ $row['correct_plural'] }}
                                @endif
                            </p>
                            @if ($showingTranslation && ! empty($row['translation']))
                                <p class="text-sm text-[#3D2B1F]/70 mt-1 flex items-center gap-1 flex-wrap">
                                    <span>{{ $row['translation'] }}</span>
                                    <x-speak-word
                                        :text="$row['translation']"
                                        :lang="SpeechLocale::for($translation_language)"
                                        label="Hear translation"
                                    />
                                </p>
                            @endif
                        </div>
                        <span class="text-xs font-bold uppercase tracking-wide {{ $row['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $row['is_correct'] ? 'True' : 'False' }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <button type="button" wire:click="toggleTranslation({{ $senseId }})" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/80 text-[#3D2B1F]">
                            {{ $showingTranslation ? 'Hide translation' : 'Translate' }}
                        </button>
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
                        @else
                            <button type="button" wire:click="toggleFlag({{ $senseId }}, 'important')" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/80 text-[#3D2B1F]/70">
                                Important (sign in)
                            </button>
                        @endauth
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <flux:button wire:click="backToSetup" variant="primary" class="w-full justify-center bg-[#FF8C42] hover:bg-[#e67a35]">
                Practice again
            </flux:button>
            @auth
                <a href="{{ route('learn.plurals.history') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold text-white bg-[#FF8C42] hover:bg-[#f07d33] transition">
                    View progress
                    <span aria-hidden="true">→</span>
                </a>
            @endauth
        </div>
    @endif

    @if ($show_error_modal && $error_message)
        <div class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4" role="alertdialog" aria-modal="true" aria-labelledby="quiz-error-title">
            <div class="absolute inset-0 bg-[#3D2B1F]/45" wire:click="dismissError"></div>
            <div class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-lg">
                <div class="flex items-start gap-3 mb-3">
                    <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 text-lg font-bold" aria-hidden="true">!</div>
                    <div class="min-w-0 flex-1">
                        <h2 id="quiz-error-title" class="text-lg font-bold text-[#3D2B1F]">Could not continue</h2>
                        <p class="mt-1 text-sm text-[#3D2B1F]/75">{{ $error_message }}</p>
                    </div>
                    <button type="button" wire:click="dismissError" class="text-[#3D2B1F]/50 text-xl leading-none" aria-label="Close">
                        &times;
                    </button>
                </div>
                <flux:button type="button" wire:click="dismissError" variant="primary" class="w-full justify-center bg-[#FF8C42] hover:bg-[#e67a35]">
                    Got it
                </flux:button>
            </div>
        </div>
    @endif

    @php
        $detailCard = null;
        if ($details_sense_id) {
            if (is_array($search_detail) && (int) ($search_detail['sense_id'] ?? 0) === (int) $details_sense_id) {
                $detailCard = $search_detail;
            } else {
                $source = $phase === 'results' ? $results : $cards;
                $detailCard = collect($source)->firstWhere('sense_id', $details_sense_id);
            }
        }
    @endphp

    @if ($detailCard)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeDetails"></div>
            <div class="relative w-full sm:max-w-lg max-h-[85dvh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                @php
                    $detailArticle = (string) ($detailCard['article'] ?? '');
                    $detailPlural = (string) ($detailCard['correct_plural'] ?? $detailCard['plural_form'] ?? '');
                    $showDetailArticle = $detailArticle !== '';
                    $detailSpeakGerman = $showDetailArticle
                        ? trim($detailArticle.' '.$detailCard['lemma'])
                        : (string) $detailCard['lemma'];
                @endphp
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="min-w-0">
                        <p class="text-xl font-bold text-[#3D2B1F] flex items-center gap-1.5 flex-wrap">
                            <span>
                                @if ($showDetailArticle)
                                    <span class="text-[#FF8C42]">{{ $detailArticle }}</span>
                                @endif
                                {{ $detailCard['lemma'] }}
                            </span>
                            <x-speak-word
                                :text="$detailSpeakGerman"
                                :lang="SpeechLocale::german()"
                            />
                        </p>
                        @if ($detailPlural !== '')
                            <p class="text-sm text-[#3D2B1F]/55 flex items-center gap-1 flex-wrap">
                                <span>Plural: {{ $detailPlural }}</span>
                                <x-speak-word
                                    :text="$detailPlural"
                                    :lang="SpeechLocale::german()"
                                    label="Hear plural"
                                />
                            </p>
                        @endif
                        @if (! empty($detailCard['gender']))
                            <p class="text-sm text-[#3D2B1F]/55 capitalize">Gender: {{ $detailCard['gender'] }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closeDetails" class="text-[#3D2B1F]/50 text-xl leading-none">&times;</button>
                </div>

                @if (! empty($detailCard['translations']))
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-1">Translations</h3>
                        <ul class="space-y-1.5">
                            @foreach ($detailCard['translations'] as $translationText)
                                <li class="text-sm text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                                    <span>{{ $translationText }}</span>
                                    <x-speak-word
                                        :text="$translationText"
                                        :lang="SpeechLocale::for($translation_language)"
                                        label="Hear translation"
                                    />
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (! empty($detailCard['examples']))
                    <div class="mb-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-2">Examples</h3>
                        <ul class="space-y-2">
                            @foreach ($detailCard['examples'] as $example)
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

                @if (! empty($detailCard['collocations']))
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45 mb-2">Collocations</h3>
                        <ul class="space-y-2">
                            @foreach ($detailCard['collocations'] as $collocation)
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
