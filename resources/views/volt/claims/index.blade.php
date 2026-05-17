<?php

use function Livewire\Volt\{state, mount};
use App\Actions\AcceptClaim;
use App\Repositories\Contracts\IClaimRepository;

state([
    'incoming' => [],
    'outgoing' => [],
    'tab'      => 'incoming',
]);

mount(function () {
    $repo = app(IClaimRepository::class);
    $this->incoming = $repo->incomingForUser(auth()->id());
    $this->outgoing = $repo->outgoingForUser(auth()->id());
});

$accept = function (int $claimId, AcceptClaim $action) {
    $action->execute($claimId);
    $this->incoming = app(IClaimRepository::class)->incomingForUser(auth()->id());
};

$reject = function (int $claimId) {
    app(IClaimRepository::class)->updateStatus($claimId, 'rejected');
    $this->incoming = app(IClaimRepository::class)->incomingForUser(auth()->id());
};

$withdraw = function (int $claimId) {
    app(IClaimRepository::class)->updateStatus($claimId, 'withdrawn');
    $this->outgoing = app(IClaimRepository::class)->outgoingForUser(auth()->id());
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
                        <div class="flex-1">
                            <p class="font-medium text-[#3D2B1F]">{{ $claim->sender->profile->display_name ?? 'User' }}</p>
                            @if ($claim->message)
                                <p class="text-sm text-[#3D2B1F]/60 mt-1 italic">"{{ $claim->message }}"</p>
                            @endif
                            <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full
                                {{ $claim->status === 'pending' ? 'bg-[#FFD166]/40 text-[#3D2B1F]' : ($claim->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600') }}">
                                {{ $claim->status }}
                            </span>
                        </div>
                        @if ($claim->status === 'pending')
                            <div class="flex gap-2">
                                <flux:button wire:click="accept({{ $claim->id }})" variant="primary" size="sm">Accept</flux:button>
                                <flux:button wire:click="reject({{ $claim->id }})" variant="ghost" size="sm">Decline</flux:button>
                            </div>
                        @elseif ($claim->status === 'accepted' && $claim->conversation)
                            <flux:button href="{{ route('messages.show', $claim->conversation->id) }}" variant="ghost" size="sm">Chat</flux:button>
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
                        <div>
                            <p class="font-medium text-[#3D2B1F]">To: {{ $claim->receiver->profile->display_name ?? 'User' }}</p>
                            <span class="inline-block mt-2 text-xs px-2 py-0.5 rounded-full
                                {{ $claim->status === 'pending' ? 'bg-[#FFD166]/40 text-[#3D2B1F]' : ($claim->status === 'accepted' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600') }}">
                                {{ $claim->status }}
                            </span>
                        </div>
                        @if ($claim->status === 'pending')
                            <flux:button wire:click="withdraw({{ $claim->id }})" variant="ghost" size="sm">Withdraw</flux:button>
                        @endif
                    </div>
                </flux:card>
            @empty
                <p class="text-center text-[#3D2B1F]/40 py-12">No outgoing claims.</p>
            @endforelse
        @endif
    </div>
</div>
