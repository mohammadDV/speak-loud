<?php

use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\Lesson;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'available' => false,
    'course_code' => 'de-grammar-a1',
    'lesson_code' => 'prepositions-time-and-place',
    'lesson_id' => null,
    'lesson_title' => 'Prepositions: Time & Place',
    'lesson_url' => null,
    'quiz_url' => null,
]);

mount(function () {
    Seo::share([
        'seoTitle' => 'German prepositions quiz',
        'seoDescription' => 'Learn German temporal and local prepositions: mit, zu, nach, FUDOG, Wechselpräpositionen, and Wohin / Wo / Woher.',
        'seoUrl' => route('learn.prepositions'),
    ]);

    $course = Course::query()
        ->where('code', $this->course_code)
        ->where('status', LessonContentStatus::Published)
        ->first();

    if ($course === null) {
        return;
    }

    $lesson = Lesson::query()
        ->where('course_id', $course->id)
        ->where('code', $this->lesson_code)
        ->where('status', LessonContentStatus::Published)
        ->with('translations')
        ->first();

    if ($lesson === null) {
        return;
    }

    $translation = $lesson->translationFor('en');
    $this->available = true;
    $this->lesson_id = $lesson->id;
    $this->lesson_title = $translation?->title ?: 'Prepositions: Time & Place';
    $this->lesson_url = route('learn.grammar.lesson', [
        'course' => $this->course_code,
        'lesson' => $this->lesson_code,
    ]);
    $this->quiz_url = route('learn.grammar.quiz', [
        'course' => $this->course_code,
        'lessons' => $lesson->id,
        'count' => 10,
    ]);
});

title(fn () => Seo::pageTitle('German prepositions'));

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">German practice</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Prepositions</h1>
        <p class="mt-2 text-[#3D2B1F]/70 max-w-2xl">
            Master time and place prepositions — one of the most important A1 skills for clear German sentences.
        </p>
        <x-learn.nav active="dashboard" />
    </div>

    <section class="relative overflow-hidden rounded-3xl bg-[#3D2B1F] text-[#FFF8F0] p-6 md:p-8 shadow-[0_24px_60px_-28px_rgba(61,43,31,0.65)]">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_85%_15%,rgba(255,140,66,0.45),transparent_42%),radial-gradient(circle_at_10%_90%,rgba(255,240,224,0.12),transparent_40%)]" aria-hidden="true"></div>
        <div class="relative space-y-4">
            <p class="text-sm text-[#FFF0E0]/75">Featured grammar focus</p>
            <h2 class="text-2xl md:text-3xl font-bold">{{ $lesson_title }}</h2>
            <p class="text-[#FFF0E0]/85 max-w-xl">
                Practice <span class="font-semibold text-white">im / am / um</span>, dative-only prepositions,
                <span class="font-semibold text-white">FUDOG</span>, two-way prepositions, and
                <span class="font-semibold text-white">Wohin? / Wo? / Woher?</span>
            </p>

            @if ($available)
                <div class="flex flex-col sm:flex-row gap-3 pt-2">
                    <a
                        href="{{ $quiz_url }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl bg-[#FF8C42] px-5 py-3 text-sm font-bold text-white hover:bg-[#f07d33] transition"
                    >
                        Practice prepositions
                    </a>
                    <a
                        href="{{ $lesson_url }}"
                        wire:navigate
                        class="inline-flex items-center justify-center rounded-xl bg-white/10 px-5 py-3 text-sm font-bold text-white ring-1 ring-white/25 hover:bg-white/15 transition"
                    >
                        Study the lesson
                    </a>
                </div>
            @else
                <p class="text-sm text-[#FFF0E0]/85">
                    This lesson is not published yet. Check back soon, or browse all
                    <a href="{{ route('learn.grammar') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">grammar lessons</a>.
                </p>
            @endif
        </div>
    </section>

    <section class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm space-y-4">
        <h2 class="font-bold text-[#3D2B1F]">What you will practice</h2>
        <ul class="space-y-3 text-sm text-[#3D2B1F]/75">
            <li><span class="font-semibold text-[#3D2B1F]">Time:</span> im Januar, am Montag, um 9 Uhr, in der Nacht, seit…</li>
            <li><span class="font-semibold text-[#3D2B1F]">Always dative:</span> aus, bei, mit, nach, seit, von, zu (zum / zur / beim / vom)</li>
            <li><span class="font-semibold text-[#3D2B1F]">Always accusative (FUDOG):</span> für, um, durch, ohne, gegen</li>
            <li><span class="font-semibold text-[#3D2B1F]">Two-way:</span> Wo? → dative · Wohin? → accusative</li>
            <li><span class="font-semibold text-[#3D2B1F]">Local matrix:</span> nach Berlin · in die Schweiz · zum Arzt · nach Hause</li>
        </ul>
    </section>

    <p class="text-sm text-[#3D2B1F]/60">
        Prefer the full course path?
        <a href="{{ route('learn.grammar') }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">All grammar lessons</a>
    </p>
</div>
