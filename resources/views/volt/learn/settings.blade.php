<?php

use App\Domain\Gamification\Actions\ChangeDailyGoal;
use App\Domain\Gamification\Actions\UpdateGamificationSettings;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Domain\Gamification\Support\TimezoneOptions;
use App\Support\Seo;
use Illuminate\Validation\ValidationException;
use function Livewire\Volt\{mount, state, title};

state([
    'step' => 1,
    'daily_goal' => 250,
    'timezone' => 'Europe/Berlin',
    'show_on_leaderboard' => false,
    'leaderboard_display_name' => '',
    'timezones' => [],
    'message' => null,
]);

mount(function (GamificationProfileService $profiles) {
    Seo::share([
        'seoTitle' => 'Goals & settings',
        'seoDescription' => 'Set your daily XP goal, timezone, and leaderboard preferences.',
        'seoUrl' => route('learn.settings'),
    ]);

    $profile = $profiles->forUser(auth()->user());
    $this->daily_goal = $profile->daily_goal;
    $this->timezone = $profile->timezone;
    $this->show_on_leaderboard = $profile->show_on_leaderboard;
    $this->leaderboard_display_name = (string) ($profile->leaderboard_display_name ?? '');
    $this->timezones = TimezoneOptions::forSelect($profile->timezone);
});

title(fn () => Seo::pageTitle('Goals & settings'));

$selectPreset = function (int $goal) {
    $this->daily_goal = $goal;
};

$saveGoalAndContinue = function (ChangeDailyGoal $action) {
    try {
        $action->execute(auth()->user(), (int) $this->daily_goal);
        $this->message = null;
        $this->step = 2;
    } catch (ValidationException $e) {
        $this->message = collect($e->errors())->flatten()->first();
    }
};

$saveSettings = function (UpdateGamificationSettings $action) {
    try {
        $action->execute(auth()->user(), [
            'timezone' => $this->timezone,
            'show_on_leaderboard' => (bool) $this->show_on_leaderboard,
            'leaderboard_display_name' => $this->leaderboard_display_name,
        ]);
        $this->message = 'Settings saved.';
        $this->redirect(route('learn.progress'), navigate: true);
    } catch (ValidationException $e) {
        $this->message = collect($e->errors())->flatten()->first();
    }
};

$backToGoal = function () {
    $this->step = 1;
    $this->message = null;
};

?>

<div class="max-w-3xl mx-auto px-4 py-6 md:py-10 pb-28 md:pb-10 space-y-8">
    <div>
        <a href="{{ route('learn.progress') }}" wire:navigate class="text-sm font-semibold text-[#FF8C42] hover:underline">← Progress</a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight text-[#3D2B1F]">Goals & settings</h1>
        <p class="mt-2 text-sm text-[#3D2B1F]/60">A short two-step flow for your goal and preferences.</p>
        <x-learn.nav active="progress" />
    </div>

    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide">
        <span @class(['rounded-full px-3 py-1', 'bg-[#FF8C42] text-white' => $step === 1, 'bg-[#FFF0E0] text-[#3D2B1F]/60' => $step !== 1])>1 · Daily goal</span>
        <span class="text-[#3D2B1F]/30">→</span>
        <span @class(['rounded-full px-3 py-1', 'bg-[#FF8C42] text-white' => $step === 2, 'bg-[#FFF0E0] text-[#3D2B1F]/60' => $step !== 2])>2 · Preferences</span>
    </div>

    @if ($message)
        <p class="text-sm text-emerald-700 font-medium">{{ $message }}</p>
    @endif

    @if ($step === 1)
        @php
            $presets = config('gamification.daily_goal.presets', ['easy' => 100, 'medium' => 250, 'hard' => 500]);
        @endphp
        <section class="rounded-3xl bg-gradient-to-br from-[#FFF0E0] to-[#FFE4C8] p-5 md:p-6 space-y-5 ring-1 ring-[#FF8C42]/10">
            <div>
                <h2 class="text-xl font-bold text-[#3D2B1F]">Choose your daily XP goal</h2>
                <p class="text-sm text-[#3D2B1F]/65 mt-1">Aim for something you can finish most days.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                @foreach ($presets as $name => $xp)
                    <button
                        type="button"
                        wire:click="selectPreset({{ (int) $xp }})"
                        @class([
                            'rounded-2xl px-4 py-4 text-left transition ring-2',
                            'bg-white ring-[#FF8C42] shadow-sm' => (int) $daily_goal === (int) $xp,
                            'bg-white/60 ring-transparent hover:bg-white' => (int) $daily_goal !== (int) $xp,
                        ])
                    >
                        <span class="block text-xs uppercase tracking-wide text-[#3D2B1F]/50">{{ $name }}</span>
                        <span class="mt-1 block text-2xl font-bold text-[#3D2B1F]">{{ $xp }} XP</span>
                    </button>
                @endforeach
            </div>

            <flux:field>
                <flux:label>Custom goal</flux:label>
                <flux:input type="number" wire:model="daily_goal" min="{{ (int) config('gamification.daily_goal.minimum', 5) }}" max="{{ (int) config('gamification.daily_goal.maximum', 500) }}" />
                <flux:error name="daily_goal" />
            </flux:field>

            <flux:button wire:click="saveGoalAndContinue" variant="primary" class="bg-[#FF8C42]">
                Save goal & continue
            </flux:button>
        </section>
    @else
        <section class="rounded-3xl bg-white border border-[#3D2B1F]/10 p-5 md:p-6 space-y-5 shadow-sm">
            <div>
                <h2 class="text-xl font-bold text-[#3D2B1F]">Preferences</h2>
                <p class="text-sm text-[#3D2B1F]/65 mt-1">Timezone and how you appear on the weekly leaderboard.</p>
            </div>

            <flux:field>
                <flux:label>Timezone</flux:label>
                <flux:select wire:model="timezone">
                    @foreach ($timezones as $zone)
                        <flux:select.option value="{{ $zone['value'] }}">{{ $zone['label'] }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="timezone" />
            </flux:field>

            <flux:field>
                <flux:label>Leaderboard display name</flux:label>
                <flux:input wire:model="leaderboard_display_name" placeholder="Optional" />
                <flux:error name="leaderboard_display_name" />
            </flux:field>

            <label class="flex items-center gap-3 text-sm text-[#3D2B1F]">
                <input type="checkbox" wire:model="show_on_leaderboard" class="rounded border-[#3D2B1F]/20 text-[#FF8C42] focus:ring-[#FF8C42]">
                Show me on the weekly leaderboard
            </label>

            <div class="flex flex-wrap gap-3">
                <flux:button wire:click="backToGoal" variant="ghost">Back</flux:button>
                <flux:button wire:click="saveSettings" variant="primary" class="bg-[#FF8C42]">Save settings</flux:button>
            </div>
        </section>
    @endif
</div>
