<?php

use App\Domain\Gamification\Queries\GetQuizGamificationResult;
use function Livewire\Volt\{mount, state};

state([
    'activityType' => '',
    'sourceId' => null,
    'result' => null,
]);

$loadResult = function (GetQuizGamificationResult $query): void {
    if ($this->sourceId === null || $this->sourceId === '') {
        $this->result = [
            'state' => auth()->check() ? 'missing' : 'guest',
        ];

        return;
    }

    $data = $query->execute($this->activityType, (string) $this->sourceId, auth()->user());
    $this->result = [
        'state' => $data->state,
        'xp_total' => $data->xpTotal,
        'breakdown' => $data->breakdown,
        'streak' => $data->streak,
        'daily_goal' => $data->dailyGoal,
        'next_step' => $data->nextStep,
        'capped' => $data->capped,
        'below_minimum' => $data->belowMinimum,
    ];
};

mount(function (GetQuizGamificationResult $query, string $activityType = '', ?string $sourceId = null) {
    $this->activityType = $activityType;
    $this->sourceId = $sourceId;
    $this->loadResult($query);
});

$refreshResult = function (GetQuizGamificationResult $query) {
    $this->loadResult($query);
};

?>

<div class="mt-4 text-left" @if (($result['state'] ?? null) === 'pending') wire:poll.2s="refreshResult" @endif>
    @php($state = $result['state'] ?? 'missing')

    @if ($state === 'guest')
        <p class="text-sm text-[#3D2B1F]/70">
            Sign in to earn XP, keep a streak and join the weekly leaderboard.
        </p>
    @elseif ($state === 'pending')
        <p class="text-sm text-[#3D2B1F]/70">Calculating your rewards…</p>
    @elseif ($state === 'missing')
        {{-- Keep quiz result unaffected --}}
    @elseif ($state === 'rewarded')
        <div class="rounded-xl bg-white/70 p-4 space-y-3">
            <p class="text-2xl font-bold text-[#3D2B1F]">+{{ $result['xp_total'] }} XP</p>

            @if (! empty($result['breakdown']))
                <ul class="text-sm text-[#3D2B1F]/70 space-y-1">
                    @foreach ($result['breakdown'] as $row)
                        <li>{{ str_replace('_', ' ', ucfirst($row['reason'])) }} +{{ $row['amount'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($result['capped'] ?? false)
                <p class="text-xs text-amber-700">Daily XP limit reached.</p>
            @endif

            @if ($result['below_minimum'] ?? false)
                <p class="text-xs text-[#3D2B1F]/60">
                    Answer at least {{ config('gamification.rewards.minimum_questions', 5) }} questions to earn bonuses and keep your streak.
                </p>
            @endif

            @if ($result['daily_goal'] ?? null)
                <div class="text-sm text-[#3D2B1F]/80">
                    <p class="font-semibold">Daily goal</p>
                    <p>{{ $result['daily_goal']['earned'] }} / {{ $result['daily_goal']['target'] }} XP
                        @if ($result['daily_goal']['completed'])
                            · Completed!
                        @endif
                    </p>
                </div>
            @endif

            @if ($result['streak'] ?? null)
                <div class="text-sm text-[#3D2B1F]/80">
                    <p class="font-semibold">Current streak</p>
                    <p>{{ $result['streak']['current'] }} days</p>
                </div>
            @endif

            @if ($result['next_step'] ?? null)
                <div class="text-sm">
                    <p class="font-semibold text-[#3D2B1F]">Next challenge</p>
                    <p class="text-[#3D2B1F]/70">{{ $result['next_step']['reason'] }}</p>
                    <a href="{{ $result['next_step']['url'] }}" wire:navigate class="inline-block mt-2 text-[#FF8C42] font-semibold hover:underline">
                        Continue learning
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>
