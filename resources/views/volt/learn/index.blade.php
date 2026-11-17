<?php

use App\Domain\Gamification\Actions\ChangeDailyGoal;
use App\Domain\Gamification\Actions\SkipDailyGoalOnboarding;
use App\Domain\Gamification\Queries\GetGamificationDashboard;
use App\Domain\Gamification\Services\LeaderboardService;
use App\Domain\Gamification\Support\CompetitionPeriod;
use App\Support\Seo;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{mount, state, title};

state([
    'dashboard' => null,
    'public_leaderboard' => null,
    'needs_goal_setup' => false,
    'onboarding_goal' => 20,
    'onboarding_message' => null,
]);

mount(function (GetGamificationDashboard $dashboard, LeaderboardService $leaderboard) {
    Seo::share([
        'seoTitle' => 'Learn German',
        'seoDescription' => 'Practice German with article quizzes and vocabulary meaning quizzes. Track XP, streaks, and weekly progress.',
        'seoUrl' => route('learn.index'),
    ]);

    if (auth()->check()) {
        $this->dashboard = $dashboard->execute(auth()->user());
        $this->needs_goal_setup = (bool) ($this->dashboard['profile']['needs_goal_setup'] ?? false);
        $this->onboarding_goal = (int) ($this->dashboard['profile']['daily_goal'] ?? config('gamification.daily_goal.default', 20));
    } elseif (config('gamification.leaderboard.enabled', true) && config('gamification.leaderboard.public', false)) {
        $this->public_leaderboard = $leaderboard->forPeriod(CompetitionPeriod::forDate(), null, 10);
    }
});

title(fn () => Seo::pageTitle('Learn German'));

$applyGoal = function (ChangeDailyGoal $action, int $goal): void {
    try {
        $action->execute(auth()->user(), $goal);
        $this->needs_goal_setup = false;
        $this->dashboard = app(GetGamificationDashboard::class)->execute(auth()->user());
        $this->onboarding_message = null;
    } catch (ValidationException $e) {
        $this->onboarding_message = collect($e->errors())->flatten()->first();
    }
};

$chooseGoal = function (ChangeDailyGoal $action, int $goal) {
    $this->applyGoal($action, $goal);
};

$saveCustomGoal = function (ChangeDailyGoal $action) {
    $this->applyGoal($action, (int) $this->onboarding_goal);
};

$skipGoalSetup = function (SkipDailyGoalOnboarding $action) {
    $action->execute(auth()->user());
    $this->needs_goal_setup = false;
    $this->dashboard = app(GetGamificationDashboard::class)->execute(auth()->user());
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">German practice</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Learn</h1>
        <x-learn.nav active="dashboard" />
    </div>

    @auth
        @if ($needs_goal_setup)
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#FF8C42] via-[#FF9F5A] to-[#F06A1A] p-6 md:p-8 text-white shadow-[0_20px_50px_-24px_rgba(255,140,66,0.8)]">
                <div class="absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/15 blur-2xl" aria-hidden="true"></div>
                <div class="absolute -left-8 bottom-0 h-32 w-32 rounded-full bg-[#3D2B1F]/15 blur-2xl" aria-hidden="true"></div>
                <div class="relative space-y-5">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Quick setup</p>
                        <h2 class="mt-1 text-2xl md:text-3xl font-bold">Set a daily XP goal</h2>
                        <p class="mt-2 text-sm text-white/85 max-w-md">
                            Pick a target that fits your day. You can change it anytime in Progress → Settings.
                        </p>
                    </div>

                    @php
                        $presets = config('gamification.daily_goal.presets', ['easy' => 10, 'medium' => 20, 'hard' => 40]);
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach ($presets as $name => $xp)
                            <button
                                type="button"
                                wire:click="chooseGoal({{ (int) $xp }})"
                                class="rounded-2xl bg-white/15 hover:bg-white/25 backdrop-blur px-4 py-4 text-left transition duration-200 ring-1 ring-white/25"
                            >
                                <span class="block text-xs uppercase tracking-wide text-white/75">{{ $name }}</span>
                                <span class="mt-1 block text-2xl font-bold">{{ $xp }} XP</span>
                            </button>
                        @endforeach
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
                        <div class="flex-1">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-white/75 mb-1">Custom</label>
                            <input
                                type="number"
                                min="{{ (int) config('gamification.daily_goal.minimum', 5) }}"
                                max="{{ (int) config('gamification.daily_goal.maximum', 100) }}"
                                wire:model="onboarding_goal"
                                class="w-full rounded-xl border-0 bg-white/90 text-[#3D2B1F] px-3 py-2.5 font-semibold focus:ring-2 focus:ring-white"
                            >
                        </div>
                        <flux:button wire:click="saveCustomGoal" variant="primary" class="!bg-[#3D2B1F] hover:!bg-[#2a1d14]">
                            Use custom goal
                        </flux:button>
                    </div>

                    @if ($onboarding_message)
                        <p class="text-sm text-white font-medium">{{ $onboarding_message }}</p>
                    @endif

                    <button type="button" wire:click="skipGoalSetup" class="text-sm font-semibold text-white/85 hover:text-white underline-offset-2 hover:underline">
                        Skip for now
                    </button>
                </div>
            </section>
        @elseif ($dashboard)
            @php
                $streak = (int) $dashboard['streak']['current'];
                $atRisk = (bool) $dashboard['streak']['at_risk'];
                $goalDone = (bool) $dashboard['daily_goal']['completed'];
                $encourage = match (true) {
                    $streak === 0 => 'One short quiz today starts your streak.',
                    $atRisk => 'Practice once today to keep your streak alive.',
                    $goalDone => 'Daily goal crushed — keep the momentum going!',
                    default => 'You are building a habit. Keep going!',
                };
            @endphp

            <section class="relative overflow-hidden rounded-3xl bg-[#3D2B1F] text-[#FFF8F0] p-6 md:p-8 shadow-[0_24px_60px_-28px_rgba(61,43,31,0.65)]">
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_85%_15%,rgba(255,140,66,0.45),transparent_42%),radial-gradient(circle_at_10%_90%,rgba(255,240,224,0.12),transparent_40%)]" aria-hidden="true"></div>
                <div class="relative flex flex-col md:flex-row md:items-end md:justify-between gap-6">
                    <div class="space-y-3 min-w-0">
                        <p class="text-sm text-[#FFF0E0]/75">Welcome back, {{ $dashboard['profile']['display_name'] }}</p>
                        <div class="flex items-end gap-3">
                            <p class="text-6xl md:text-7xl font-black leading-none tracking-tight text-[#FF8C42] drop-shadow-[0_8px_24px_rgba(255,140,66,0.35)]">
                                {{ $streak }}
                            </p>
                            <div class="pb-1">
                                <p class="text-lg md:text-xl font-bold">day streak</p>
                                <p class="text-sm text-[#FFF0E0]/75">Best {{ $dashboard['streak']['longest'] }}</p>
                            </div>
                        </div>
                        <p class="text-base md:text-lg font-medium text-[#FFF0E0]">{{ $encourage }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 shrink-0">
                        <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-[11px] uppercase tracking-wide text-[#FFF0E0]/60">Lifetime</p>
                            <p class="mt-1 text-xl font-bold">{{ $dashboard['profile']['total_xp'] }} XP</p>
                        </div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur">
                            <p class="text-[11px] uppercase tracking-wide text-[#FFF0E0]/60">This week</p>
                            <p class="mt-1 text-xl font-bold">{{ $dashboard['weekly_xp'] }} XP</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-[#3D2B1F]">Daily goal</h2>
                        <p class="text-sm text-[#3D2B1F]/65 mt-1">
                            {{ $dashboard['daily_goal']['earned'] }} / {{ $dashboard['daily_goal']['target'] }} XP
                            @if ($goalDone)
                                · Done for today
                            @else
                                · {{ $dashboard['daily_goal']['remaining'] }} XP to go
                            @endif
                        </p>
                    </div>
                    <a href="{{ route('learn.settings') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline">Adjust</a>
                </div>
                <div class="mt-4 h-3 rounded-full bg-[#FFF0E0] overflow-hidden">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-[#FF8C42] to-[#F06A1A] transition-all duration-500"
                        style="width: {{ max(4, min(100, (int) $dashboard['daily_goal']['percent'])) }}%"
                    ></div>
                </div>
            </section>

            @if ($dashboard['next_step'] ?? null)
                <section class="rounded-3xl bg-gradient-to-br from-[#FFF0E0] to-[#FFE4C8] p-5 md:p-6 ring-1 ring-[#FF8C42]/15">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">Your next step</p>
                    <p class="text-xl font-bold text-[#3D2B1F] mt-1">{{ $dashboard['next_step']['title'] }}</p>
                    <p class="text-sm text-[#3D2B1F]/70 mt-1">{{ $dashboard['next_step']['description'] }}</p>
                    <p class="text-sm text-[#3D2B1F]/80 mt-2">{{ $dashboard['next_step']['reason'] }}</p>
                    <a
                        href="{{ $dashboard['next_step']['url'] }}"
                        wire:navigate
                        class="inline-flex items-center gap-2 mt-4 rounded-xl bg-[#FF8C42] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#f07d33] transition"
                    >
                        Continue learning
                        <span aria-hidden="true">→</span>
                    </a>
                </section>
            @endif

            @if ($dashboard['leaderboard'] ?? null)
                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="font-bold text-[#3D2B1F]">Weekly leaderboard</h2>
                        <a href="{{ route('learn.leaderboard') }}" wire:navigate class="text-sm text-[#FF8C42] font-semibold">View all</a>
                    </div>
                    @forelse (array_slice($dashboard['leaderboard']['entries'], 0, 5) as $entry)
                        <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3 text-sm flex justify-between shadow-sm">
                            <span class="font-medium text-[#3D2B1F]">#{{ $entry['rank'] }} {{ $entry['display_name'] }}</span>
                            <span class="font-semibold text-[#FF8C42]">{{ $entry['weekly_xp'] }} XP</span>
                        </div>
                    @empty
                        <p class="text-sm text-[#3D2B1F]/60">No rankings this week yet — be the first.</p>
                    @endforelse
                </section>
            @endif
        @endif
    @else
        <div class="rounded-3xl bg-gradient-to-br from-[#FFF0E0] to-[#FFE4C8] p-6 text-sm text-[#3D2B1F]/80 ring-1 ring-[#FF8C42]/10">
            Sign in to earn XP, keep a streak, and track your daily goal.
            <a href="{{ route('login') }}" wire:navigate class="font-semibold text-[#FF8C42]">Sign in</a>
        </div>

        @if ($public_leaderboard)
            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="font-bold text-[#3D2B1F]">Weekly leaderboard</h2>
                    <a href="{{ route('learn.leaderboard') }}" wire:navigate class="text-sm text-[#FF8C42] font-semibold">View all</a>
                </div>
                @forelse (array_slice($public_leaderboard['entries'], 0, 5) as $entry)
                    <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3 text-sm flex justify-between">
                        <span>#{{ $entry['rank'] }} {{ $entry['display_name'] }}</span>
                        <span class="font-semibold text-[#FF8C42]">{{ $entry['weekly_xp'] }} XP</span>
                    </div>
                @empty
                    <p class="text-sm text-[#3D2B1F]/60">No rankings this week yet.</p>
                @endforelse
            </section>
        @endif
    @endauth

    <div class="space-y-4">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-[#3D2B1F]/45">Practice</h2>

        <a href="{{ route('learn.der-die-das') }}" wire:navigate class="block group rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm hover:border-[#FF8C42]/40 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Der / Die / Das</h3>
                    <p class="text-sm text-[#3D2B1F]/65 mt-1">
                        Practice German noun articles. Filter by CEFR level and category, then choose der, die, or das.
                    </p>
                </div>
                <span class="shrink-0 text-[#FF8C42] font-semibold text-sm mt-1" aria-hidden="true">→</span>
            </div>
        </a>
        @auth
            <p class="-mt-2 px-1">
                <a href="{{ route('learn.der-die-das.history') }}" wire:navigate class="text-xs font-semibold text-[#FF8C42] hover:underline">
                    View article quiz history
                </a>
            </p>
        @endauth

        <a href="{{ route('learn.vocabulary-meaning') }}" wire:navigate class="block group rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 shadow-sm hover:border-[#FF8C42]/40 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h3 class="text-lg font-bold text-[#3D2B1F] group-hover:text-[#FF8C42] transition-colors">Vocabulary meaning</h3>
                    <p class="text-sm text-[#3D2B1F]/65 mt-1">
                        See a German word and pick or type its meaning. Multiple-choice or write-in with fuzzy matching.
                    </p>
                </div>
                <span class="shrink-0 text-[#FF8C42] font-semibold text-sm mt-1" aria-hidden="true">→</span>
            </div>
        </a>
        @auth
            <p class="-mt-2 px-1">
                <a href="{{ route('learn.vocabulary-meaning.history') }}" wire:navigate class="text-xs font-semibold text-[#FF8C42] hover:underline">
                    View meaning quiz history
                </a>
            </p>
        @endauth
    </div>
</div>
