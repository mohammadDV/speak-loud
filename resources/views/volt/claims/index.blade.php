<?php

use function Livewire\Volt\{state, mount, computed, title, updated, hydrate};
use App\Actions\AcceptClaim;
use App\Actions\RejectClaim;
use App\Actions\WithdrawClaim;
use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Support\Seo;
use Illuminate\Support\Collection;

state([
    'incoming'          => [],
    'outgoing'          => [],
    'tab'               => 'incoming',
    'sort'              => 'newest',
    'status'            => '',
    'showRejectModal'   => false,
    'rejectingClaimId'  => null,
    'rejectMessage'     => '',
]);

$listFilters = fn (): array => [
    'status' => $this->status !== '' ? $this->status : null,
    'sort' => $this->sort,
];

$visibleIncoming = computed(fn () => Collection::make($this->incoming));

$visibleOutgoing = computed(fn () => Collection::make($this->outgoing));

$refreshClaims = function () {
    $repo = app(IClaimRepository::class);
    $filters = $this->listFilters();
    $this->incoming = $repo->incomingForUser(auth()->id(), $filters);
    $this->outgoing = $repo->outgoingForUser(auth()->id(), $filters);

    unset($this->visibleIncoming);
    unset($this->visibleOutgoing);
};

mount(function () {
    $this->refreshClaims();

    Seo::share([
        'seoTitle' => 'Claims',
        'seoDescription' => 'Manage your SpeakLoud schedule claims.',
        'seoUrl' => route('claims'),
        'seoRobots' => 'noindex, nofollow',
    ]);
});

updated([
    'status' => function () {
        $this->refreshClaims();
    },
    'sort' => function () {
        $this->refreshClaims();
    },
]);

hydrate(function () {
    $this->refreshClaims();
});

title(fn () => Seo::pageTitle('Claims'));

$accept = function (int $claimId, AcceptClaim $action) {
    $claim = Claim::findOrFail($claimId);
    $this->authorize('accept', $claim);
    $action->execute($claimId, auth()->id());
    $this->refreshClaims();
};

$openRejectModal = function (int $claimId) {
    $this->rejectingClaimId = $claimId;
    $this->rejectMessage    = '';
    $this->resetValidation();
    $this->showRejectModal  = true;
};

$closeRejectModal = function () {
    $this->showRejectModal  = false;
    $this->rejectingClaimId = null;
    $this->rejectMessage    = '';
};

$confirmReject = function (RejectClaim $action) {
    $this->validate([
        'rejectMessage' => 'nullable|string|max:500',
    ]);

    $claim = Claim::findOrFail($this->rejectingClaimId);
    $this->authorize('reject', $claim);
    $action->execute($this->rejectingClaimId, auth()->id(), $this->rejectMessage ?: null);

    $this->showRejectModal  = false;
    $this->rejectingClaimId = null;
    $this->rejectMessage    = '';
    $this->refreshClaims();
};

$withdraw = function (int $claimId, WithdrawClaim $action) {
    $claim = Claim::findOrFail($claimId);
    $this->authorize('withdraw', $claim);
    $action->execute($claimId, auth()->id());
    $this->refreshClaims();
};

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#3D2B1F] mb-6">Claims</h1>

    @error('claim')
        <div class="mb-4 rounded-lg border border-[#D94F3D]/30 bg-[#D94F3D]/10 px-4 py-3 text-sm text-[#D94F3D]">
            {{ $message }}
        </div>
    @enderror

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex gap-1 p-1 bg-[#FFF0E0] rounded-lg w-fit" role="tablist">
            <button type="button" wire:click="$set('tab', 'incoming')" role="tab"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-md transition-all',
                    'bg-white text-[#3D2B1F] shadow-sm' => $tab === 'incoming',
                    'text-[#3D2B1F]/60 hover:text-[#3D2B1F]' => $tab !== 'incoming',
                ])>
                Received ({{ $this->visibleIncoming->count() }})
            </button>
            <button type="button" wire:click="$set('tab', 'outgoing')" role="tab"
                @class([
                    'px-4 py-2 text-sm font-medium rounded-md transition-all',
                    'bg-white text-[#3D2B1F] shadow-sm' => $tab === 'outgoing',
                    'text-[#3D2B1F]/60 hover:text-[#3D2B1F]' => $tab !== 'outgoing',
                ])>
                Sent ({{ $this->visibleOutgoing->count() }})
            </button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <flux:select wire:model.live="status" label="Status" class="sm:w-40">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="accepted">Accepted</flux:select.option>
                <flux:select.option value="rejected">Rejected</flux:select.option>
                <flux:select.option value="withdrawn">Withdrawn</flux:select.option>
                <flux:select.option value="expired">Expired</flux:select.option>
            </flux:select>

            <flux:select wire:model.live="sort" label="Sort" class="sm:w-44">
                <flux:select.option value="newest">Newest first</flux:select.option>
                <flux:select.option value="oldest">Oldest first</flux:select.option>
            </flux:select>
        </div>
    </div>

    <div class="mt-6 space-y-4">
        @if ($tab === 'incoming')
            @forelse ($this->visibleIncoming as $claim)
                <flux:card wire:key="incoming-claim-{{ $claim->id }}" class="bg-[#FFF0E0] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-[#3D2B1F]">{{ $claim->sender->profile->display_name ?? 'User' }}</p>
                            @if ($claim->schedule)
                                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $claim->schedule->language->name_en ?? 'Session' }} slot claim</p>
                            @elseif ($claim->type === 'direct')
                                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">Message request</p>
                            @endif
                            @if ($claim->message)
                                <p class="text-sm text-[#3D2B1F]/60 mt-1 italic">"{{ $claim->message }}"</p>
                            @endif
                            <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full
                                {{ $claim->status === 'pending' ? 'bg-[#FFD166]/40 text-[#3D2B1F]' : ($claim->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600') }}">
                                {{ $claim->status }}
                            </span>
                        </div>
                        @if ($claim->status === 'pending')
                            @php
                                $scheduleFull = $claim->schedule
                                    && ($claim->schedule->accepted_claims_count ?? 0) >= $claim->schedule->max_participants;
                            @endphp
                            <div class="flex flex-col items-end gap-2 shrink-0">
                                @if ($scheduleFull)
                                    <p class="text-xs text-[#D94F3D] text-right max-w-[14rem]">
                                        Slot full ({{ $claim->schedule->accepted_claims_count }}/{{ $claim->schedule->max_participants }}). Increase max claims or decline.
                                    </p>
                                @endif
                                <div class="flex gap-2">
                                <flux:button
                                    wire:click="accept({{ $claim->id }})"
                                    wire:confirm="Accept this claim? You can coordinate in your existing chat with this person."
                                    variant="primary"
                                    size="sm"
                                    :disabled="$scheduleFull"
                                >Accept</flux:button>
                                <flux:button
                                    type="button"
                                    wire:click="openRejectModal({{ $claim->id }})"
                                    variant="ghost"
                                    size="sm"
                                >Decline</flux:button>
                                </div>
                            </div>
                        @elseif ($claim->status === 'accepted' && $claim->schedule_id)
                            <flux:button href="{{ route('schedules.show', $claim->schedule_id) }}" wire:navigate variant="primary" size="sm">
                                Group chat
                            </flux:button>
                        @elseif (in_array($claim->status, ['accepted', 'rejected'], true) && $claim->conversation)
                            <flux:button href="{{ route('messages.show', $claim->conversation->id) }}" variant="ghost" size="sm">
                                Chat
                            </flux:button>
                        @endif
                    </div>
                </flux:card>
            @empty
                <p class="text-center text-[#3D2B1F]/40 py-12">
                    {{ count($incoming) === 0 ? 'No received claims.' : 'No claims match these filters.' }}
                </p>
            @endforelse
        @else
            @forelse ($this->visibleOutgoing as $claim)
                <flux:card wire:key="outgoing-claim-{{ $claim->id }}" class="bg-[#FFF0E0] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F]">To: {{ $claim->receiver->profile->display_name ?? $claim->schedule?->user?->profile?->display_name ?? 'User' }}</p>
                            @if ($claim->schedule)
                                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $claim->schedule->language->name_en ?? 'Session' }} slot claim</p>
                            @elseif ($claim->type === 'direct')
                                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">Message request</p>
                            @endif
                            <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full
                                {{ $claim->status === 'pending' ? 'bg-[#FFD166]/40 text-[#3D2B1F]' : ($claim->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600') }}">
                                {{ $claim->status }}
                            </span>
                        </div>
                        <div class="shrink-0">
                            @if ($claim->status === 'pending')
                                <flux:button
                                    wire:click="withdraw({{ $claim->id }})"
                                    wire:confirm="Withdraw this claim?"
                                    variant="ghost"
                                    size="sm"
                                >Withdraw</flux:button>
                            @elseif ($claim->status === 'accepted' && $claim->schedule_id)
                                <flux:button href="{{ route('schedules.show', $claim->schedule_id) }}" wire:navigate variant="primary" size="sm">
                                    Open slot
                                </flux:button>
                            @elseif (in_array($claim->status, ['accepted', 'rejected'], true) && $claim->conversation)
                                <flux:button href="{{ route('messages.show', $claim->conversation->id) }}" variant="ghost" size="sm">
                                    Chat
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @empty
                <p class="text-center text-[#3D2B1F]/40 py-12">
                    {{ count($outgoing) === 0 ? 'No sent claims.' : 'No claims match these filters.' }}
                </p>
            @endforelse
        @endif
    </div>

    @if ($showRejectModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeRejectModal"></div>

            <div class="relative w-full max-w-md rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">Decline claim</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">Optional: send a short message so they know why. It will appear in chat.</p>

                    <form wire:submit="confirmReject" class="mt-5 space-y-4">
                        <flux:textarea
                            wire:model="rejectMessage"
                            label="Message (optional)"
                            rows="4"
                            placeholder="e.g. Sorry, I'm fully booked this week. Try again next Saturday!"
                        />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeRejectModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="danger">Decline claim</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
