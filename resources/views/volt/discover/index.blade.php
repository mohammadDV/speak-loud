<?php

use App\Support\Seo;
use function Livewire\Volt\{state, mount, computed, title, hydrate};
use App\Actions\SendClaim;
use App\Models\Schedule;
use App\Repositories\Contracts\IScheduleRepository;
use App\Support\CountryCodes;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

state([
    'search'                => '',
    'language_id'           => '',
    'level'                 => '',
    'country_code'          => '',
    'type'                  => '',
    'selected_interest_ids' => [],
    'languages'             => [],
    'allInterests'          => [],
    'schedulesPage'         => 1,
    'loadedSchedules'       => [],
    'hasMoreSchedules'      => false,
    'scrollLoadEnabled'     => false,
    'showClaimModal'        => false,
    'claimScheduleId'       => null,
    'claimMessage'          => '',
]);

$loadSchedules = function (bool $append = false) {
    $paginator = app(IScheduleRepository::class)->searchOpenSchedules([
        'search'       => $this->search,
        'language_id'  => $this->language_id ?: null,
        'level'        => $this->level ?: null,
        'country_code' => $this->country_code ?: null,
        'type'         => $this->type ?: null,
        'interest_ids' => $this->selected_interest_ids ?: null,
        'page'         => $this->schedulesPage,
    ], auth()->id());

    if ($append) {
        $this->loadedSchedules = [...$this->loadedSchedules, ...$paginator->items()];
    } else {
        $this->loadedSchedules = $paginator->items();
    }

    $this->hasMoreSchedules = $paginator->hasMorePages();
};

$loadMoreSchedules = function () {
    if (! $this->hasMoreSchedules) {
        return;
    }

    $this->schedulesPage++;
    $this->loadSchedules(append: true);
};

$enableScrollLoad = function () {
    $this->scrollLoadEnabled = true;
};

$loadMoreSchedulesFromScroll = function () {
    if (! $this->scrollLoadEnabled) {
        return;
    }

    $this->loadMoreSchedules();
};

mount(function () {
    Seo::share([
        'seoTitle'       => 'Discover practice partners',
        'seoDescription' => 'Browse open language practice slots by language, level, country, and shared interests. Claim a slot and start speaking.',
        'seoUrl'         => route('discover'),
    ]);

    $this->languages = LookupCache::activeLanguages();
    $this->allInterests = LookupCache::interests();
    $this->loadSchedules();

    if (auth()->check() && session()->has('pending_claim_schedule_id')) {
        if (! auth()->user()->hasVerifiedEmail()) {
            $this->redirect(route('verification.notice'), navigate: true);

            return;
        }

        $this->claimScheduleId = session()->pull('pending_claim_schedule_id');
        $this->showClaimModal  = true;
    }
});

hydrate(function () {
    $this->languages = LookupCache::activeLanguages();
    $this->allInterests = LookupCache::interests();

    $schedules = collect($this->loadedSchedules)->filter(fn ($schedule) => $schedule instanceof Schedule);

    if ($schedules->isEmpty()) {
        return;
    }

    $with = [
        'user.profile',
        'user.interests',
        'user.languages.language',
        'language',
        'recurringRule',
        'oneTimeSlot',
    ];

    if (auth()->check()) {
        $viewerId = auth()->id();
        $with['claims'] = fn ($q) => $q
            ->where('sender_id', $viewerId)
            ->whereIn('status', ['pending', 'accepted']);
    }

    (new EloquentCollection($schedules->all()))->load($with);
    (new EloquentCollection($schedules->all()))->loadCount([
        'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
    ]);
});

title(fn () => Seo::pageTitle('Discover practice partners'));

$myInterestIds = computed(function () {
    if (! auth()->check()) {
        return [];
    }

    return auth()->user()
        ->interests()
        ->pluck('interests.id')
        ->map(fn ($id) => (string) $id)
        ->all();
});

$applyFilters = function () {
    $this->schedulesPage = 1;
    $this->scrollLoadEnabled = false;
    $this->loadSchedules();
};

$applyMyInterests = function () {
    $this->selected_interest_ids = $this->myInterestIds;
    $this->applyFilters();
};

$clearInterestFilter = function () {
    $this->selected_interest_ids = [];
    $this->applyFilters();
};

$activeFilterCount = computed(function () {
    $count = 0;

    if (trim($this->search) !== '') {
        $count++;
    }

    if ($this->language_id !== '') {
        $count++;
    }

    if ($this->country_code !== '') {
        $count++;
    }

    if ($this->type !== '') {
        $count++;
    }

    if ($this->level !== '') {
        $count++;
    }

    $count += count($this->selected_interest_ids);

    return $count;
});

$clearAllFilters = function () {
    $this->search = '';
    $this->language_id = '';
    $this->level = '';
    $this->country_code = '';
    $this->type = '';
    $this->selected_interest_ids = [];
    $this->applyFilters();
};

$claimTarget = computed(function () {
    if (! $this->claimScheduleId) {
        return null;
    }

    return Schedule::query()
        ->where('status', 'active')
        ->when(auth()->check(), fn ($q) => $q->where('user_id', '!=', auth()->id()))
        ->with(['user.profile', 'user.interests', 'user.languages.language', 'language', 'recurringRule', 'oneTimeSlot'])
        ->withCount([
            'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
        ])
        ->find($this->claimScheduleId);
});

$openClaimModal = function (int $scheduleId) {
    if (! auth()->check()) {
        session([
            'pending_claim_schedule_id' => $scheduleId,
            'pending_claim_return'      => route('discover'),
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    if (! auth()->user()->hasVerifiedEmail()) {
        return $this->redirect(route('verification.notice'), navigate: true);
    }

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
    if (! auth()->check()) {
        return $this->redirect(route('login'), navigate: true);
    }

    if (! auth()->user()->hasVerifiedEmail()) {
        return $this->redirect(route('verification.notice'), navigate: true);
    }

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

<div
    class="max-w-7xl mx-auto px-4 py-8"
    x-data="{ filtersOpen: false }"
    @keydown.escape.window="filtersOpen = false"
    @scroll.window.once="$wire.enableScrollLoad()"
>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Open slots</h1>
            <p class="text-sm text-[#3D2B1F]/60 mt-1">
                @auth
                    Find partners by language, level, and shared interests. Track claims under Claims.
                @else
                    Browse open practice slots and filter by what hosts like to talk about.
                @endauth
                <span class="block mt-1 text-xs text-[#3D2B1F]/45">Times shown in your local timezone ({{ \App\Support\ViewerTimezone::current() }}).</span>
            </p>
        </div>

        <flux:button type="button" x-on:click="filtersOpen = true" variant="ghost" class="w-full sm:w-auto shrink-0">
            Filters
            @if ($this->activeFilterCount > 0)
                <span class="ms-1 inline-flex min-w-5 items-center justify-center rounded-full bg-[#FF8C42] px-1.5 text-[11px] font-semibold text-white">
                    {{ $this->activeFilterCount }}
                </span>
            @endif
        </flux:button>
    </div>

    @if ($loadedSchedules === [])
        <p class="text-[#3D2B1F]/50 text-center py-16">No open slots match your filters. Try fewer interests, another language, or check back later.</p>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 items-stretch">
            @foreach ($loadedSchedules as $schedule)
                <x-open-schedule-card :schedule="$schedule" layout="grid" wire:key="discover-schedule-{{ $schedule->id }}" />
            @endforeach
        </div>

        @if ($hasMoreSchedules)
            <div
                wire:intersect="loadMoreSchedulesFromScroll"
                wire:intersect:margin.400px
                wire:key="discover-scroll-sentinel"
                class="h-px"
                aria-hidden="true"
            ></div>

            <div class="mt-8 flex justify-center">
                <flux:button
                    type="button"
                    wire:click="loadMoreSchedules"
                    wire:loading.attr="disabled"
                    wire:target="loadMoreSchedules"
                    variant="ghost"
                >
                    <span wire:loading.remove wire:target="loadMoreSchedules">Load more</span>
                    <span wire:loading wire:target="loadMoreSchedules">Loading…</span>
                </flux:button>
            </div>
        @endif
    @endif

    <div
        x-show="filtersOpen"
        x-cloak
        class="fixed inset-0 z-[70] flex items-end justify-center"
        role="dialog"
        aria-modal="true"
        aria-label="Filters"
    >
        <div class="absolute inset-0 bg-[#3D2B1F]/40" x-on:click="filtersOpen = false"></div>

        <div class="relative flex w-full max-w-lg flex-col rounded-t-2xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10 max-h-[92dvh] pb-[env(safe-area-inset-bottom)]" @click.stop>
            <div class="shrink-0 px-5 pt-4 pb-3">
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-[#3D2B1F]/15" aria-hidden="true"></div>
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">Filters</h2>
                    <button
                        type="button"
                        x-on:click="filtersOpen = false"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-[#3D2B1F]/70 hover:bg-black/[0.04]"
                        aria-label="Close filters"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto px-5 pb-4 space-y-4">
                <flux:input wire:model="search"
                    placeholder="Rules, host name..."
                    label="Search slots" />

                <flux:select wire:model="language_id" label="Practice language">
                    <flux:select.option value="">Any language</flux:select.option>
                    @foreach ($languages as $lang)
                        <flux:select.option value="{{ $lang->id }}">{{ $lang->name_en }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="country_code" label="Host country">
                    <flux:select.option value="">Any country</flux:select.option>
                    @foreach (CountryCodes::options() as $code => $name)
                        <flux:select.option value="{{ $code }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="type" label="Slot type">
                    <flux:select.option value="">Weekly or one-off</flux:select.option>
                    <flux:select.option value="recurring">Weekly</flux:select.option>
                    <flux:select.option value="one_time">One-off</flux:select.option>
                </flux:select>

                <flux:select wire:model="level" label="Host level">
                    <flux:select.option value="">Any level</flux:select.option>
                    <flux:select.option value="beginner">A1 – Beginner</flux:select.option>
                    <flux:select.option value="elementary">A2 – Elementary</flux:select.option>
                    <flux:select.option value="intermediate">B1 – Intermediate</flux:select.option>
                    <flux:select.option value="upper_intermediate">B2 – Upper Intermediate</flux:select.option>
                    <flux:select.option value="advanced">C1 – Advanced</flux:select.option>
                    <flux:select.option value="fluent">C2 – Fluent</flux:select.option>
                </flux:select>

                <fieldset>
                    <legend class="text-sm font-medium text-[#3D2B1F]">Host interests</legend>
                    <p class="text-xs text-[#3D2B1F]/50 mt-1 mb-2">Show slots from hosts who share at least one selected interest.</p>

                    @auth
                        @if ($this->myInterestIds !== [])
                            <flux:button type="button" wire:click="applyMyInterests" variant="ghost" size="sm" class="mb-2 w-full">
                                Use my interests
                            </flux:button>
                        @else
                            <p class="text-xs text-[#3D2B1F]/45 mb-2">
                                <a href="{{ route('profile.edit') }}" wire:navigate class="text-[#FF8C42] hover:underline">Add interests</a>
                                to your profile to use this shortcut.
                            </p>
                        @endif
                    @endauth

                    <div class="max-h-40 overflow-y-auto space-y-1.5 pr-1">
                        @foreach ($allInterests as $interest)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm text-[#3D2B1F] hover:bg-[#FFF0E0] has-[:checked]:bg-[#FF8C42]/10">
                                <input
                                    type="checkbox"
                                    wire:model="selected_interest_ids"
                                    value="{{ $interest->id }}"
                                    class="rounded border-[#FF8C42]/50 text-[#FF8C42] focus:ring-[#FF8C42]"
                                />
                                {{ $interest->name_en }}
                            </label>
                        @endforeach
                    </div>

                    @if ($selected_interest_ids !== [])
                        <button type="button" wire:click="clearInterestFilter" class="mt-2 text-xs text-[#FF8C42] hover:underline">
                            Clear interests
                        </button>
                    @endif
                </fieldset>
            </div>

            <div class="shrink-0 flex gap-2 border-t border-black/[0.06] px-5 py-4">
                @if ($this->activeFilterCount > 0)
                    <flux:button type="button" wire:click="clearAllFilters" x-on:click="filtersOpen = false" variant="ghost" class="flex-1">Clear all</flux:button>
                @endif
                <flux:button type="button" wire:click="applyFilters" x-on:click="filtersOpen = false" variant="primary" class="flex-1">Done</flux:button>
            </div>
        </div>
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
