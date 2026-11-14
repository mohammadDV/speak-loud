<?php

use App\Domain\Learning\Actions\SaveArticleQuizResult;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Services\ArticleQuizService;
use App\Support\Seo;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{state, mount, title};

state([
    'phase' => 'setup',
    'level_codes' => [],
    'category_codes' => [],
    'word_count' => 10,
    'translation_language' => 'en',
    'levels' => [],
    'categories' => [],
    'languages' => [],
    'cards' => [],
    'answers' => [],
    'results' => [],
    'correct_count' => 0,
    'incorrect_count' => 0,
    'percentage' => 0,
    'saved_session_id' => null,
    'details_sense_id' => null,
    'show_translation_for' => [],
    'error_message' => null,
    'show_error_modal' => false,
    'scroll_to_id' => null,
]);

mount(function (ArticleQuizService $quiz) {
    Seo::share([
        'seoTitle' => 'Der / Die / Das quiz',
        'seoDescription' => 'Practice German noun articles. Filter by CEFR level and category, then quiz yourself on der, die, and das.',
        'seoUrl' => route('learn.der-die-das'),
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
});

title(fn () => Seo::pageTitle('Der / Die / Das quiz'));

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

$loadCards = function (ArticleQuizService $quiz) {
    $this->error_message = null;
    $this->show_error_modal = false;

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

        $this->cards = $quiz->buildQuiz(
            (int) $this->word_count,
            array_values($this->level_codes),
            array_values($this->category_codes),
            $this->translation_language,
            auth()->user(),
        );
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

$previewArticles = function (ArticleQuizService $quiz) {
    if (! $this->loadCards($quiz)) {
        return;
    }

    $this->phase = 'preview';
};

$startQuiz = function (ArticleQuizService $quiz) {
    if ($this->phase !== 'preview' || $this->cards === []) {
        if (! $this->loadCards($quiz)) {
            return;
        }
    }

    $this->answers = [];
    foreach ($this->cards as $card) {
        $this->answers[(string) $card['sense_id']] = '';
    }

    $this->phase = 'quiz';
    $this->details_sense_id = null;
};

$backToSetup = function () {
    $this->phase = 'setup';
    $this->cards = [];
    $this->answers = [];
    $this->results = [];
    $this->error_message = null;
    $this->show_error_modal = false;
    $this->scroll_to_id = null;
    $this->details_sense_id = null;
};

$submitQuiz = function (SaveArticleQuizResult $save) {
    try {
        $this->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['required', 'in:der,die,das'],
        ], [
            'answers.*.required' => 'Pick der, die, or das for every word.',
            'answers.*.in' => 'Pick der, die, or das for every word.',
        ]);
    } catch (ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());
        $message = collect($e->errors())->flatten()->first()
            ?? 'Pick der, die, or das for every word.';

        $scrollTo = null;
        foreach ($this->cards as $card) {
            $senseKey = (string) $card['sense_id'];
            $answer = trim((string) ($this->answers[$senseKey] ?? ''));
            if ($answer === '' || ! in_array($answer, ['der', 'die', 'das'], true)) {
                $scrollTo = 'quiz-word-'.$senseKey;
                break;
            }
        }

        $this->showError($message, $scrollTo);

        return;
    }

    $results = [];
    $correct = 0;

    foreach ($this->cards as $index => $card) {
        $senseId = (string) $card['sense_id'];
        $selected = strtolower((string) ($this->answers[$senseId] ?? ''));
        $article = strtolower((string) $card['article']);
        $isCorrect = $selected === $article;

        if ($isCorrect) {
            $correct++;
        }

        $results[] = [
            'sense_id' => $card['sense_id'],
            'lemma' => $card['lemma'],
            'correct_article' => $article,
            'selected_article' => $selected,
            'is_correct' => $isCorrect,
            'position' => $index,
            'translation' => $card['translation'] ?? null,
            'translations' => $card['translations'] ?? [],
            'gender' => $card['gender'] ?? null,
            'plural_form' => $card['plural_form'] ?? null,
            'examples' => $card['examples'] ?? [],
            'collocations' => $card['collocations'] ?? [],
            'is_important' => $card['is_important'] ?? false,
            'is_learned' => $card['is_learned'] ?? false,
        ];
    }

    $total = count($results);
    $this->results = $results;
    $this->correct_count = $correct;
    $this->incorrect_count = $total - $correct;
    $this->percentage = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
    $this->phase = 'results';

    if (auth()->check()) {
        $session = $save->execute(auth()->user(), [
            'level_codes' => array_values($this->level_codes),
            'category_codes' => array_values($this->category_codes),
            'word_count' => $total,
            'translation_language_code' => $this->translation_language,
            'answers' => array_map(fn (array $row): array => [
                'sense_id' => $row['sense_id'],
                'lemma' => $row['lemma'],
                'correct_article' => $row['correct_article'],
                'selected_article' => $row['selected_article'],
                'is_correct' => $row['is_correct'],
                'position' => $row['position'],
            ], $results),
        ]);

        $this->saved_session_id = $session->id;
    }
};

$toggleFlag = function (int $senseId, string $flag, ToggleUserSenseFlag $toggle) {
    if (! auth()->check()) {
        session([
            'pending_learn_return' => route('learn.der-die-das'),
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    $record = $toggle->execute(auth()->user(), $senseId, $flag);

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
    $this->details_sense_id = $this->details_sense_id === $senseId ? null : $senseId;
};

$closeDetails = function () {
    $this->details_sense_id = null;
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10">
    <div class="flex items-start justify-between gap-3 mb-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42] mb-1">German practice</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Der / Die / Das</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">Pick your filters, practice articles, and check your score.</p>
        </div>
        @auth
            <a
                href="{{ route('learn.der-die-das.history') }}"
                wire:navigate
                class="shrink-0 text-sm font-semibold text-[#FF8C42] hover:underline"
            >
                History
            </a>
        @endauth
    </div>

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
                <flux:button wire:click="previewArticles" variant="ghost" class="w-full sm:w-auto justify-center" :disabled="$languages === []">
                    Show articles
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
        <div class="flex items-center justify-between gap-3 mb-4">
            <h2 class="text-lg font-semibold text-[#3D2B1F]">Preview with articles</h2>
            <button type="button" wire:click="backToSetup" class="text-sm text-[#3D2B1F]/60 hover:text-[#3D2B1F]">Change filters</button>
        </div>

        <div class="space-y-3 mb-6">
            @foreach ($cards as $card)
                <div class="rounded-xl bg-[#FFF0E0] px-4 py-3 flex items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-[#3D2B1F]">
                            <span class="text-[#FF8C42]">{{ $card['article'] }}</span>
                            {{ $card['lemma'] }}
                        </p>
                        @if (! empty($card['translation']))
                            <p class="text-sm text-[#3D2B1F]/55">{{ $card['translation'] }}</p>
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
                <h2 class="text-lg font-semibold text-[#3D2B1F]">Choose the article</h2>
                <span class="text-sm text-[#3D2B1F]/50">{{ count($cards) }} words</span>
            </div>

            @foreach ($cards as $index => $card)
                @php $senseKey = (string) $card['sense_id']; @endphp
                <fieldset id="quiz-word-{{ $senseKey }}" class="rounded-2xl bg-[#FFF0E0] p-4">
                    <legend class="sr-only">Word {{ $index + 1 }}</legend>
                    <p class="text-xl font-bold text-[#3D2B1F] mb-3">{{ $card['lemma'] }}</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach (['der', 'die', 'das'] as $article)
                            <label
                                class="cursor-pointer rounded-xl border-2 px-2 py-3 text-center font-semibold capitalize transition
                                    border-transparent bg-white/70 text-[#3D2B1F]/80
                                    has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/20 has-[:checked]:text-[#3D2B1F] has-[:checked]:shadow-sm"
                            >
                                <input
                                    type="radio"
                                    class="sr-only"
                                    wire:model="answers.{{ $senseKey }}"
                                    name="answer-{{ $senseKey }}"
                                    value="{{ $article }}"
                                >
                                {{ $article }}
                            </label>
                        @endforeach
                    </div>
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
                        <div>
                            <p class="font-semibold text-[#3D2B1F]">
                                <span class="text-[#FF8C42]">{{ $row['correct_article'] }}</span>
                                {{ $row['lemma'] }}
                            </p>
                            <p class="text-xs mt-1 {{ $row['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                                @if ($row['is_correct'])
                                    Correct — you chose {{ $row['selected_article'] }}
                                @else
                                    Incorrect — you chose {{ $row['selected_article'] }}, answer is {{ $row['correct_article'] }}
                                @endif
                            </p>
                            @if ($showingTranslation && ! empty($row['translation']))
                                <p class="text-sm text-[#3D2B1F]/70 mt-1">{{ $row['translation'] }}</p>
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
                <a href="{{ route('learn.der-die-das.history') }}" wire:navigate class="w-full sm:w-auto inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-semibold text-[#3D2B1F] bg-[#FFF0E0]">
                    View history
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
            $source = $phase === 'results' ? $results : $cards;
            $detailCard = collect($source)->firstWhere('sense_id', $details_sense_id);
        }
    @endphp

    @if ($detailCard)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeDetails"></div>
            <div class="relative w-full sm:max-w-lg max-h-[85dvh] overflow-y-auto rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))]">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <p class="text-xl font-bold text-[#3D2B1F]">
                            @if ($phase !== 'quiz')
                                <span class="text-[#FF8C42]">{{ $detailCard['correct_article'] ?? $detailCard['article'] ?? '' }}</span>
                            @endif
                            {{ $detailCard['lemma'] }}
                        </p>
                        @if (! empty($detailCard['plural_form']))
                            <p class="text-sm text-[#3D2B1F]/55">Plural: {{ $detailCard['plural_form'] }}</p>
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
                        <p class="text-sm text-[#3D2B1F]">{{ implode(' · ', $detailCard['translations']) }}</p>
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
