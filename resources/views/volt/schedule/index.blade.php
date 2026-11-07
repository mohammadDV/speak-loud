<?php

use function Livewire\Volt\{state, computed, mount, title, updated};
use App\Actions\CreateSchedule;
use App\Actions\DeleteSchedule;
use App\Actions\UpdateSchedule;
use App\Models\Schedule;
use App\Support\LookupCache;
use App\Support\ScheduleDayOfWeek;
use App\Support\ScheduleDescription;
use App\Support\ScheduleLimits;
use App\Support\ScheduleTime;
use App\Support\Seo;
use App\Support\UtcTime;
use App\Support\ViewerTimezone;
use Carbon\Carbon;

state([
    'showModal'          => false,
    'editingScheduleId'  => null,
    'type'             => 'recurring',
    'title'            => '',
    'description'      => '',
    'language_id'      => '',
    'selected_days'    => [],
    'start_time'       => '18:00',
    'end_time'         => '19:00',
    'start_hour'       => '18',
    'start_minute'     => '00',
    'end_hour'         => '19',
    'end_minute'       => '00',
    'slot_date'        => '',
    'max_participants'     => 1,
    'min_max_participants' => 1,
    'start_datetime'   => '',
    'end_datetime'     => '',
]);

mount(function () {
    Seo::share([
        'seoTitle' => 'My schedule',
        'seoDescription' => 'Manage your SpeakLoudMate practice slots.',
        'seoUrl' => route('schedule'),
        'seoRobots' => 'noindex, nofollow',
    ]);
});

title(fn () => Seo::pageTitle('My schedule'));

$schedules = computed(function () {
    return Schedule::query()
        ->where('user_id', auth()->id())
        ->with(['recurringRule', 'oneTimeSlot', 'language'])
        ->latest()
        ->get();
});

$languages = computed(fn () => LookupCache::activeLanguages());

$maxSlots = computed(fn () => ScheduleLimits::maxSlotsForUser());

$canCreateSlot = computed(fn () => $this->schedules->count() < $this->maxSlots);

$viewerTimezone = computed(fn () => ViewerTimezone::current());

$viewerTimezoneLabel = computed(fn () => ViewerTimezone::label($this->viewerTimezone));

$syncPartsFromRecurringTimes = function () {
    [$this->start_hour, $this->start_minute] = UtcTime::split($this->start_time);
    [$this->end_hour, $this->end_minute] = UtcTime::split($this->end_time);
};

$syncPartsFromDatetimes = function () {
    if ($this->start_datetime !== '') {
        $this->slot_date = UtcTime::dateFromDatetimeLocal($this->start_datetime);
        [$this->start_hour, $this->start_minute] = UtcTime::split(UtcTime::timeFromDatetimeLocal($this->start_datetime));
    }

    if ($this->end_datetime !== '') {
        [$this->end_hour, $this->end_minute] = UtcTime::split(UtcTime::timeFromDatetimeLocal($this->end_datetime));

        if ($this->slot_date === '') {
            $this->slot_date = UtcTime::dateFromDatetimeLocal($this->end_datetime);
        }
    }
};

$composeTimesFromParts = function () {
    $this->start_time = UtcTime::join($this->start_hour, $this->start_minute);
    $this->end_time = UtcTime::join($this->end_hour, $this->end_minute);

    if ($this->slot_date !== '') {
        $this->start_datetime = UtcTime::composeDatetimeLocal($this->slot_date, $this->start_time);
        $this->end_datetime = UtcTime::composeDatetimeLocal($this->slot_date, $this->end_time);
    }
};

updated([
    'start_time' => function () {
        $this->syncPartsFromRecurringTimes();
    },
    'end_time' => function () {
        $this->syncPartsFromRecurringTimes();
    },
    'start_datetime' => function () {
        $this->syncPartsFromDatetimes();
    },
    'end_datetime' => function () {
        $this->syncPartsFromDatetimes();
    },
    'start_hour' => function () {
        $this->composeTimesFromParts();
    },
    'start_minute' => function () {
        $this->composeTimesFromParts();
    },
    'end_hour' => function () {
        $this->composeTimesFromParts();
    },
    'end_minute' => function () {
        $this->composeTimesFromParts();
    },
    'slot_date' => function () {
        $this->composeTimesFromParts();
    },
]);

$resetForm = function () {
    $this->editingScheduleId = null;
    $this->type             = 'recurring';
    $this->language_id      = '';
    $this->selected_days    = [];
    $this->start_time       = '18:00';
    $this->end_time         = '19:00';
    $this->start_hour       = '18';
    $this->start_minute     = '00';
    $this->end_hour         = '19';
    $this->end_minute       = '00';
    $this->slot_date        = '';
    $this->max_participants     = 1;
    $this->min_max_participants = 1;
    $this->title            = '';
    $this->description      = '';
    $this->start_datetime   = '';
    $this->end_datetime     = '';
};

$openModal = function () {
    if (! $this->canCreateSlot) {
        return;
    }

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
    $acceptedClaims = $schedule->claims()->where('status', 'accepted')->count();

    $this->max_participants     = $schedule->max_participants;
    $this->min_max_participants = max(1, $acceptedClaims);
    $this->title             = $schedule->title ?? '';
    $this->description       = $schedule->description ?? '';

    $viewerTz = ViewerTimezone::current();

    if ($schedule->type === 'recurring' && $schedule->recurringRule) {
        $converted = ScheduleTime::convertRecurringWallClock(
            explode(',', $schedule->recurringRule->day_of_week),
            substr((string) $schedule->recurringRule->start_time, 0, 5),
            substr((string) $schedule->recurringRule->end_time, 0, 5),
            $schedule->recurringRule->timezone ?: 'UTC',
            $viewerTz,
        );
        $this->selected_days = $converted['days'];
        $this->start_time = $converted['start'];
        $this->end_time = $converted['end'];
        $this->syncPartsFromRecurringTimes();
        $this->slot_date = '';
        $this->start_datetime = '';
        $this->end_datetime = '';
    } elseif ($schedule->oneTimeSlot) {
        $localStart = $schedule->oneTimeSlot->start_datetime->copy()->timezone($viewerTz);
        $localEnd = $schedule->oneTimeSlot->end_datetime->copy()->timezone($viewerTz);
        $this->slot_date = $localStart->toDateString();
        $this->start_time = $localStart->format('H:i');
        $this->end_time = $localEnd->format('H:i');
        $this->syncPartsFromRecurringTimes();
        $this->composeTimesFromParts();
    }

    $this->resetValidation();
    $this->showModal = true;
};

$refreshSchedules = function () {
    unset($this->schedules);
    unset($this->canCreateSlot);
};

$deleteSchedule = function (int $scheduleId, DeleteSchedule $action) {
    $schedule = Schedule::findOrFail($scheduleId);
    $this->authorize('delete', $schedule);
    $action->execute(auth()->id(), $scheduleId);

    $this->refreshSchedules();

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
    $this->composeTimesFromParts();

    if ($this->type === 'one_time' && $this->slot_date === '' && $this->start_datetime !== '') {
        $this->syncPartsFromDatetimes();
        $this->composeTimesFromParts();
    }

    $rules = [
        'language_id'      => 'required|exists:languages,id',
        'type'             => 'required|in:recurring,one_time',
        'title'            => 'required|string|min:1|max:200',
        'description'      => 'required|string|min:10|max:2000',
        'max_participants' => 'required|integer|min:1|max:10',
    ];

    if ($this->type === 'recurring') {
        $rules['selected_days']   = 'required|array|min:1';
        $rules['selected_days.*'] = 'in:'.implode(',', ScheduleDayOfWeek::CODES);
        $rules['start_time']      = 'required';
        $rules['end_time']        = 'required';
    } else {
        $rules['slot_date']      = 'required|date';
        $rules['start_datetime'] = 'required|date';
        $rules['end_datetime']   = 'required|date';
    }

    $this->validate($rules);

    $payload = [
        'type'             => $this->type,
        'language_id'      => $this->language_id,
        'title'            => trim($this->title),
        'description'      => trim($this->description),
        'max_participants' => $this->max_participants,
    ];

    $viewerTz = ViewerTimezone::current();

    if ($this->type === 'recurring') {
        if ($this->start_time >= $this->end_time) {
            $this->addError('end_time', 'End time must be after the start time.');

            return;
        }

        $payload['day_of_week'] = ScheduleDayOfWeek::normalize($this->selected_days);
        $payload['start_time']  = $this->start_time;
        $payload['end_time']    = $this->end_time;
        $payload['timezone']    = $viewerTz;
    } else {
        $start = Carbon::parse($this->slot_date.' '.$this->start_time, $viewerTz);
        $end = Carbon::parse($this->slot_date.' '.$this->end_time, $viewerTz);

        // Editing in another TZ can surface a slot that crosses local midnight
        // (same wall times on one date). Treat end-before-start as next day.
        if ($end->lte($start)) {
            $end->addDay();
        }

        $minStart = Carbon::now($viewerTz)->addDay()->startOfDay();

        if ($start->lt($minStart)) {
            $this->addError('start_datetime', 'Start time must be tomorrow or later in your timezone.');

            return;
        }

        if ($end->lte($start)) {
            $this->addError('end_datetime', 'End time must be after the start time.');

            return;
        }

        // New slots must stay on one local calendar day; edits may keep a
        // cross-midnight range that only appears after timezone conversion.
        if (! $this->editingScheduleId && ! $start->isSameDay($end)) {
            $this->addError('end_datetime', 'End must be on the same day as the start in your timezone.');

            return;
        }

        if ($start->diffInMinutes($end) < 15) {
            $this->addError('end_datetime', 'The slot must be at least 15 minutes long.');

            return;
        }

        $payload['start_datetime'] = $start->utc();
        $payload['end_datetime']   = $end->utc();
    }

    if ($this->editingScheduleId) {
        $schedule = Schedule::findOrFail($this->editingScheduleId);
        $this->authorize('update', $schedule);
        $update->execute(auth()->id(), $this->editingScheduleId, $payload);
    } else {
        if (! $this->canCreateSlot) {
            $this->addError('title', ScheduleLimits::limitReachedMessage());

            return;
        }

        $create->execute(auth()->id(), $payload);
    }

    $this->refreshSchedules();

    $this->showModal = false;
    $this->resetForm();
};

?>

<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-6">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-[#3D2B1F]">My Schedule</h1>
            <p class="text-sm text-[#3D2B1F]/50 mt-1">
                {{ $this->schedules->count() }} of {{ $this->maxSlots }} slots used · Times shown in {{ $this->viewerTimezone }}.
            </p>
        </div>
        @if ($this->canCreateSlot)
            <flux:button type="button" variant="primary" wire:click="openModal" class="w-full sm:w-auto shrink-0">+ New slot</flux:button>
        @else
            <flux:button type="button" variant="primary" disabled title="{{ ScheduleLimits::limitReachedMessage() }}" class="w-full sm:w-auto shrink-0">+ New slot</flux:button>
        @endif
    </div>

    <div class="space-y-4">
        @forelse ($this->schedules as $schedule)
            <flux:card wire:key="schedule-{{ $schedule->id }}" class="bg-[#FFF0E0] p-4">
                <div class="flex flex-col gap-3">
                    <div class="min-w-0">
                        @if ($schedule->title)
                            <p class="font-semibold text-[#3D2B1F]">{{ $schedule->title }}</p>
                        @endif
                        <div class="flex flex-wrap items-center gap-2 {{ $schedule->title ? 'mt-1' : '' }}">
                            <span class="font-medium text-[#3D2B1F] {{ $schedule->title ? 'text-sm' : '' }}">{{ $schedule->language->name_en }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]' }}">
                                {{ $schedule->type === 'recurring' ? 'Weekly' : 'One-off' }}
                            </span>
                            <span class="text-xs text-[#3D2B1F]/50">Max {{ $schedule->max_participants }} {{ Str::plural('claim', $schedule->max_participants) }}</span>
                        </div>
                        <x-schedule-when :schedule="$schedule" class="text-sm text-[#3D2B1F]/60 mt-2 block" />
                        @if ($schedule->description)
                            <p class="text-sm text-[#3D2B1F]/70 mt-2 line-clamp-3">{{ $schedule->description }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button href="{{ route('schedules.show', $schedule) }}" wire:navigate size="sm" variant="ghost" class="flex-1 sm:flex-none">View</flux:button>
                        <flux:button type="button" size="sm" variant="ghost" wire:click="editSchedule({{ $schedule->id }})" class="flex-1 sm:flex-none">Edit</flux:button>
                        <flux:button
                            type="button"
                            size="sm"
                            variant="danger"
                            wire:click="deleteSchedule({{ $schedule->id }})"
                            wire:confirm="Delete this slot? It will be removed from your schedule."
                            class="flex-1 sm:flex-none"
                        >Delete</flux:button>
                    </div>
                </div>
            </flux:card>
        @empty
            <p class="text-center text-[#3D2B1F]/40 py-16">No slots yet. Create your first one!</p>
        @endforelse
    </div>

    @if (! $this->canCreateSlot)
        <p class="mt-4 text-sm text-[#3D2B1F]/55 text-center">{{ ScheduleLimits::limitReachedMessage() }}</p>
    @endif

    @if ($showModal)
        <div class="fixed inset-0 z-[70] flex items-end sm:items-center justify-center sm:p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeModal"></div>

            <div class="relative w-full max-w-md rounded-t-2xl sm:rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10 max-h-[92dvh] sm:max-h-[90vh] overflow-y-auto pb-[env(safe-area-inset-bottom)]">
                <div class="p-5 sm:p-6">
                    <div class="mx-auto mb-4 h-1 w-10 rounded-full bg-[#3D2B1F]/15 sm:hidden" aria-hidden="true"></div>
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">{{ $editingScheduleId ? 'Edit time slot' : 'New time slot' }}</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">{{ $editingScheduleId ? 'Update your availability.' : "Set when you're free to chat." }}</p>
                    <div class="mt-3 rounded-lg bg-[#FFF0E0] px-3 py-2 text-sm text-[#3D2B1F] ring-1 ring-[#FF8C42]/25">
                        <p class="font-medium">Your timezone: {{ $this->viewerTimezoneLabel }}</p>
                        <p class="mt-1 text-[#3D2B1F]/65">Times are saved for this timezone. Others see them converted to their local time.{{ $editingScheduleId ? ' Saving re-anchors this slot to your current timezone.' : '' }}</p>
                    </div>

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

                        <flux:input
                            wire:model="title"
                            label="Title"
                            maxlength="200"
                            placeholder="e.g. Saturday evening chat"
                            description="Only you can see this."
                        />

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
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <x-utc-time-fields
                                    label="Start"
                                    hour-model="start_hour"
                                    minute-model="start_minute"
                                />
                                <x-utc-time-fields
                                    label="End"
                                    hour-model="end_hour"
                                    minute-model="end_minute"
                                />
                            </div>
                            @error('end_time') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                        @else
                            @php $minDate = now(ViewerTimezone::current())->addDay()->toDateString(); @endphp
                            <flux:input
                                wire:model.live="slot_date"
                                label="Date"
                                type="date"
                                min="{{ $minDate }}"
                                description="Calendar day in your local timezone."
                            />
                            @error('slot_date') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <x-utc-time-fields
                                    label="Start"
                                    hour-model="start_hour"
                                    minute-model="start_minute"
                                    description="Local time."
                                />
                                <x-utc-time-fields
                                    label="End"
                                    hour-model="end_hour"
                                    minute-model="end_minute"
                                    description="Same day as start. Minimum 15 minutes long."
                                />
                            </div>
                            @error('start_datetime') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                            @error('end_datetime') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                        @endif

                        <flux:textarea
                            wire:model="description"
                            label="Session rules & notes"
                            rows="4"
                            placeholder="{{ ScheduleDescription::PLACEHOLDER }}"
                            description="Tell partners how the session works (format, language split, tools, expectations)."
                        />

                        <flux:input
                            wire:model="max_participants"
                            label="Max claims"
                            type="number"
                            min="{{ $editingScheduleId ? $min_max_participants : 1 }}"
                            max="10"
                            description="{{ ($editingScheduleId && $min_max_participants > 1) ? 'At least '.$min_max_participants.' because that many claims are already accepted.' : '' }}"
                        />
                        @error('max_participants') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 pt-2">
                            <flux:button type="button" wire:click="closeModal" variant="ghost" class="w-full sm:w-auto">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" class="w-full sm:w-auto">{{ $editingScheduleId ? 'Update slot' : 'Save slot' }}</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
