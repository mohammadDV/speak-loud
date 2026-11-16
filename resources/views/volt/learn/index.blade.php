<?php

use App\Support\Seo;
use function Livewire\Volt\{mount, title};

mount(function () {
    Seo::share([
        'seoTitle' => 'Learn German',
        'seoDescription' => 'Practice German with article quizzes and vocabulary meaning quizzes. Filter by level and category.',
        'seoUrl' => route('learn.index'),
    ]);
});

title(fn () => Seo::pageTitle('Learn German'));

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42] mb-1">German practice</p>
        <h1 class="text-2xl md:text-3xl font-bold text-[#3D2B1F]">Learn</h1>
        <p class="text-sm text-[#3D2B1F]/60 mt-1">
            Pick a quiz to practice articles or vocabulary meanings.
        </p>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl bg-[#FFF0E0] p-5 md:p-6">
            <a href="{{ route('learn.der-die-das') }}" wire:navigate class="block group">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Der / Die / Das</h2>
                        <p class="text-sm text-[#3D2B1F]/65 mt-1">
                            Practice German noun articles. Filter by CEFR level and category, then choose der, die, or das.
                        </p>
                    </div>
                    <span class="shrink-0 text-[#FF8C42] font-semibold text-sm mt-1" aria-hidden="true">→</span>
                </div>
            </a>
            @auth
                <p class="mt-3">
                    <a href="{{ route('learn.der-die-das.history') }}" wire:navigate class="text-xs font-semibold text-[#FF8C42] hover:underline">
                        View article quiz progress
                    </a>
                </p>
            @endauth
        </div>

        <div class="rounded-2xl bg-[#FFF0E0] p-5 md:p-6">
            <a href="{{ route('learn.vocabulary-meaning') }}" wire:navigate class="block group">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Vocabulary meaning</h2>
                        <p class="text-sm text-[#3D2B1F]/65 mt-1">
                            See a German word and pick or type its meaning. Multiple-choice uses same-category options; write-in uses fuzzy matching.
                        </p>
                    </div>
                    <span class="shrink-0 text-[#FF8C42] font-semibold text-sm mt-1" aria-hidden="true">→</span>
                </div>
            </a>
            @auth
                <p class="mt-3">
                    <a href="{{ route('learn.vocabulary-meaning.history') }}" wire:navigate class="text-xs font-semibold text-[#FF8C42] hover:underline">
                        View meaning quiz progress
                    </a>
                </p>
            @endauth
        </div>
    </div>
</div>
