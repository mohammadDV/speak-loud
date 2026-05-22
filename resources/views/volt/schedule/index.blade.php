<?php

use function Livewire\Volt\{state, computed};
use App\Actions\CreateSchedule;
use App\Actions\DeleteSchedule;
use App\Actions\UpdateSchedule;
use App\Models\Language;
use App\Models\Schedule;
use App\Support\ScheduleDayOfWeek;
use App\Support\ScheduleDescription;

state([
    'showModal'          => false,
    'editingScheduleId'  => null,
    'type'             => 'recurring',
    'description'      => '',
    'language_id'      => '',
    'selected_days'    => [],
    'start_time'       => '18:00',
    'end_time'         => '19:00',
    'max_participants' => 1,
    'start_datetime'   => '',
    'end_datetime'     => '',
]);

$schedules = computed(function () {
    return Schedule::query()
        ->where('user_id', auth()->id())
        ->with(['recurringRule', 'oneTimeSlot', 'language'])
        ->latest()
        ->get();
});

$languages = computed(function () {
    return Language::query()
        ->where('is_active', true)
        ->orderBy('name_en')
        ->get();
});

$resetForm = function () {
    $this->editingScheduleId = null;
    $this->type             = 'recurring';
    $this->language_id      = '';
    $this->selected_days    = [];
    $this->start_time       = '18:00';
    $this->end_time         = '19:00';
    $this->max_participants = 1;
    $this->description      = '';
    $this->start_datetime   = '';
    $this->end_datetime     = '';
};

$openModal = function () {
    $this->resetForm();
    $this->resetValidation();
    $this->showModal = true;
};

$editSchedule = function (int $scheduleId) {
    $schedule = Schedule::query()
        ->where('user_id', auth()->id())
        ->with(['recurringRule', 'oneTimeSlot'])
        ->findOrFail($scheduleId);

    $this->editingScheduleId = $schedule->id;
    $this->type              = $schedule->type;
    $this->language_id       = (string) $schedule->language_id;
    $this->max_participants  = $schedule->max_participants;
    $this->description       = $schedule->description ?? '';

    if ($schedule->type === 'recurring' && $schedule->recurringRule) {
        $this->selected_days = explode(',', $schedule->recurringRule->day_of_week);
        $this->start_time    = substr((string) $schedule->recurringRule->start_time, 0, 5);
        $this->end_time      = substr((string) $schedule->recurringRule->end_time, 0, 5);
    } elseif ($schedule->oneTimeSlot) {
        $this->start_datetime = $schedule->oneTimeSlot->start_datetime->format('Y-m-d\TH:i');
        $this->end_datetime   = $schedule->oneTimeSlot->end_datetime->format('Y-m-d\TH:i');
    }

    $this->resetValidation();
    $this->showModal = true;
};

$deleteSchedule = function (int $scheduleId, DeleteSchedule $action) {
    $action->execute(auth()->id(), $scheduleId);

    if ($this->editingScheduleId === $scheduleId) {
        $this->showModal = false;
        $this->resetForm();
    }
};

$closeModal = function () {
    $this->showModal = false;
    $this->resetForm();
};

$saveSlot = function (CreateSchedule $create, UpdateSchedule $update) {
    $rules = [
        'language_id'      => 'required|exists:languages,id',
        'type'             => 'required|in:recurring,one_time',
        'description'      => 'required|string|min:10|max:2000',
        'max_participants' => 'required|integer|min:1|max:10',
    ];

    if ($this->type === 'recurring') {
        $rules['selected_days']   = 'required|array|min:1';
        $rules['selected_days.*'] = 'in:'.implode(',', ScheduleDayOfWeek::CODES);
        $rules['start_time']      = 'required';
        $rules['end_time']        = 'required';
    } else {
        $rules['start_datetime'] = 'required|date|after_or_equal:tomorrow';
        $rules['end_datetime']   = 'required|date|after:start_datetime';
    }

    $this->validate($rules, [
        'start_datetime.after_or_equal' => 'Start time must be tomorrow or later.',
    ]);

    $payload = [
        'type'             => $this->type,
        'language_id'      => $this->language_id,
        'description'      => trim($this->description),
        'max_participants' => $this->max_participants,
    ];

    if ($this->type === 'recurring') {
        $payload['day_of_week'] = ScheduleDayOfWeek::normalize($this->selected_days);
        $payload['start_time']  = $this->start_time;
        $payload['end_time']    = $this->end_time;
    } else {
        $payload['start_datetime'] = $this->start_datetime;
        $payload['end_datetime']   = $this->end_datetime;
    }

    if ($this->editingScheduleId) {
        $update->execute(auth()->id(), $this->editingScheduleId, $payload);
    } else {
        $create->execute(auth()->id(), $payload);
    }

    $this->showModal = false;
    $this->resetForm();
};

?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#3D2B1F]">My Schedule</h1>
        <flux:button type="button" variant="primary" wire:click="openModal">+ New slot</flux:button>
    </div>

    <div class="space-y-4">
        @forelse ($this->schedules as $schedule)
            <flux:card class="bg-[#FFF0E0] p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-medium text-[#3D2B1F]">{{ $schedule->language->name_en }}</span>
                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full {{ $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]' }}">
                            {{ $schedule->type === 'recurring' ? 'Weekly' : 'One-off' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-sm text-[#3D2B1F]/50 hidden sm:inline">Max {{ $schedule->max_participants }} {{ Str::plural('claim', $schedule->max_participants) }}</span>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="editSchedule({{ $schedule->id }})">Edit</flux:button>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="danger"
                            wire:click="deleteSchedule({{ $schedule->id }})"
                            wire:confirm="Delete this slot? It will be removed from your schedule."
                        >Delete</flux:button>
                    </div>
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
                @if ($schedule->description)
                    <p class="text-sm text-[#3D2B1F]/70 mt-2 line-clamp-3">{{ $schedule->description }}</p>
                @endif
            </flux:card>
        @empty
            <p class="text-center text-[#3D2B1F]/40 py-16">No slots yet. Create your first one!</p>
        @endforelse
    </div>

    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeModal"></div>

            <div class="relative w-full max-w-md rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">{{ $editingScheduleId ? 'Edit time slot' : 'New time slot' }}</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">{{ $editingScheduleId ? 'Update your availability.' : "Set when you're free to chat." }}</p>

                    <form wire:submit="saveSlot" class="mt-6 space-y-5">
                        @if (! $editingScheduleId)
                            <div>
                                <label class="text-sm font-medium text-[#3D2B1F] block mb-2">Repeat</label>
                                <div class="flex gap-2">
                                    <flux:button type="button" wire:click="$set('type', 'recurring')"
                                        variant="{{ $type === 'recurring' ? 'primary' : 'ghost' }}">Every week</flux:button>
                                    <flux:button type="button" wire:click="$set('type', 'one_time')"
                                        variant="{{ $type === 'one_time' ? 'primary' : 'ghost' }}">One-off</flux:button>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-[#3D2B1F]/60">
                                {{ $type === 'recurring' ? 'Weekly slot' : 'One-off slot' }} (type cannot be changed).
                            </p>
                        @endif

                        <flux:select wire:model="language_id" label="Practice language">
                            <flux:select.option value="">Select language</flux:select.option>
                            @foreach ($this->languages as $lang)
                                <flux:select.option value="{{ $lang->id }}">{{ $lang->name_en }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        @if ($type === 'recurring')
                            <fieldset>
                                <legend class="text-sm font-medium text-[#3D2B1F] mb-2">Days</legend>
                                <div class="flex flex-wrap gap-2">
                                    @foreach (ScheduleDayOfWeek::CODES as $code)
                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#FF8C42]/30 bg-[#FFF0E0] px-3 py-2 text-sm text-[#3D2B1F] has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/15">
                                            <input type="checkbox" wire:model="selected_days" value="{{ $code }}" class="rounded border-[#FF8C42]/50 text-[#FF8C42] focus:ring-[#FF8C42]" />
                                            {{ $code }}
                                        </label>
                                    @endforeach
                                </div>
                                @error('selected_days') <p class="mt-1 text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                            </fieldset>
                            <div class="flex gap-3">
                                <flux:input wire:model="start_time" label="Start" type="time" class="flex-1" />
                                <flux:input wire:model="end_time" label="End" type="time" class="flex-1" />
                            </div>
                        @else
                            @php $minStart = now()->addDay()->startOfDay()->format('Y-m-d\TH:i'); @endphp
                            <flux:input wire:model="start_datetime" label="Start" type="datetime-local" min="{{ $minStart }}" />
                            <flux:input wire:model="end_datetime" label="End" type="datetime-local" min="{{ $minStart }}" />
                            @error('start_datetime') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                        @endif

                        <flux:textarea
                            wire:model="description"
                            label="Session rules & notes"
                            rows="4"
                            placeholder="{{ ScheduleDescription::PLACEHOLDER }}"
                            description="Tell partners how the session works (format, language split, tools, expectations)."
                        />

                        <flux:input wire:model="max_participants" label="Max claims" type="number" min="1" max="10" />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">{{ $editingScheduleId ? 'Update slot' : 'Save slot' }}</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
