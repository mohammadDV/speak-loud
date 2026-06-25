<?php

use App\Actions\ReplyToTicket;
use App\Models\Ticket;
use App\Repositories\Contracts\ITicketRepository;
use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};

state([
    'ticket'         => null,
    'ticketMessages' => [],
    'replyBody'      => '',
]);

mount(function (Ticket $ticket) {
    $record = app(ITicketRepository::class)->findForUser($ticket->id, auth()->id());

    if (! $record) {
        abort(404);
    }

    $this->ticket = $record;
    $this->loadMessages();

    Seo::share([
        'seoTitle'       => $record->subject,
        'seoDescription' => 'Support ticket conversation on SpeakLoud.',
        'seoUrl'         => route('tickets.show', $record),
    ]);
});

title(fn () => Seo::pageTitle($this->ticket?->subject ?? 'Support ticket'));

$loadMessages = function () {
    if (! $this->ticket) {
        return;
    }

    $this->ticketMessages = app(ITicketRepository::class)->messagesForTicket($this->ticket->id);
};

$sendReply = function (ReplyToTicket $action) {
    $this->validate([
        'replyBody' => 'required|string|max:5000',
    ]);

    $action->execute($this->ticket, auth()->user(), $this->replyBody);
    $this->replyBody = '';
    $this->ticket->refresh();
    $this->loadMessages();
};

?>

<div class="flex flex-col h-[calc(100vh-4rem)] max-w-3xl mx-auto">
    <div class="px-4 pt-4">
        <x-profile-nav />
    </div>

    @if ($ticket)
        <div class="px-4 py-4 border-b border-[#3D2B1F]/10 bg-[#FFF8F0]">
            <a href="{{ route('tickets.index') }}" class="text-sm text-[#FF8C42] hover:underline">← Support tickets</a>
            <div class="mt-3 flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-[#3D2B1F] truncate">{{ $ticket->subject }}</h1>
                    <p class="text-xs text-[#3D2B1F]/50 mt-1">
                        @if ($ticket->category)
                            {{ $ticket->category->name }} ·
                        @endif
                        Opened {{ $ticket->created_at->format('M j, Y') }}
                    </p>
                </div>
                <span class="shrink-0 text-xs px-2 py-1 rounded-full bg-[#FFF0E0] text-[#3D2B1F]">
                    {{ $ticket->statusLabel() }}
                </span>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            @foreach ($ticketMessages as $message)
                @php
                    $isMine = $message->sender_id === auth()->id();
                    $isStaff = $message->sender?->isStaff() ?? false;
                    $senderName = $isMine
                        ? 'You'
                        : ($isStaff ? 'Support team' : ($message->sender?->profile?->display_name ?? 'Support'));
                @endphp
                <div class="flex w-full {{ $isMine ? 'justify-end' : 'justify-start' }}">
                    <div class="flex max-w-[85%] flex-col gap-1 sm:max-w-md {{ $isMine ? 'items-end' : 'items-start' }}">
                        <p class="text-xs text-[#3D2B1F]/45">
                            {{ $senderName }} · {{ $message->created_at->format('M j, g:i A') }}
                        </p>
                        <div
                            dir="ltr"
                            style="text-align: {{ $isMine ? 'right' : 'left' }};"
                            @class([
                                'inline-block max-w-full rounded-2xl px-4 py-3 text-sm leading-relaxed whitespace-pre-wrap',
                                'bg-[#FF8C42] text-white' => $isMine,
                                'bg-[#FFF0E0] text-[#3D2B1F]' => ! $isMine,
                            ])
                        >{{ $message->body }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        @if ($ticket->userCanReply())
            <div class="p-4 border-t border-[#3D2B1F]/10 bg-[#FFF8F0]">
                <form wire:submit="sendReply" class="flex gap-3 items-end">
                    <flux:textarea
                        wire:model="replyBody"
                        placeholder="Write a reply..."
                        rows="2"
                        class="flex-1"
                    />
                    <flux:button type="submit" variant="primary">Send</flux:button>
                </form>
            </div>
        @else
            <div class="p-4 border-t border-[#3D2B1F]/10 bg-[#FFF8F0] text-center text-sm text-[#3D2B1F]/50">
                {{ $ticket->replyBlockedMessage() }}
            </div>
        @endif
    @endif
</div>
