<?php

use function Livewire\Volt\{state, mount, rules};
use App\Actions\CreateSchedule;
use App\Models\Language;
use App\Models\Schedule;

state([
    'schedules'   => [],
    'languages'   => [],
    'showModal'   => false,
    'type'        => 'recurring',
    'language_id' => '',
    'day_of_week' => '',
    'start_time'  => '18:00',
    'end_time'    => '19:00',
    'max_participants' => 1,
    'start_datetime' => '',
    'end_datetime'   => '',
]);

mount(function () {
    $this->languages = Language::where('is_active', true)->orderBy('name_en')->get();
    $this->schedules = Schedule::where('user_id', auth()->id())
        ->with(['recurringRule', 'oneTimeSlot', 'language'])
        ->latest()
        ->get();
});

$saveSlot = function (CreateSchedule $action) {
    $this->validate([
        'language_id' => 'required',
        'type'        => 'required|in:recurring,one_time',
    ]);

    $action->execute(auth()->id(), [
        'type'             => $this->type,
        'language_id'      => $this->language_id,
        'max_participants' => $this->max_participants,
        'day_of_week'      => $this->day_of_week,
        'start_time'       => $this->start_time,
        'end_time'         => $this->end_time,
        'start_datetime'   => $this->start_datetime,
        'end_datetime'     => $this->end_datetime,
    ]);

    $this->showModal = false;
    $this->schedules = Schedule::where('user_id', auth()->id())
        ->with(['recurringRule', 'oneTimeSlot', 'language'])
        ->latest()
        ->get();
};

?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#3D2B1F]">My Schedule</h1>
        <flux:button variant="primary" wire:click="$set('showModal', true)">+ New slot</flux:button>
    </div>

    <div class="space-y-4">
        @forelse ($schedules as $schedule)
            <flux:card class="bg-[#FFF0E0] p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-[#3D2B1F]">{{ $schedule->language->name_en }}</span>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full {{ $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]' }}">
                            {{ $schedule->type === 'recurring' ? 'Weekly' : 'One-off' }}
                        </span>
                    </div>
                    <span class="text-sm text-[#3D2B1F]/50">Max {{ $schedule->max_participants }} {{ Str::plural('claim', $schedule->max_participants) }}</span>
                </div>
                @if ($schedule->recurringRule)
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">
                        {{ $schedule->recurringRule->day_of_week }} · {{ $schedule->recurringRule->start_time }} – {{ $schedule->recurringRule->end_time }}
                    </p>
                @elseif ($schedule->oneTimeSlot)
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">
                        {{ $schedule->oneTimeSlot->start_datetime->format('D, M j · H:i') }} – {{ $schedule->oneTimeSlot->end_datetime->format('H:i') }}
                    </p>
                @endif
            </flux:card>
        @empty
            <p class="text-center text-[#3D2B1F]/40 py-16">No slots yet. Create your first one!</p>
        @endforelse
    </div>

    <flux:modal wire:model="showModal" class="max-w-md">
        <flux:heading>New time slot</flux:heading>
        <flux:text>Set when you're free to chat.</flux:text>

        <form wire:submit="saveSlot" class="mt-6 space-y-5">
            <div>
                <label class="text-sm font-medium text-[#3D2B1F] block mb-2">Repeat</label>
                <div class="flex gap-2">
                    <flux:button type="button" wire:click="$set('type', 'recurring')"
                        variant="{{ $type === 'recurring' ? 'primary' : 'ghost' }}">Every week</flux:button>
                    <flux:button type="button" wire:click="$set('type', 'one_time')"
                        variant="{{ $type === 'one_time' ? 'primary' : 'ghost' }}">One-off</flux:button>
                </div>
            </div>

            <flux:select wire:model="language_id" label="Practice language">
                <flux:select.option value="">Select language</flux:select.option>
                @foreach ($languages as $lang)
                    <flux:select.option value="{{ $lang->id }}">{{ $lang->name_en }}</flux:select.option>
                @endforeach
            </flux:select>

            @if ($type === 'recurring')
                <flux:input wire:model="day_of_week" label="Days (e.g. Sat,Sun)" placeholder="Mon,Wed,Fri" />
                <div class="flex gap-3">
                    <flux:input wire:model="start_time" label="Start" type="time" class="flex-1" />
                    <flux:input wire:model="end_time" label="End" type="time" class="flex-1" />
                </div>
            @else
                <flux:input wire:model="start_datetime" label="Start" type="datetime-local" />
                <flux:input wire:model="end_datetime" label="End" type="datetime-local" />
            @endif

            <flux:input wire:model="max_participants" label="Max claims" type="number" min="1" max="10" />

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showModal', false)" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Save slot</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
