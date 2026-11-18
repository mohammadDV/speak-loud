<?php

use App\Domain\Learning\Services\GrammarQuizService;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'course_code' => '',
    'language' => 'en',
    'course' => null,
    'lessons' => [],
    'selected_lesson_ids' => [],
]);

mount(function (string $course, GrammarQuizService $quiz) {
    $preferred = request()->query('lang');
    if (is_string($preferred) && in_array($preferred, GrammarQuizService::UI_LANGUAGES, true)) {
        $this->language = $preferred;
    }

    $model = $quiz->findPublishedCourse($course);
    abort_if($model === null, 404);

    $this->course_code = $model->code;
    $translation = $model->translationFor($this->language);

    Seo::share([
        'seoTitle' => $translation?->title ?? 'Grammar course',
        'seoDescription' => $translation?->description ?? 'German grammar lessons and practice quizzes.',
        'seoUrl' => route('learn.grammar.course', ['course' => $model->code]),
    ]);

    $this->course = [
        'code' => $model->code,
        'cefr_level' => $model->cefr_level,
        'title' => $translation?->title ?? $model->code,
        'description' => $translation?->description,
    ];

    $this->lessons = $model->lessons->map(function ($lesson) {
        $lessonTranslation = $lesson->translationFor($this->language);

        return [
            'id' => $lesson->id,
            'code' => $lesson->code,
            'title' => $lessonTranslation?->title ?? $lesson->code,
            'position' => $lesson->position,
            'cefr_level' => $lesson->cefr_level,
        ];
    })->all();

    $this->selected_lesson_ids = array_map(
        static fn (array $lesson): int => (int) $lesson['id'],
        $this->lessons,
    );
});

title(fn () => Seo::pageTitle($this->course['title'] ?? 'Grammar course'));

$toggleLesson = function (int $lessonId): void {
    $ids = array_map('intval', $this->selected_lesson_ids);
    if (in_array($lessonId, $ids, true)) {
        $this->selected_lesson_ids = array_values(array_filter($ids, static fn (int $id): bool => $id !== $lessonId));
    } else {
        $ids[] = $lessonId;
        $this->selected_lesson_ids = array_values($ids);
    }
};

$startPractice = function () {
    if ($this->selected_lesson_ids === []) {
        return;
    }

    $query = http_build_query([
        'lessons' => implode(',', array_map('intval', $this->selected_lesson_ids)),
        'lang' => $this->language,
        'course' => $this->course_code,
    ]);

    return $this->redirect(route('learn.grammar.quiz').'?'.$query, navigate: true);
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <a href="{{ route('learn.grammar', ['lang' => $language]) }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline">← All courses</a>
        @if ($course['cefr_level'] ?? null)
            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42]">{{ $course['cefr_level'] }}</p>
        @endif
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">{{ $course['title'] ?? '' }}</h1>
        @if ($course['description'] ?? null)
            <p class="mt-2 text-sm text-[#3D2B1F]/65">{{ $course['description'] }}</p>
        @endif
    </div>

    <section class="space-y-3">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#3D2B1F]/45">Lessons</h2>
        @foreach ($lessons as $index => $lesson)
            <div class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-[#3D2B1F]/45">Lesson {{ $index + 1 }}</p>
                        <h3 class="text-lg font-bold text-[#3D2B1F]">{{ $lesson['title'] }}</h3>
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-[#3D2B1F]/70">
                        <input
                            type="checkbox"
                            wire:click="toggleLesson({{ $lesson['id'] }})"
                            @checked(in_array($lesson['id'], $selected_lesson_ids, true))
                            class="rounded border-[#3D2B1F]/25 text-[#FF8C42] focus:ring-[#FF8C42]"
                        >
                        Quiz
                    </label>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a
                        href="{{ route('learn.grammar.lesson', ['course' => $course_code, 'lesson' => $lesson['code'], 'lang' => $language]) }}"
                        wire:navigate
                        class="text-sm font-semibold text-[#FF8C42] hover:underline"
                    >
                        Read lesson
                    </a>
                    <a
                        href="{{ route('learn.grammar.quiz', ['lessons' => $lesson['id'], 'lang' => $language, 'course' => $course_code]) }}"
                        wire:navigate
                        class="text-sm font-semibold text-[#FF8C42] hover:underline"
                    >
                        Practice this lesson
                    </a>
                </div>
            </div>
        @endforeach
    </section>

    <div class="sticky bottom-20 md:bottom-6 z-10">
        <button
            type="button"
            wire:click="startPractice"
            @disabled($selected_lesson_ids === [])
            class="w-full rounded-2xl bg-[#FF8C42] px-4 py-3.5 text-sm font-bold text-white shadow-lg disabled:opacity-40 disabled:cursor-not-allowed hover:bg-[#f07d33] transition"
        >
            Practice selected lessons ({{ count($selected_lesson_ids) }})
        </button>
    </div>
</div>
