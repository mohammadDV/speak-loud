<?php

use App\Domain\Learning\Services\GrammarQuizService;
use App\Domain\Learning\Services\LessonMarkdownRenderer;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'course_code' => '',
    'lesson_code' => '',
    'lesson_id' => null,
    'language' => 'en',
    'languages' => [],
    'title_text' => '',
    'body_html' => '',
    'is_rtl' => false,
    'next_lesson' => null,
]);

$loadLessonContent = function (LessonMarkdownRenderer $renderer, $lessonModel): void {
    $translation = $lessonModel->translationFor($this->language);
    $this->title_text = $translation?->title ?? $lessonModel->code;
    $this->is_rtl = $renderer->isRtl($this->language);
    $this->body_html = $renderer->toHtml(
        (string) ($translation?->body_markdown ?? ''),
        $this->language,
    );

    $next = $lessonModel->nextLesson();
    $this->next_lesson = null;
    if ($next !== null) {
        $nextTranslation = $next->translationFor($this->language);
        $this->next_lesson = [
            'code' => $next->code,
            'title' => $nextTranslation?->title ?? $next->code,
        ];
    }
};

mount(function (string $course, string $lesson, GrammarQuizService $quiz, LessonMarkdownRenderer $renderer) {
    $preferred = request()->query('lang');
    if (is_string($preferred) && in_array($preferred, GrammarQuizService::UI_LANGUAGES, true)) {
        $this->language = $preferred;
    }

    $courseModel = $quiz->findPublishedCourse($course);
    abort_if($courseModel === null, 404);

    $lessonModel = $quiz->findPublishedLesson($courseModel, $lesson);
    abort_if($lessonModel === null, 404);

    $this->course_code = $courseModel->code;
    $this->lesson_code = $lessonModel->code;
    $this->lesson_id = $lessonModel->id;
    $this->languages = GrammarQuizService::UI_LANGUAGES;

    $this->loadLessonContent($renderer, $lessonModel);

    Seo::share([
        'seoTitle' => $this->title_text,
        'seoDescription' => 'German grammar lesson with practice quiz.',
        'seoUrl' => route('learn.grammar.lesson', [
            'course' => $courseModel->code,
            'lesson' => $lessonModel->code,
        ]),
    ]);
});

title(fn () => Seo::pageTitle($this->title_text ?: 'Grammar lesson'));

$changeLanguage = function (string $lang, GrammarQuizService $quiz, LessonMarkdownRenderer $renderer) {
    if (! in_array($lang, GrammarQuizService::UI_LANGUAGES, true)) {
        return;
    }

    $this->language = $lang;
    $courseModel = $quiz->findPublishedCourse($this->course_code);
    $lessonModel = $courseModel ? $quiz->findPublishedLesson($courseModel, $this->lesson_code) : null;
    if ($lessonModel === null) {
        return;
    }

    $this->loadLessonContent($renderer, $lessonModel);
};

$uiCopy = function (): array {
    return match ($this->language) {
        'es' => [
            'back' => 'Volver al curso',
            'eyebrow' => 'Lección de gramática',
            'practice' => 'Practicar esta lección',
            'next' => 'Siguiente lección',
        ],
        'fa' => [
            'back' => 'بازگشت به دوره',
            'eyebrow' => 'درس گرامر',
            'practice' => 'تمرین این درس',
            'next' => 'درس بعدی',
        ],
        'ar' => [
            'back' => 'العودة إلى الدورة',
            'eyebrow' => 'درس القواعد',
            'practice' => 'تمرّن على هذا الدرس',
            'next' => 'الدرس التالي',
        ],
        'tr' => [
            'back' => 'Kursa dön',
            'eyebrow' => 'Dilbilgisi dersi',
            'practice' => 'Bu dersi pratik et',
            'next' => 'Sonraki ders',
        ],
        default => [
            'back' => 'Back to course',
            'eyebrow' => 'Grammar lesson',
            'practice' => 'Practice this lesson',
            'next' => 'Next lesson',
        ],
    };
};

?>

@php($ui = $this->uiCopy())

<div
    class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-6"
    @if ($is_rtl) dir="rtl" @else dir="ltr" @endif
>
    <div class="space-y-4">
        <a
            href="{{ route('learn.grammar.course', ['course' => $course_code, 'lang' => $language]) }}"
            wire:navigate
            class="inline-flex items-center gap-1 text-sm font-semibold text-[#FF8C42] hover:underline"
        >
            <span aria-hidden="true">{{ $is_rtl ? '→' : '←' }}</span>
            <span>{{ $ui['back'] }}</span>
        </a>

        <div class="space-y-3">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42]">
                {{ $ui['eyebrow'] }}
            </p>
            <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F] leading-tight">
                {{ $title_text }}
            </h1>
        </div>

        <div class="flex flex-wrap gap-2" dir="ltr">
            @foreach ($languages as $code)
                <button
                    type="button"
                    wire:click="changeLanguage('{{ $code }}')"
                    @class([
                        'rounded-lg px-3 py-1.5 text-xs font-bold border transition',
                        'bg-[#FF8C42] text-white border-[#FF8C42] shadow-sm' => $language === $code,
                        'bg-white text-[#3D2B1F]/70 border-[#3D2B1F]/12 hover:border-[#FF8C42]/40' => $language !== $code,
                    ])
                >
                    {{ strtoupper($code) }}
                </button>
            @endforeach
        </div>
    </div>

    <article
        @class([
            'lesson-content rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-8 shadow-sm',
            'lesson-content--rtl' => $is_rtl,
        ])
        @if ($is_rtl) dir="rtl" @else dir="ltr" @endif
    >
        {!! $body_html !!}
    </article>

    <div class="flex flex-col sm:flex-row gap-3">
        <a
            href="{{ route('learn.grammar.quiz', ['lessons' => $lesson_id, 'course' => $course_code, 'lang' => $language]) }}"
            wire:navigate
            class="inline-flex justify-center rounded-2xl bg-[#FF8C42] px-4 py-3 text-sm font-bold text-white hover:bg-[#f07d33] transition"
        >
            {{ $ui['practice'] }}
        </a>
        @if ($next_lesson)
            <a
                href="{{ route('learn.grammar.lesson', ['course' => $course_code, 'lesson' => $next_lesson['code'], 'lang' => $language]) }}"
                wire:navigate
                class="inline-flex justify-center rounded-2xl bg-white border border-[#3D2B1F]/15 px-4 py-3 text-sm font-bold text-[#3D2B1F] hover:border-[#FF8C42]/40 transition"
            >
                {{ $ui['next'] }}: {{ $next_lesson['title'] }}
                <span class="ms-1" aria-hidden="true">{{ $is_rtl ? '←' : '→' }}</span>
            </a>
        @endif
    </div>
</div>
