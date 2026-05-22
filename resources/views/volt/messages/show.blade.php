<?php

use App\Models\Conversation;
use function Livewire\Volt\{state, mount};
use App\Repositories\Contracts\IMessageRepository;

state([
    'conversation' => null,
    'messages'     => [],
    'newMessage'   => '',
]);

mount(function (int $id) {
    $conversation = Conversation::query()
        ->with(['userA.profile', 'userB.profile'])
        ->find($id);

    if (! $conversation) {
        abort(404);
    }

    if (! $conversation->userCanAccess(auth()->id())) {
        abort(403);
    }

    if ($conversation->isScheduleGroup()) {
        $this->redirect(route('schedules.show', $conversation->schedule_id), navigate: true);

        return;
    }

    $this->conversation = $conversation;
    $this->loadMessages();
});

$loadMessages = function () {
    if (! $this->conversation) {
        return;
    }

    $messages = app(IMessageRepository::class);
    $this->messages = $messages->forConversation($this->conversation->id);
    $messages->markRead($this->conversation->id, auth()->id());
};

$sendMessage = function () {
    if (! trim($this->newMessage) || ! $this->conversation) {
        return;
    }

    app(IMessageRepository::class)->create([
        'conversation_id' => $this->conversation->id,
        'sender_id'       => auth()->id(),
        'body'            => trim($this->newMessage),
    ]);

    $this->conversation->update(['last_message_at' => now()]);
    $this->newMessage = '';
    $this->loadMessages();
};

?>

<div class="flex flex-col h-[calc(100vh-4rem)] max-w-3xl mx-auto">
    @if ($conversation)
        @php
            $partnerUser = $conversation->user_a_id === auth()->id()
                ? $conversation->userB
                : $conversation->userA;
        @endphp

        <div class="flex items-center gap-3 px-4 py-3 border-b border-[#3D2B1F]/10 bg-[#FFF8F0]">
            <a href="{{ route('messages') }}" class="text-sm text-[#FF8C42] hover:underline shrink-0">← Messages</a>
            <div class="w-10 h-10 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold shrink-0">
                {{ strtoupper(substr($partnerUser?->profile?->display_name ?? '?', 0, 1)) }}
            </div>
            <div class="min-w-0">
                <p class="font-semibold text-[#3D2B1F] truncate">{{ $partnerUser?->profile?->display_name ?? 'User' }}</p>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 space-y-4">
            @forelse ($messages as $msg)
                <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-sm px-4 py-2 rounded-2xl text-sm {{ $msg->sender_id === auth()->id() ? 'bg-[#FF8C42] text-white' : 'bg-[#FFF0E0] text-[#3D2B1F]' }}">
                        {{ $msg->body }}
                    </div>
                </div>
            @empty
                <p class="text-center text-[#3D2B1F]/40 text-sm">No messages yet.</p>
            @endforelse
        </div>

        <div class="p-4 border-t border-[#3D2B1F]/10 flex gap-3 bg-[#FFF8F0]">
            <flux:textarea wire:model="newMessage" placeholder="Type a message..." rows="1" class="flex-1" />
            <flux:button wire:click="sendMessage" variant="primary">Send</flux:button>
        </div>
    @else
        <div class="flex-1 flex items-center justify-center text-[#3D2B1F]/40">
            Conversation not found.
        </div>
    @endif
</div>
