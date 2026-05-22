<?php

use App\Models\Conversation;
use function Livewire\Volt\{state, mount};
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;

state([
    'conversations'      => [],
    'activeConversation' => null,
    'messages'           => [],
    'newMessage'         => '',
]);

mount(function () {
    $this->conversations = app(IConversationRepository::class)->forUser(auth()->id());
});

$selectConversation = function (int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation || ! $conversation->userCanAccess(auth()->id())) {
        return;
    }

    if ($conversation->isScheduleGroup()) {
        $this->redirect(route('schedules.show', $conversation->schedule_id), navigate: true);

        return;
    }

    $this->activeConversation = $conversationId;
    $this->messages           = app(IMessageRepository::class)->forConversation($conversationId);
    app(IMessageRepository::class)->markRead($conversationId, auth()->id());
};

$sendMessage = function () {
    if (! trim($this->newMessage) || ! $this->activeConversation) {
        return;
    }

    $conversation = Conversation::find($this->activeConversation);

    if (! $conversation) {
        return;
    }

    app(IMessageRepository::class)->create([
        'conversation_id' => $this->activeConversation,
        'sender_id'       => auth()->id(),
        'body'            => trim($this->newMessage),
    ]);

    $conversation->update(['last_message_at' => now()]);
    $this->messages = app(IMessageRepository::class)->forConversation($this->activeConversation);
    $this->newMessage = '';
};

?>

<div class="flex h-[calc(100vh-4rem)] max-w-6xl mx-auto">
    <aside class="w-72 border-r border-[#3D2B1F]/10 overflow-y-auto">
        <div class="p-4">
            <flux:input placeholder="Search chats..." />
        </div>
        @foreach ($conversations as $conv)
            @php
                if ($conv->isScheduleGroup()) {
                    $label = ($conv->schedule?->language?->name_en ?? 'Schedule').' group';
                    $initial = 'G';
                    $href = route('schedules.show', $conv->schedule_id);
                } else {
                    $partner = $conv->user_a_id === auth()->id() ? $conv->userB : $conv->userA;
                    $label = $partner->profile->display_name ?? 'User';
                    $initial = strtoupper(substr($label, 0, 1));
                    $href = null;
                }
            @endphp
            @if ($href)
                <a href="{{ $href }}" wire:navigate
                    class="block w-full text-left p-4 hover:bg-[#FFF0E0] transition-colors">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F] text-sm truncate">{{ $label }}</p>
                            <p class="text-xs text-[#3D2B1F]/45">Group chat</p>
                        </div>
                    </div>
                </a>
            @else
                <button wire:click="selectConversation({{ $conv->id }})"
                    class="w-full text-left p-4 hover:bg-[#FFF0E0] transition-colors {{ $activeConversation === $conv->id ? 'bg-[#FFF0E0]' : '' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold">
                            {{ $initial }}
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F] text-sm truncate">{{ $label }}</p>
                        </div>
                    </div>
                </button>
            @endif
        @endforeach
    </aside>

    <main class="flex-1 flex flex-col">
        @if ($activeConversation)
            <div class="flex-1 overflow-y-auto p-6 space-y-4">
                @foreach ($messages as $msg)
                    <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-sm px-4 py-2 rounded-2xl text-sm {{ $msg->sender_id === auth()->id() ? 'bg-[#FF8C42] text-white' : 'bg-[#FFF0E0] text-[#3D2B1F]' }}">
                            {{ $msg->body }}
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-[#3D2B1F]/10 flex gap-3">
                <flux:textarea wire:model="newMessage" placeholder="Type a message..." rows="1" class="flex-1" />
                <flux:button wire:click="sendMessage" variant="primary">Send</flux:button>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center text-[#3D2B1F]/40">
                Select a conversation to start chatting
            </div>
        @endif
    </main>
</div>
