<?php

use App\Domain\Gamification\Services\LeaderboardService;
use App\Domain\Gamification\Support\CompetitionPeriod;
use App\Support\Seo;
use Carbon\CarbonImmutable;
use function Livewire\Volt\{mount, state, title};

state([
    'entries' => [],
    'participants' => 0,
    'viewer' => null,
    'week_label' => '',
    'prev_week' => null,
    'next_week' => null,
    'is_current_week' => true,
]);

mount(function (LeaderboardService $leaderboard) {
    abort_unless(config('gamification.leaderboard.enabled', true), 404);

    if (! config('gamification.leaderboard.public', false) && ! auth()->check()) {
        $this->redirect(route('login'), navigate: true);

        return;
    }

    $timezone = (string) config('gamification.competition_timezone', 'Europe/Berlin');
    $weekParam = request()->query('week');
    $current = CompetitionPeriod::forDate();

    if (is_string($weekParam) && $weekParam !== '') {
        $start = CarbonImmutable::parse($weekParam, $timezone)->startOfDay();
        abort_unless($start->isMonday(), 404);
        $period = CompetitionPeriod::forWeekStart($start);
    } else {
        $period = $current;
    }

    Seo::share([
        'seoTitle' => 'Weekly leaderboard',
        'seoDescription' => 'Friendly weekly XP competition for learners.',
        'seoUrl' => route('learn.leaderboard'),
    ]);

    $data = $leaderboard->forPeriod($period, auth()->user(), 10);
    $this->entries = $data['entries'];
    $this->participants = $data['participants'];
    $this->viewer = $data['viewer'];
    $this->week_label = $period->startLocal->toFormattedDateString().' – '.$period->startLocal->addDays(6)->toFormattedDateString();
    $this->prev_week = $period->startLocal->subWeek()->toDateString();
    $this->next_week = $period->startLocal->addWeek()->toDateString();
    $this->is_current_week = $period->startLocal->toDateString() === $current->startLocal->toDateString();
});

title(fn () => Seo::pageTitle('Weekly leaderboard'));

?>

@php
    $topThree = array_slice($entries, 0, 3);
    $rest = array_slice($entries, 3);
    $podiumOrder = [];
    if (isset($topThree[1])) {
        $podiumOrder[] = ['entry' => $topThree[1], 'place' => 2];
    }
    if (isset($topThree[0])) {
        $podiumOrder[] = ['entry' => $topThree[0], 'place' => 1];
    }
    if (isset($topThree[2])) {
        $podiumOrder[] = ['entry' => $topThree[2], 'place' => 3];
    }
@endphp

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-[#FF8C42] mb-1">German practice</p>
        <h1 class="text-3xl md:text-4xl font-bold tracking-tight text-[#3D2B1F]">Leaderboard</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/60">Friendly weekly XP race. Climb the board one quiz at a time.</p>
        <x-learn.nav active="leaderboard" />
    </div>

    <section class="relative overflow-hidden rounded-3xl bg-[#3D2B1F] text-[#FFF8F0] p-6 md:p-8">
        <div
            class="pointer-events-none absolute inset-0 opacity-90"
            style="background: radial-gradient(circle at 88% 12%, rgba(255,140,66,0.5), transparent 42%), radial-gradient(circle at 8% 88%, rgba(255,240,224,0.12), transparent 40%);"
            aria-hidden="true"
        ></div>
        <div class="relative flex flex-col sm:flex-row sm:items-end sm:justify-between gap-5">
            <div class="space-y-2 min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">
                    {{ $is_current_week ? 'This week' : 'Past week' }}
                </p>
                <h2 class="text-2xl md:text-3xl font-bold tracking-tight">{{ $week_label }}</h2>
                <p class="text-sm text-[#FFF0E0]/75">
                    {{ $participants }} {{ $participants === 1 ? 'learner' : 'learners' }} competing
                </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a
                    href="{{ route('learn.leaderboard', ['week' => $prev_week]) }}"
                    wire:navigate
                    class="rounded-xl bg-white/10 hover:bg-white/20 px-3.5 py-2 text-sm font-semibold transition"
                >
                    ← Prev
                </a>
                @unless ($is_current_week)
                    <a
                        href="{{ route('learn.leaderboard') }}"
                        wire:navigate
                        class="rounded-xl bg-[#FF8C42] hover:bg-[#f07d33] px-3.5 py-2 text-sm font-bold text-white transition"
                    >
                        This week
                    </a>
                @endunless
                <a
                    href="{{ route('learn.leaderboard', ['week' => $next_week]) }}"
                    wire:navigate
                    class="rounded-xl bg-white/10 hover:bg-white/20 px-3.5 py-2 text-sm font-semibold transition"
                >
                    Next →
                </a>
            </div>
        </div>
    </section>

    @auth
        @if ($viewer)
            <section class="rounded-3xl border border-[#3D2B1F]/10 bg-white p-5 md:p-6 shadow-sm">
                @if ($viewer['hidden'])
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">Your standing</p>
                    <p class="mt-1 text-lg font-bold text-[#3D2B1F]">You are hidden from the board</p>
                    <p class="mt-1 text-sm text-[#3D2B1F]/65">Turn on visibility anytime in Goals & settings.</p>
                    <a href="{{ route('learn.settings') }}" wire:navigate class="inline-flex mt-4 text-sm font-bold text-[#FF8C42] hover:underline">
                        Open settings →
                    </a>
                @elseif ($viewer['rank'] === null)
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">Your standing</p>
                    <p class="mt-1 text-lg font-bold text-[#3D2B1F]">Not ranked yet this week</p>
                    <p class="mt-1 text-sm text-[#3D2B1F]/65">Earn XP from a quiz to join the race.</p>
                    <a href="{{ route('learn.index') }}" wire:navigate class="inline-flex items-center gap-2 mt-4 rounded-xl bg-[#FF8C42] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#f07d33] transition">
                        Start practicing
                        <span aria-hidden="true">→</span>
                    </a>
                @else
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-[#FF8C42]">Your standing</p>
                            <div class="mt-1 flex items-end gap-3">
                                <p class="text-5xl font-black leading-none text-[#FF8C42]">#{{ $viewer['rank'] }}</p>
                                <div class="pb-1">
                                    <p class="text-lg font-bold text-[#3D2B1F]">{{ $viewer['weekly_xp'] }} XP this week</p>
                                    @if ($viewer['gap'] !== null)
                                        <p class="text-sm text-[#3D2B1F]/65">{{ $viewer['gap'] }} XP to reach #{{ $viewer['next_rank'] }}</p>
                                    @elseif ($viewer['rank'] === 1)
                                        <p class="text-sm text-[#3D2B1F]/65">You are leading the week!</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if ($viewer['rank'] !== 1)
                            <a href="{{ route('learn.index') }}" wire:navigate class="inline-flex items-center justify-center rounded-xl bg-[#FFF0E0] px-4 py-2.5 text-sm font-bold text-[#3D2B1F] hover:bg-[#FFE4C8] transition">
                                Climb higher →
                            </a>
                        @endif
                    </div>
                @endif
            </section>
        @endif
    @endauth

    @if ($entries === [])
        <section class="rounded-3xl bg-gradient-to-br from-[#FFF0E0] to-[#FFE4C8] p-8 text-center ring-1 ring-[#FF8C42]/10">
            <p class="text-2xl font-bold text-[#3D2B1F]">The board is open</p>
            <p class="mt-2 text-sm text-[#3D2B1F]/70 max-w-sm mx-auto">
                No rankings yet for this week. Be the first to earn XP and claim the top spot.
            </p>
            @auth
                <a href="{{ route('learn.index') }}" wire:navigate class="inline-flex mt-5 rounded-xl bg-[#FF8C42] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#f07d33] transition">
                    Earn XP now →
                </a>
            @else
                <a href="{{ route('login') }}" wire:navigate class="inline-flex mt-5 rounded-xl bg-[#FF8C42] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#f07d33] transition">
                    Sign in to compete →
                </a>
            @endauth
        </section>
    @else
        @if ($topThree !== [])
            <section>
                <div class="flex items-end justify-center gap-2 sm:gap-4 min-h-[11rem] sm:min-h-[13rem]">
                    @foreach ($podiumOrder as $slot)
                        @php
                            $place = $slot['place'];
                            $entry = $slot['entry'];
                            $heights = [1 => 'h-36 sm:h-44', 2 => 'h-28 sm:h-36', 3 => 'h-24 sm:h-32'];
                            $accents = [
                                1 => 'from-[#FF8C42] to-[#F06A1A] text-white ring-[#FF8C42]/40',
                                2 => 'from-[#FFF0E0] to-[#FFE4C8] text-[#3D2B1F] ring-[#3D2B1F]/10',
                                3 => 'from-[#FFF8F0] to-[#FFF0E0] text-[#3D2B1F] ring-[#3D2B1F]/10',
                            ];
                        @endphp
                        <div class="flex-1 max-w-[9rem] flex flex-col items-center justify-end">
                            <div class="mb-2 text-center px-1">
                                <p class="text-xs font-semibold uppercase tracking-wide text-[#3D2B1F]/45">#{{ $place }}</p>
                                <p class="mt-0.5 text-sm font-bold text-[#3D2B1F] truncate w-full" title="{{ $entry['display_name'] }}">
                                    {{ $entry['display_name'] }}
                                </p>
                            </div>
                            <div @class([
                                'w-full rounded-t-3xl rounded-b-xl bg-gradient-to-b ring-1 flex flex-col items-center justify-end pb-4 pt-6 px-2 shadow-sm',
                                $heights[$place],
                                $accents[$place],
                            ])>
                                <p @class([
                                    'text-2xl sm:text-3xl font-black leading-none',
                                    'text-white' => $place === 1,
                                    'text-[#FF8C42]' => $place !== 1,
                                ])>{{ $entry['weekly_xp'] }}</p>
                                <p @class([
                                    'mt-1 text-[11px] font-semibold uppercase tracking-wide',
                                    'text-white/80' => $place === 1,
                                    'text-[#3D2B1F]/50' => $place !== 1,
                                ])>XP</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($rest !== [])
            <section class="space-y-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#3D2B1F]/45">Full rankings</h2>
                @foreach ($rest as $entry)
                    <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3.5 flex items-center justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF0E0] text-sm font-bold text-[#3D2B1F]">
                                #{{ $entry['rank'] }}
                            </span>
                            <span class="font-semibold text-[#3D2B1F] truncate">{{ $entry['display_name'] }}</span>
                        </div>
                        <span class="shrink-0 font-bold text-[#FF8C42]">{{ $entry['weekly_xp'] }} XP</span>
                    </div>
                @endforeach
            </section>
        @elseif (count($topThree) > 0 && count($topThree) < 4)
            {{-- Top three only: list them for accessibility on small screens too --}}
            <section class="space-y-2 sm:hidden">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-[#3D2B1F]/45">Rankings</h2>
                @foreach ($topThree as $entry)
                    <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3 flex items-center justify-between gap-3 shadow-sm">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#FFF0E0] text-sm font-bold text-[#3D2B1F]">
                                #{{ $entry['rank'] }}
                            </span>
                            <span class="font-semibold text-[#3D2B1F] truncate">{{ $entry['display_name'] }}</span>
                        </div>
                        <span class="shrink-0 font-bold text-[#FF8C42]">{{ $entry['weekly_xp'] }} XP</span>
                    </div>
                @endforeach
            </section>
        @endif
    @endif
</div>
