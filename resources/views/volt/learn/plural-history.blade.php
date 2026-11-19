<?php

use App\Domain\Learning\Actions\ClearUserSenseFlags;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\PluralQuizSession;
use App\Domain\Learning\Services\UserSenseFlagListService;
use App\Support\Seo;
use App\Support\SpeechLocale;
use function Livewire\Volt\{state, mount, title};

state([
    'tab' => 'history',
    'sessions' => [],
    'selected_session_id' => null,
    'selected_answers' => [],
    'translation_language' => 'en',
    'important_words' => [],
    'learned_words' => [],
    'important_loaded' => false,
    'learned_loaded' => false,
    'confirm_action' => null,
    'confirm_sense_id' => null,
    'confirm_message' => null,
]);

mount(function () {
    Seo::share([
        'seoTitle' => 'Noun Plurals progress',
        'seoDescription' => 'Review your saved German plural quiz results and marked words.',
        'seoUrl' => route('learn.plurals.history'),
    ]);

    $learning = auth()->user()->languages()
        ->where('type', 'learning')
        ->with('language')
        ->first();

    $code = $learning?->language?->code;
    if (is_string($code) && $code !== '' && $code !== 'de') {
        $this->translation_language = $code;
    }

    $this->sessions = PluralQuizSession::query()
        ->where('user_id', auth()->id())
        ->latest('completed_at')
        ->limit(50)
        ->get([
            'id',
            'level_codes',
            'category_codes',
            'word_count',
            'correct_count',
            'incorrect_count',
            'percentage',
            'translation_language_code',
            'completed_at',
        ])
        ->map(fn (PluralQuizSession $session) => [
            'id' => $session->id,
            'level_label' => ($session->level_codes ?: []) !== []
                ? implode(', ', $session->level_codes)
                : 'All levels',
            'category_label' => ($session->category_codes ?: []) !== []
                ? implode(', ', $session->category_codes)
                : 'All categories',
            'word_count' => $session->word_count,
            'correct_count' => $session->correct_count,
            'incorrect_count' => $session->incorrect_count,
            'percentage' => $session->percentage,
            'translation_language_code' => $session->translation_language_code,
            'completed_at' => optional($session->completed_at)->toIso8601String(),
            'completed_label' => optional($session->completed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A'),
        ])
        ->all();
});

title(fn () => Seo::pageTitle('Noun Plurals progress'));

$setTab = function (string $tab, UserSenseFlagListService $list) {
    if (! in_array($tab, ['history', 'important', 'learned'], true)) {
        return;
    }

    $this->tab = $tab;
    $this->dismissConfirm();

    if ($tab === 'important' && ! $this->important_loaded) {
        $this->important_words = $list->important(
            auth()->user(),
            $this->translation_language,
            SenseFlagContext::Plural,
        );
        $this->important_loaded = true;
    }

    if ($tab === 'learned' && ! $this->learned_loaded) {
        $this->learned_words = $list->learned(
            auth()->user(),
            $this->translation_language,
            SenseFlagContext::Plural,
        );
        $this->learned_loaded = true;
    }
};

$openSession = function (int $sessionId) {
    if ($this->selected_session_id === $sessionId) {
        $this->selected_session_id = null;
        $this->selected_answers = [];

        return;
    }

    $session = PluralQuizSession::query()
        ->where('user_id', auth()->id())
        ->with(['answers' => fn ($q) => $q->orderBy('position')])
        ->find($sessionId);

    if ($session === null) {
        $this->selected_session_id = null;
        $this->selected_answers = [];

        return;
    }

    $this->selected_session_id = $session->id;
    $this->selected_answers = $session->answers->map(fn ($answer) => [
        'lemma' => $answer->lemma,
        'correct_plural' => $answer->correct_plural,
        'selected_plural' => $answer->selected_plural,
        'is_correct' => (bool) $answer->is_correct,
    ])->all();
};

$askRemoveOne = function (int $senseId) {
    $words = $this->tab === 'important' ? $this->important_words : $this->learned_words;
    $word = collect($words)->firstWhere('sense_id', $senseId);

    if ($word === null) {
        return;
    }

    $label = trim(($word['article'] ?? '').' '.($word['lemma'] ?? ''));
    if (! empty($word['plural_form'])) {
        $label .= ' → '.$word['plural_form'];
    }
    $listName = $this->tab === 'important' ? 'important' : 'learned';

    $this->confirm_action = 'remove_one';
    $this->confirm_sense_id = $senseId;
    $this->confirm_message = 'Remove '.$label.' from '.$listName.'?';
};

$askClearAll = function () {
    $words = $this->tab === 'important' ? $this->important_words : $this->learned_words;
    if ($words === []) {
        return;
    }

    $listName = $this->tab === 'important' ? 'important' : 'learned';
    $this->confirm_action = 'clear_all';
    $this->confirm_sense_id = null;
    $this->confirm_message = 'Remove all '.$listName.' words? This cannot be undone.';
};

$dismissConfirm = function () {
    $this->confirm_action = null;
    $this->confirm_sense_id = null;
    $this->confirm_message = null;
};

$confirmAction = function (ClearUserSenseFlags $clear) {
    if ($this->confirm_action === null || ! in_array($this->tab, ['important', 'learned'], true)) {
        $this->dismissConfirm();

        return;
    }

    $flag = $this->tab;
    $action = $this->confirm_action;
    $senseId = $this->confirm_sense_id;

    $this->dismissConfirm();

    if ($action === 'remove_one' && $senseId !== null) {
        $clear->clearOne(auth()->user(), (int) $senseId, $flag, SenseFlagContext::Plural);
        $property = $flag === 'important' ? 'important_words' : 'learned_words';
        $this->{$property} = collect($this->{$property})
            ->reject(fn (array $word): bool => (int) $word['sense_id'] === (int) $senseId)
            ->values()
            ->all();

        return;
    }

    if ($action === 'clear_all') {
        $clear->clearAll(auth()->user(), $flag, SenseFlagContext::Plural);

        if ($flag === 'important') {
            $this->important_words = [];
        } else {
            $this->learned_words = [];
        }
    }
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10">
    <div class="flex items-start justify-between gap-3 mb-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42] mb-1">German practice</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Your progress</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">Quiz history and words you marked important or learned.</p>
        </div>
        <a href="{{ route('learn.plurals') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline shrink-0">
            New quiz
        </a>
    </div>

    <div class="mb-5 grid grid-cols-3 gap-2" role="tablist" aria-label="Progress sections">
        @foreach ([
            'history' => 'History',
            'important' => 'Important',
            'learned' => 'Learned',
        ] as $tabKey => $tabLabel)
            <button
                type="button"
                role="tab"
                wire:click="setTab('{{ $tabKey }}')"
                aria-selected="{{ $tab === $tabKey ? 'true' : 'false' }}"
                class="rounded-xl px-3 py-2.5 text-sm font-semibold transition
                    {{ $tab === $tabKey
                        ? 'bg-[#FF8C42] text-white'
                        : 'bg-[#FFF0E0] text-[#3D2B1F]/70 hover:text-[#3D2B1F]' }}"
            >
                {{ $tabLabel }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'history')
        @if ($sessions === [])
            <div class="rounded-2xl bg-[#FFF0E0] px-4 py-8 text-center">
                <p class="text-sm text-[#3D2B1F]/60 mb-3">No saved quizzes yet.</p>
                <a href="{{ route('learn.plurals') }}" wire:navigate class="inline-flex font-semibold text-[#FF8C42] hover:underline">
                    Start your first quiz
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($sessions as $session)
                    <div class="rounded-2xl bg-[#FFF0E0] overflow-hidden">
                        <button
                            type="button"
                            wire:click="openSession({{ $session['id'] }})"
                            class="w-full text-left px-4 py-4 flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="font-semibold text-[#3D2B1F]">{{ $session['percentage'] }}% · {{ $session['word_count'] }} words</p>
                                <p class="text-xs text-[#3D2B1F]/55 mt-0.5">
                                    {{ $session['completed_label'] }}
                                    · {{ $session['level_label'] }}
                                    · {{ $session['category_label'] }}
                                </p>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-xs text-emerald-700 font-semibold">{{ $session['correct_count'] }} true</p>
                                <p class="text-xs text-red-700 font-semibold">{{ $session['incorrect_count'] }} false</p>
                            </div>
                        </button>

                        @if ($selected_session_id === $session['id'])
                            <div class="border-t border-[#3D2B1F]/10 px-4 py-3 space-y-2 bg-[#FFF8F0]">
                                @foreach ($selected_answers as $answer)
                                    <div class="flex items-center justify-between gap-3 text-sm">
                                        <p class="text-[#3D2B1F] flex items-center gap-1 flex-wrap min-w-0">
                                            <span>
                                                {{ $answer['lemma'] }}
                                                <span class="text-[#3D2B1F]/40">→</span>
                                                <span class="font-semibold text-[#FF8C42]">{{ $answer['correct_plural'] }}</span>
                                            </span>
                                            <x-speak-word
                                                :text="$answer['lemma']"
                                                :lang="SpeechLocale::german()"
                                            />
                                            <x-speak-word
                                                :text="$answer['correct_plural']"
                                                :lang="SpeechLocale::german()"
                                                label="Hear plural"
                                            />
                                        </p>
                                        <p class="{{ $answer['is_correct'] ? 'text-emerald-700' : 'text-red-700' }} shrink-0">
                                            @if ($answer['is_correct'])
                                                True
                                            @else
                                                False ({{ $answer['selected_plural'] }})
                                            @endif
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    @if (in_array($tab, ['important', 'learned'], true))
        @php
            $words = $tab === 'important' ? $important_words : $learned_words;
            $emptyLabel = $tab === 'important' ? 'No important words yet.' : 'No learned words yet.';
            $clearLabel = $tab === 'important' ? 'Clear all important' : 'Clear all learned';
        @endphp

        @if ($words !== [])
            <div class="mb-3 flex justify-end">
                <button
                    type="button"
                    wire:click="askClearAll"
                    class="text-sm font-semibold text-red-700 hover:underline"
                >
                    {{ $clearLabel }}
                </button>
            </div>
        @endif

        @if ($words === [])
            <div class="rounded-2xl bg-[#FFF0E0] px-4 py-8 text-center">
                <p class="text-sm text-[#3D2B1F]/60 mb-3">{{ $emptyLabel }}</p>
                <a href="{{ route('learn.plurals') }}" wire:navigate class="inline-flex font-semibold text-[#FF8C42] hover:underline">
                    Practice to mark words
                </a>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($words as $word)
                    <div class="rounded-2xl bg-[#FFF0E0] px-4 py-3 flex items-start justify-between gap-3" wire:key="flag-{{ $tab }}-{{ $word['sense_id'] }}">
                        <div class="min-w-0">
                            <p class="font-semibold text-[#3D2B1F] flex items-center gap-1 flex-wrap">
                                <span>
                                    @if (! empty($word['article']))
                                        <span class="text-[#3D2B1F]/55">{{ $word['article'] }}</span>
                                    @endif
                                    {{ $word['lemma'] }}
                                    @if (! empty($word['plural_form']))
                                        <span class="text-[#3D2B1F]/40">→</span>
                                        <span class="text-[#FF8C42]">{{ $word['plural_form'] }}</span>
                                    @endif
                                </span>
                                <x-speak-word
                                    :text="trim(($word['article'] ?? '').' '.$word['lemma'])"
                                    :lang="SpeechLocale::german()"
                                />
                                @if (! empty($word['plural_form']))
                                    <x-speak-word
                                        :text="$word['plural_form']"
                                        :lang="SpeechLocale::german()"
                                        label="Hear plural"
                                    />
                                @endif
                            </p>
                            @if (! empty($word['translation']))
                                <p class="text-sm text-[#3D2B1F]/55 mt-0.5 flex items-center gap-1 flex-wrap">
                                    <span>{{ $word['translation'] }}</span>
                                    <x-speak-word
                                        :text="$word['translation']"
                                        :lang="SpeechLocale::for($translation_language)"
                                        label="Hear translation"
                                    />
                                </p>
                            @endif
                        </div>
                        <button
                            type="button"
                            wire:click="askRemoveOne({{ $word['sense_id'] }})"
                            class="shrink-0 text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-white/80 text-red-700 hover:bg-white"
                        >
                            Remove
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    @if ($confirm_action && $confirm_message)
        <div class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4" role="alertdialog" aria-modal="true" aria-labelledby="flag-confirm-title">
            <div class="absolute inset-0 bg-[#3D2B1F]/45" wire:click="dismissConfirm"></div>
            <div class="relative w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl bg-[#FFF8F0] p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-lg">
                <div class="mb-4">
                    <h2 id="flag-confirm-title" class="text-lg font-bold text-[#3D2B1F]">
                        {{ $confirm_action === 'clear_all' ? 'Clear all?' : 'Remove word?' }}
                    </h2>
                    <p class="mt-1 text-sm text-[#3D2B1F]/75">{{ $confirm_message }}</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-2">
                    <flux:button type="button" wire:click="dismissConfirm" variant="ghost" class="w-full justify-center">
                        Cancel
                    </flux:button>
                    <flux:button type="button" wire:click="confirmAction" variant="primary" class="w-full justify-center bg-red-600 hover:bg-red-700">
                        Confirm
                    </flux:button>
                </div>
            </div>
        </div>
    @endif
</div>
