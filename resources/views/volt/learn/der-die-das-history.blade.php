<?php

use App\Domain\Learning\Models\ArticleQuizSession;
use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};

state([
    'sessions' => [],
    'selected_session_id' => null,
    'selected_answers' => [],
]);

mount(function () {
    Seo::share([
        'seoTitle' => 'Der / Die / Das history',
        'seoDescription' => 'Review your saved German article quiz results.',
        'seoUrl' => route('learn.der-die-das.history'),
    ]);

    $this->sessions = ArticleQuizSession::query()
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
        ->map(fn (ArticleQuizSession $session) => [
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

title(fn () => Seo::pageTitle('Der / Die / Das history'));

$openSession = function (int $sessionId) {
    if ($this->selected_session_id === $sessionId) {
        $this->selected_session_id = null;
        $this->selected_answers = [];

        return;
    }

    $session = ArticleQuizSession::query()
        ->where('user_id', auth()->id())
        ->with(['answers' => fn ($q) => $q->orderBy('position')])
        ->findOrFail($sessionId);

    $this->selected_session_id = $session->id;
    $this->selected_answers = $session->answers->map(fn ($answer) => [
        'lemma' => $answer->lemma,
        'correct_article' => $answer->correct_article,
        'selected_article' => $answer->selected_article,
        'is_correct' => (bool) $answer->is_correct,
    ])->all();
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10">
    <div class="flex items-start justify-between gap-3 mb-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42] mb-1">German practice</p>
            <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Quiz history</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">Your saved der / die / das results.</p>
        </div>
        <a href="{{ route('learn.der-die-das') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline shrink-0">
            New quiz
        </a>
    </div>

    @if ($sessions === [])
        <div class="rounded-2xl bg-[#FFF0E0] px-4 py-8 text-center">
            <p class="text-sm text-[#3D2B1F]/60 mb-3">No saved quizzes yet.</p>
            <a href="{{ route('learn.der-die-das') }}" wire:navigate class="inline-flex font-semibold text-[#FF8C42] hover:underline">
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
                                    <p class="text-[#3D2B1F]">
                                        <span class="font-semibold text-[#FF8C42]">{{ $answer['correct_article'] }}</span>
                                        {{ $answer['lemma'] }}
                                    </p>
                                    <p class="{{ $answer['is_correct'] ? 'text-emerald-700' : 'text-red-700' }}">
                                        @if ($answer['is_correct'])
                                            True
                                        @else
                                            False ({{ $answer['selected_article'] }})
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
</div>
