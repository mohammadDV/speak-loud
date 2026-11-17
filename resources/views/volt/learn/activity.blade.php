<?php

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Queries\GetXpHistory;
use App\Support\Seo;
use function Livewire\Volt\{mount, state, title};

state([
    'history' => [],
    'recent_days' => [],
]);

mount(function (GetXpHistory $history) {
    Seo::share([
        'seoTitle' => 'Activity & XP history',
        'seoDescription' => 'Your recent daily learning activity and XP transaction history.',
        'seoUrl' => route('learn.activity'),
    ]);

    $this->history = $history->execute(auth()->user(), 40)->through(fn ($tx) => [
        'amount' => $tx->amount,
        'reason' => $tx->reason->value,
        'created_at' => $tx->created_at?->toDateTimeString(),
        'local_date' => $tx->local_date?->toDateString(),
    ])->items();

    $this->recent_days = UserDailyActivity::query()
        ->where('user_id', auth()->id())
        ->orderByDesc('activity_date')
        ->limit(30)
        ->get()
        ->map(fn (UserDailyActivity $day) => [
            'date' => $day->activity_date->toDateString(),
            'xp_earned' => $day->xp_earned,
            'bonus_xp_earned' => $day->bonus_xp_earned,
            'daily_goal' => $day->daily_goal,
            'completed' => $day->goal_completed_at !== null,
        ])
        ->all();
});

title(fn () => Seo::pageTitle('Activity & XP history'));

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <a href="{{ route('learn.progress') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline">← Progress</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-[#3D2B1F]">Activity & XP</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/60">Daily practice totals and every XP reward you have earned.</p>
        <x-learn.nav active="progress" />
    </div>

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-bold text-[#3D2B1F]">Recent daily activity</h2>
            <p class="text-sm text-[#3D2B1F]/55">How much XP you earned each day toward your goal.</p>
        </div>
        @forelse ($recent_days as $day)
            <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3 text-sm flex items-center justify-between gap-3 shadow-sm">
                <div>
                    <p class="font-semibold text-[#3D2B1F]">{{ $day['date'] }}</p>
                    <p class="text-xs text-[#3D2B1F]/55 mt-0.5">Goal {{ $day['daily_goal'] }} XP</p>
                </div>
                <div class="text-right">
                    <p class="font-bold text-[#FF8C42]">{{ $day['xp_earned'] + $day['bonus_xp_earned'] }} XP</p>
                    @if ($day['completed'])
                        <p class="text-xs font-semibold text-emerald-700 mt-0.5">Goal done</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-2xl bg-[#FFF0E0] px-4 py-6 text-sm text-[#3D2B1F]/70">
                No daily activity yet. Complete a quiz to start your record.
            </div>
        @endforelse
    </section>

    <section class="space-y-3">
        <div>
            <h2 class="text-lg font-bold text-[#3D2B1F]">XP history</h2>
            <p class="text-sm text-[#3D2B1F]/55">Each reward from quizzes, milestones, streaks, and bonuses.</p>
        </div>
        @forelse ($history as $row)
            <div class="rounded-2xl bg-white border border-[#3D2B1F]/10 px-4 py-3 text-sm flex justify-between gap-3 shadow-sm">
                <div>
                    <p class="font-medium text-[#3D2B1F]">{{ str_replace('_', ' ', ucfirst($row['reason'])) }}</p>
                    @if ($row['local_date'])
                        <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $row['local_date'] }}</p>
                    @endif
                </div>
                <span class="font-bold {{ $row['amount'] >= 0 ? 'text-[#FF8C42]' : 'text-red-600' }}">
                    {{ $row['amount'] > 0 ? '+' : '' }}{{ $row['amount'] }} XP
                </span>
            </div>
        @empty
            <div class="rounded-2xl bg-[#FFF0E0] px-4 py-6 text-sm text-[#3D2B1F]/70">
                No XP yet. Finish a quiz with at least a few correct answers to earn your first rewards.
            </div>
        @endforelse
    </section>
</div>
