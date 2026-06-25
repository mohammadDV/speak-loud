<?php

use App\Actions\CreateTicket;
use App\Models\TicketCategory;
use App\Repositories\Contracts\ITicketRepository;
use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};

state([
    'tickets'          => [],
    'categories'       => [],
    'pendingTicket'    => null,
    'showCreateModal'  => false,
    'categoryId'       => null,
    'subject'          => '',
    'body'             => '',
]);

mount(function () {
    $repo = app(ITicketRepository::class);
    $this->tickets = $repo->forUser(auth()->id());
    $this->pendingTicket = $repo->pendingStaffReplyForUser(auth()->id());
    $this->categories = TicketCategory::query()->orderBy('name')->get();

    Seo::share([
        'seoTitle'       => 'Support tickets',
        'seoDescription' => 'Contact the SpeakLoud support team and track your requests.',
        'seoUrl'         => route('tickets.index'),
    ]);
});

title(fn () => Seo::pageTitle('Support tickets'));

$refreshTickets = function () {
    $repo = app(ITicketRepository::class);
    $this->tickets = $repo->forUser(auth()->id());
    $this->pendingTicket = $repo->pendingStaffReplyForUser(auth()->id());
};

$openCreateModal = function () {
    if ($this->pendingTicket) {
        return;
    }

    $this->categoryId = $this->categories->first()?->id;
    $this->subject = '';
    $this->body = '';
    $this->resetValidation();
    $this->showCreateModal = true;
};

$closeCreateModal = function () {
    $this->showCreateModal = false;
};

$createTicket = function (CreateTicket $action) {
    $this->validate([
        'categoryId' => 'nullable|exists:ticket_categories,id',
        'subject'    => 'required|string|max:255',
        'body'       => 'required|string|max:5000',
    ]);

    $ticket = $action->execute(
        auth()->user(),
        $this->categoryId,
        $this->subject,
        $this->body,
    );

    $this->showCreateModal = false;
    $this->refreshTickets();

    $this->redirect(route('tickets.show', $ticket), navigate: true);
};

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6">
        <x-profile-nav />
    </div>

    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Support tickets</h1>
            <p class="text-sm text-[#3D2B1F]/55 mt-1">
                Need help? Open a ticket and our team will get back to you here.
                Check the <a href="{{ route('faq.index') }}" class="text-[#FF8C42] font-semibold hover:underline">FAQ</a> for quick answers first.
            </p>
        </div>
        @if (! $pendingTicket)
            <flux:button wire:click="openCreateModal" variant="primary">New ticket</flux:button>
        @else
            <flux:button variant="primary" disabled>New ticket</flux:button>
        @endif
    </div>

    @if ($pendingTicket)
        <div class="mb-6 rounded-xl bg-[#FFD166]/25 border border-[#FFD166]/40 px-4 py-3 text-sm text-[#3D2B1F]">
            You already have an open ticket waiting for a response.
            <a href="{{ route('tickets.show', $pendingTicket) }}" wire:navigate class="font-semibold text-[#FF8C42] hover:underline">
                View ticket
            </a>
        </div>
    @endif

    <div class="space-y-3">
        @forelse ($tickets as $ticket)
            <a href="{{ route('tickets.show', $ticket) }}" wire:navigate
                class="block bg-[#FFF0E0] rounded-xl p-5 hover:ring-2 hover:ring-[#FF8C42]/20 transition-all">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-semibold text-[#3D2B1F] truncate">{{ $ticket->subject }}</p>
                        @if ($ticket->category)
                            <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $ticket->category->name }}</p>
                        @endif
                        @if ($ticket->messages->first())
                            <p class="text-sm text-[#3D2B1F]/60 mt-2 line-clamp-2">{{ $ticket->messages->first()->body }}</p>
                        @endif
                    </div>
                    <div class="shrink-0 text-right space-y-2">
                        <span @class(['inline-block text-xs px-2 py-0.5 rounded-full', $ticket->statusBadgeClass()])>
                            {{ $ticket->statusLabel() }}
                        </span>
                        <p class="text-xs text-[#3D2B1F]/40">{{ $ticket->updated_at->diffForHumans() }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="text-center py-16 bg-[#FFF0E0] rounded-xl">
                <p class="text-[#3D2B1F]/50 text-sm">No tickets yet.</p>
                @if (! $pendingTicket)
                    <flux:button wire:click="openCreateModal" variant="primary" class="mt-4">Open your first ticket</flux:button>
                @endif
            </div>
        @endforelse
    </div>

    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeCreateModal"></div>

            <div class="relative w-full max-w-lg rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">New support ticket</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">Describe your issue and we will reply in this thread.</p>

                    <form wire:submit="createTicket" class="mt-5 space-y-4">
                        <flux:select wire:model="categoryId" label="Category">
                            <flux:select.option value="">General</flux:select.option>
                            @foreach ($categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <flux:input wire:model="subject" label="Subject" placeholder="Brief summary of your issue" />

                        <flux:textarea
                            wire:model="body"
                            label="Message"
                            rows="5"
                            placeholder="Tell us what happened and what you need help with."
                        />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeCreateModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Send ticket</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
