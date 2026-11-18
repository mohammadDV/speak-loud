<?php

use App\Domain\Learning\Services\GrammarQuizService;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'language' => 'en',
    'courses' => [],
]);

mount(function (GrammarQuizService $quiz) {
    Seo::share([
        'seoTitle' => 'German grammar lessons',
        'seoDescription' => 'Step-by-step German grammar courses with practice quizzes.',
        'seoUrl' => route('learn.grammar'),
    ]);

    $preferred = request()->query('lang');
    if (is_string($preferred) && in_array($preferred, GrammarQuizService::UI_LANGUAGES, true)) {
        $this->language = $preferred;
    } elseif (auth()->check()) {
        $learning = auth()->user()->languages()
            ->where('type', 'learning')
            ->with('language')
            ->first();
        $code = $learning?->language?->code;
        if (is_string($code) && in_array($code, GrammarQuizService::UI_LANGUAGES, true)) {
            $this->language = $code;
        }
    }

    $this->courses = $quiz->publishedCourses()->map(function ($course) {
        $translation = $course->translationFor($this->language);

        return [
            'code' => $course->code,
            'cefr_level' => $course->cefr_level,
            'title' => $translation?->title ?? $course->code,
            'description' => $translation?->description,
            'lesson_count' => $course->lessons->count(),
        ];
    })->all();
});

title(fn () => Seo::pageTitle('German grammar lessons'));

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">Courses</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">German grammar</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/65">
            Read a lesson, then practice with multiple-choice or fill-in-the-blank quizzes.
        </p>
        <div class="mt-3 flex flex-wrap gap-3 text-sm">
            <a href="{{ route('learn.index') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">← Back to Learn</a>
            @auth
                <a href="{{ route('learn.grammar.history') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">History</a>
            @endauth
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($courses as $course)
            <a
                href="{{ route('learn.grammar.course', ['course' => $course['code'], 'lang' => $language]) }}"
                wire:navigate
                class="block group rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm hover:border-[#FF8C42]/40 hover:shadow-md transition"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        @if ($course['cefr_level'])
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">{{ $course['cefr_level'] }}</p>
                        @endif
                        <h2 class="text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">{{ $course['title'] }}</h2>
                        @if ($course['description'])
                            <p class="text-sm text-[#3D2B1F]/65 mt-1">{{ $course['description'] }}</p>
                        @endif
                        <p class="text-xs text-[#3D2B1F]/45 mt-2">{{ $course['lesson_count'] }} lesson{{ $course['lesson_count'] === 1 ? '' : 's' }}</p>
                    </div>
                    <span class="shrink-0 text-[#FF8C42] font-semibold text-sm mt-1" aria-hidden="true">→</span>
                </div>
            </a>
        @empty
            <div class="rounded-3xl bg-[#FFF0E0] p-6 text-sm text-[#3D2B1F]/70">
                No published grammar courses yet. Import lesson data with <code class="font-mono text-xs">php artisan lessons:import</code>.
            </div>
        @endforelse
    </div>
</div>
