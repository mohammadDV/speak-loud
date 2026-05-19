<?php

use function Livewire\Volt\{state, mount};
use App\Actions\AcceptClaim;
use App\Actions\RejectClaim;
use App\Repositories\Contracts\IClaimRepository;

state([
    'incoming'          => [],
    'outgoing'          => [],
    'tab'               => 'incoming',
    'showRejectModal'   => false,
    'rejectingClaimId'  => null,
    'rejectMessage'     => '',
]);

$refreshClaims = function () {
    $repo = app(IClaimRepository::class);
    $this->incoming = $repo->incomingForUser(auth()->id());
    $this->outgoing = $repo->outgoingForUser(auth()->id());
};

mount(function () {
    $this->refreshClaims();
});

$accept = function (int $claimId, AcceptClaim $action) {
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

    $action->execute($this->rejectingClaimId, auth()->id(), $this->rejectMessage ?: null);

    $this->showRejectModal  = false;
    $this->rejectingClaimId = null;
    $this->rejectMessage    = '';
    $this->refreshClaims();
};

$withdraw = function (int $claimId) {
    app(IClaimRepository::class)->updateStatus($claimId, 'withdrawn');
    $this->refreshClaims();
};

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#3D2B1F] mb-6">Claims</h1>

    <div class="flex gap-1 p-1 bg-[#FFF0E0] rounded-lg w-fit" role="tablist">
        <button type="button" wire:click="$set('tab', 'incoming')" role="tab"
            @class([
                'px-4 py-2 text-sm font-medium rounded-md transition-all',
                'bg-white text-[#3D2B1F] shadow-sm' => $tab === 'incoming',
                'text-[#3D2B1F]/60 hover:text-[#3D2B1F]' => $tab !== 'incoming',
            ])>
            Incoming ({{ count($incoming) }})
        </button>
        <button type="button" wire:click="$set('tab', 'outgoing')" role="tab"
            @class([
                'px-4 py-2 text-sm font-medium rounded-md transition-all',
                'bg-white text-[#3D2B1F] shadow-sm' => $tab === 'outgoing',
                'text-[#3D2B1F]/60 hover:text-[#3D2B1F]' => $tab !== 'outgoing',
            ])>
            Outgoing ({{ count($outgoing) }})
        </button>
    </div>

    <div class="mt-6 space-y-4">
        @if ($tab === 'incoming')
            @forelse ($incoming as $claim)
                <flux:card class="bg-[#FFF0E0] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-[#3D2B1F]">{{ $claim->sender->profile->display_name ?? 'User' }}</p>
                            @if ($claim->schedule)
                                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $claim->schedule->language->name_en ?? 'Session' }} slot</p>
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
                            <div class="flex gap-2 shrink-0">
                                <flux:button
                                    wire:click="accept({{ $claim->id }})"
                                    wire:confirm="Accept this claim? You can coordinate in your existing chat with this person."
                                    variant="primary"
                                    size="sm"
                                >Accept</flux:button>
                                <flux:button
                                    type="button"
                                    wire:click="openRejectModal({{ $claim->id }})"
                                    variant="ghost"
                                    size="sm"
                                >Decline</flux:button>
                            </div>
                        @elseif (in_array($claim->status, ['accepted', 'rejected'], true) && $claim->conversation)
                            <flux:button href="{{ route('messages.show', $claim->conversation->id) }}" variant="ghost" size="sm">
                                Chat
                            </flux:button>
                        @endif
                    </div>
                </flux:card>
            @empty
                <p class="text-center text-[#3D2B1F]/40 py-12">No incoming claims.</p>
            @endforelse
        @else
            @forelse ($outgoing as $claim)
                <flux:card class="bg-[#FFF0E0] p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F]">To: {{ $claim->receiver->profile->display_name ?? $claim->schedule?->user?->profile?->display_name ?? 'User' }}</p>
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
                            @elseif (in_array($claim->status, ['accepted', 'rejected'], true) && $claim->conversation)
                                <flux:button href="{{ route('messages.show', $claim->conversation->id) }}" variant="ghost" size="sm">
                                    Chat
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </flux:card>
            @empty
                <p class="text-center text-[#3D2B1F]/40 py-12">No outgoing claims.</p>
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
