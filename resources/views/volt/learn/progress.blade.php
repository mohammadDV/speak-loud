<?php

use App\Domain\Gamification\Queries\GetGamificationDashboard;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'dashboard' => null,
]);

mount(function (GetGamificationDashboard $dashboard) {
    Seo::share([
        'seoTitle' => 'Learning progress',
        'seoDescription' => 'Review your XP history, daily activity, goals, and learning settings.',
        'seoUrl' => route('learn.progress'),
    ]);

    $this->dashboard = $dashboard->execute(auth()->user());
});

title(fn () => Seo::pageTitle('Learning progress'));

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">German practice</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Progress</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/60">Your learning record, goals, and preferences.</p>
        <x-learn.nav active="progress" />
    </div>

    @if ($dashboard)
        <section class="grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-[#3D2B1F] text-[#FFF8F0] p-4">
                <p class="text-[11px] uppercase tracking-wide text-white/60">Lifetime XP</p>
                <p class="mt-1 text-2xl font-bold text-[#FF8C42]">{{ $dashboard['profile']['total_xp'] }}</p>
            </div>
            <div class="rounded-2xl bg-[#FFF0E0] p-4">
                <p class="text-[11px] uppercase tracking-wide text-[#3D2B1F]/50">Streak</p>
                <p class="mt-1 text-2xl font-bold text-[#3D2B1F]">{{ $dashboard['streak']['current'] }} days</p>
            </div>
        </section>
    @endif

    <div class="space-y-4">
        <a href="{{ route('learn.activity') }}" wire:navigate class="block group rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm hover:border-[#FF8C42]/40 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">History</p>
                    <h2 class="mt-1 text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Activity & XP</h2>
                    <p class="mt-1 text-sm text-[#3D2B1F]/65">
                        See recent daily activity and a clear XP history of every reward you earned.
                    </p>
                </div>
                <span class="text-[#FF8C42] font-semibold" aria-hidden="true">→</span>
            </div>
        </a>

        <a href="{{ route('learn.settings') }}" wire:navigate class="block group rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm hover:border-[#FF8C42]/40 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">Preferences</p>
                    <h2 class="mt-1 text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Goals & settings</h2>
                    <p class="mt-1 text-sm text-[#3D2B1F]/65">
                        Update your daily XP goal, timezone, and leaderboard visibility in a short guided flow.
                    </p>
                </div>
                <span class="text-[#FF8C42] font-semibold" aria-hidden="true">→</span>
            </div>
        </a>
    </div>
</div>
