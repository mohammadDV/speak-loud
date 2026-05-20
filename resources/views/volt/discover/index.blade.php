<?php

use function Livewire\Volt\{state, mount, computed, usesPagination};
use App\Actions\SendClaim;
use App\Models\Language;
use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use App\Support\CountryCodes;

usesPagination();

state([
    'search'           => '',
    'language_id'      => '',
    'level'            => '',
    'country_code'     => '',
    'type'             => '',
    'languages'        => [],
    'showClaimModal'   => false,
    'claimScheduleId'  => null,
    'claimMessage'     => '',
]);

mount(function () {
    $this->languages = Language::where('is_active', true)->orderBy('name_en')->get();
});

$openSchedules = computed(function () {
    return app(IScheduleRepository::class)->searchOpenSchedules([
        'search'       => $this->search,
        'language_id'  => $this->language_id ?: null,
        'level'        => $this->level ?: null,
        'country_code' => $this->country_code ?: null,
        'type'         => $this->type ?: null,
        'page'         => $this->getPage(),
    ], auth()->id());
});

$claimTarget = computed(function () {
    if (! $this->claimScheduleId) {
        return null;
    }

    return Schedule::query()
        ->with(['user.profile', 'user.languages', 'language', 'recurringRule', 'oneTimeSlot'])
        ->withCount([
            'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
        ])
        ->find($this->claimScheduleId);
});

$resetPageOnFilter = function () {
    $this->resetPage();
};

$updatedSearch = $resetPageOnFilter;
$updatedLanguageId = $resetPageOnFilter;
$updatedLevel = $resetPageOnFilter;
$updatedCountryCode = $resetPageOnFilter;
$updatedType = $resetPageOnFilter;

$openClaimModal = function (int $scheduleId) {
    $this->claimScheduleId = $scheduleId;
    $this->claimMessage    = '';
    $this->resetValidation();
    $this->showClaimModal = true;
};

$closeClaimModal = function () {
    $this->showClaimModal  = false;
    $this->claimScheduleId = null;
    $this->claimMessage    = '';
};

$sendClaim = function (SendClaim $action) {
    $schedule = Schedule::query()
        ->where('status', 'active')
        ->where('user_id', '!=', auth()->id())
        ->findOrFail($this->claimScheduleId);

    $this->validate([
        'claimMessage' => 'nullable|string|max:500',
    ]);

    $action->execute([
        'sender_id'   => auth()->id(),
        'receiver_id' => $schedule->user_id,
        'schedule_id' => $schedule->id,
        'message'     => trim($this->claimMessage) ?: null,
    ]);

    $this->closeClaimModal();
};

?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-[16rem_minmax(0,1fr)] gap-x-8 gap-y-6">
        <aside class="min-w-0">
            <div class="lg:sticky lg:top-[4.25rem] lg:z-10 lg:max-h-[calc(100vh-4.25rem-1rem)] lg:overflow-y-auto lg:overscroll-contain rounded-xl bg-[#FFF8F0]/95 lg:backdrop-blur-sm py-1 pr-1">
            <flux:fieldset>
                <flux:legend>Filters</flux:legend>

                <div class="space-y-4 mt-4">
                    <flux:input wire:model.live.debounce.400ms="search"
                        placeholder="Rules, host name..."
                        label="Search slots" />

                    <flux:select wire:model.live="language_id" label="Practice language">
                        <flux:select.option value="">Any language</flux:select.option>
                        @foreach ($languages as $lang)
                            <flux:select.option value="{{ $lang->id }}">{{ $lang->name_en }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="country_code" label="Host country">
                        <flux:select.option value="">Any country</flux:select.option>
                        @foreach (CountryCodes::options() as $code => $name)
                            <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="type" label="Slot type">
                        <flux:select.option value="">Weekly or one-off</flux:select.option>
                        <flux:select.option value="recurring">Weekly</flux:select.option>
                        <flux:select.option value="one_time">One-off</flux:select.option>
                    </flux:select>

                    <flux:select wire:model.live="level" label="Host level">
                        <flux:select.option value="">Any level</flux:select.option>
                        <flux:select.option value="beginner">A1 – Beginner</flux:select.option>
                        <flux:select.option value="elementary">A2 – Elementary</flux:select.option>
                        <flux:select.option value="intermediate">B1 – Intermediate</flux:select.option>
                        <flux:select.option value="upper_intermediate">B2 – Upper Intermediate</flux:select.option>
                        <flux:select.option value="advanced">C1 – Advanced</flux:select.option>
                        <flux:select.option value="fluent">C2 – Fluent</flux:select.option>
                    </flux:select>
                </div>
            </flux:fieldset>
            </div>
        </aside>

        <main class="flex-1 min-w-0">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-[#3D2B1F]">Open slots</h1>
                <p class="text-sm text-[#3D2B1F]/60 mt-1">Open slots you haven't applied to yet. Track existing claims under Claims.</p>
            </div>

            @if ($this->openSchedules->isEmpty())
                <p class="text-[#3D2B1F]/50 text-center py-16">No open slots match your filters. Try another language or check back later.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch">
                    @foreach ($this->openSchedules as $schedule)
                        <x-open-schedule-card :schedule="$schedule" layout="grid" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $this->openSchedules->links() }}</div>
            @endif
        </main>
    </div>

    @if ($showClaimModal && $this->claimTarget)
        @php $schedule = $this->claimTarget; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeClaimModal"></div>

            <div class="relative w-full max-w-lg rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">Send claim</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">
                        Review the slot below, then send your request to the host.
                    </p>

                    <x-schedule-details :schedule="$schedule" class="mt-4" />

                    <form wire:submit="sendClaim" class="mt-6 space-y-4">
                        <flux:textarea
                            wire:model="claimMessage"
                            label="Message to host (optional)"
                            rows="3"
                            placeholder="e.g. Hi! I'd love to practice — I'm B1 in German and happy to help with English."
                        />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeClaimModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Send claim</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
