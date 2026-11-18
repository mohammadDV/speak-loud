<?php

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'sessions' => [],
    'selected_session_id' => null,
    'selected_answers' => [],
    'selected_session_meta' => null,
]);

mount(function () {
    Seo::share([
        'seoTitle' => 'Grammar quiz history',
        'seoDescription' => 'Review your saved German grammar quiz results.',
        'seoUrl' => route('learn.grammar.history'),
    ]);

    $this->sessions = GrammarQuizSession::query()
        ->where('user_id', auth()->id())
        ->latest('completed_at')
        ->limit(50)
        ->get([
            'id',
            'lesson_ids',
            'word_count',
            'correct_count',
            'incorrect_count',
            'percentage',
            'translation_language_code',
            'quiz_type',
            'completed_at',
        ])
        ->map(function (GrammarQuizSession $session) {
            $quizType = $session->quiz_type instanceof GrammarQuizType
                ? $session->quiz_type
                : GrammarQuizType::tryFrom((string) $session->quiz_type);

            return [
                'id' => $session->id,
                'lesson_count' => count($session->lesson_ids ?? []),
                'word_count' => $session->word_count,
                'correct_count' => $session->correct_count,
                'incorrect_count' => $session->incorrect_count,
                'percentage' => $session->percentage,
                'translation_language_code' => $session->translation_language_code,
                'quiz_type_label' => $quizType?->label() ?? GrammarQuizType::MultipleChoice->label(),
                'completed_label' => optional($session->completed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A'),
            ];
        })
        ->all();
});

title(fn () => Seo::pageTitle('Grammar quiz history'));

$selectSession = function (int $sessionId) {
    $session = GrammarQuizSession::query()
        ->where('user_id', auth()->id())
        ->with('answers')
        ->find($sessionId);

    if ($session === null) {
        return;
    }

    $this->selected_session_id = $session->id;
    $this->selected_session_meta = [
        'percentage' => $session->percentage,
        'correct_count' => $session->correct_count,
        'incorrect_count' => $session->incorrect_count,
        'quiz_type_label' => $session->quiz_type?->label(),
        'completed_label' => optional($session->completed_at)->timezone(config('app.timezone'))->format('M j, Y g:i A'),
    ];
    $this->selected_answers = $session->answers->map(fn ($answer): array => [
        'prompt' => $answer->prompt,
        'selected_answer' => $answer->selected_answer,
        'correct_answer' => $answer->correct_answer,
        'is_correct' => (bool) $answer->is_correct,
    ])->all();
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-6">
    <div>
        <a href="{{ route('learn.grammar') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline">← Grammar courses</a>
        <h1 class="mt-3 text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Grammar history</h1>
    </div>

    <div class="space-y-3">
        @forelse ($sessions as $session)
            <button
                type="button"
                wire:click="selectSession({{ $session['id'] }})"
                class="w-full text-left rounded-3xl bg-white border border-[#3D2B1F]/10 p-4 shadow-sm hover:border-[#FF8C42]/40 transition {{ $selected_session_id === $session['id'] ? 'border-[#FF8C42]' : '' }}"
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="font-semibold text-[#3D2B1F]">{{ $session['completed_label'] }}</p>
                        <p class="text-xs text-[#3D2B1F]/55 mt-1">
                            {{ $session['quiz_type_label'] }} · {{ $session['lesson_count'] }} lesson(s) · {{ strtoupper($session['translation_language_code']) }}
                        </p>
                    </div>
                    <p class="text-lg font-bold text-[#FF8C42]">{{ $session['percentage'] }}%</p>
                </div>
            </button>
        @empty
            <p class="rounded-3xl bg-[#FFF0E0] p-5 text-sm text-[#3D2B1F]/70">No saved grammar quizzes yet.</p>
        @endforelse
    </div>

    @if ($selected_session_meta)
        <section class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 shadow-sm space-y-4">
            <div>
                <h2 class="font-bold text-[#3D2B1F]">Session details</h2>
                <p class="text-sm text-[#3D2B1F]/60 mt-1">
                    {{ $selected_session_meta['completed_label'] }} · {{ $selected_session_meta['quiz_type_label'] }} ·
                    {{ $selected_session_meta['correct_count'] }}/{{ $selected_session_meta['correct_count'] + $selected_session_meta['incorrect_count'] }}
                </p>
            </div>
            @foreach ($selected_answers as $answer)
                <div class="border-t border-[#3D2B1F]/08 pt-3">
                    <p class="text-sm font-semibold text-[#3D2B1F]">{{ $answer['prompt'] }}</p>
                    <p class="text-sm mt-1 {{ $answer['is_correct'] ? 'text-green-700' : 'text-red-700' }}">
                        Your answer: {{ $answer['selected_answer'] }}
                    </p>
                    @unless ($answer['is_correct'])
                        <p class="text-sm text-[#3D2B1F]/65">Correct: {{ $answer['correct_answer'] }}</p>
                    @endunless
                </div>
            @endforeach
        </section>
    @endif
</div>
