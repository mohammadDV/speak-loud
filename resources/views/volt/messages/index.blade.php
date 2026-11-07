<?php

use App\Models\Conversation;
use function Livewire\Volt\{state, mount, title, hydrate};
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;
use App\Support\Seo;

state([
    'conversations'      => [],
    'activeConversation' => null,
    'messages'           => [],
    'newMessage'         => '',
]);

$refreshConversations = function () {
    $this->conversations = app(IConversationRepository::class)->forUser(auth()->id());
};

mount(function () {
    $this->refreshConversations();

    Seo::share([
        'seoTitle' => 'Messages',
        'seoDescription' => 'Your SpeakLoudMate conversations.',
        'seoUrl' => route('messages'),
        'seoRobots' => 'noindex, nofollow',
    ]);
});

hydrate(function () {
    // Eloquent relations are dropped on rehydration; refresh once per request.
    $this->refreshConversations();

    if ($this->activeConversation) {
        $this->messages = app(IMessageRepository::class)->forConversation($this->activeConversation);
    }
});

title(fn () => Seo::pageTitle('Messages'));

$selectConversation = function (int $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (! $conversation || ! $conversation->userCanAccess(auth()->id())) {
        return;
    }

    if ($conversation->isScheduleGroup()) {
        $conversation->loadMissing('schedule');
        $this->redirect(route('schedules.show', $conversation->schedule), navigate: true);

        return;
    }

    $this->activeConversation = $conversationId;
    $this->messages           = app(IMessageRepository::class)->forConversation($conversationId);
    app(IMessageRepository::class)->markRead($conversationId, auth()->id());
};

$clearConversation = function () {
    $this->activeConversation = null;
    $this->messages = [];
    $this->newMessage = '';
};

$sendMessage = function () {
    if (! trim($this->newMessage) || ! $this->activeConversation) {
        return;
    }

    $conversation = Conversation::find($this->activeConversation);

    if (! $conversation || ! $conversation->userCanAccess(auth()->id())) {
        abort(403);
    }

    if ($conversation->isScheduleGroup()) {
        $conversation->loadMissing('schedule');
        $this->redirect(route('schedules.show', $conversation->schedule), navigate: true);

        return;
    }

    $this->validate([
        'newMessage' => 'required|string|max:5000',
    ]);

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

@php
    $activeConv = null;
    $activeLabel = null;
    $activeInitial = '?';
    $activeAvatarUrl = null;

    if ($activeConversation) {
        $activeConv = collect($conversations)->firstWhere('id', $activeConversation);

        if ($activeConv && ! $activeConv->isScheduleGroup()) {
            $partner = $activeConv->user_a_id === auth()->id() ? $activeConv->userB : $activeConv->userA;
            $activeLabel = $partner->profile->display_name ?? 'User';
            $activeInitial = strtoupper(substr($activeLabel, 0, 1));
            $activeAvatarUrl = $partner->profile?->profileImageUrl();
        }
    }
@endphp

<div
    class="flex h-[calc(100dvh-52px-3.5rem-env(safe-area-inset-bottom,0px))] md:h-[calc(100dvh-52px)] max-w-6xl mx-auto"
    x-data="{ chatOpen: @js((bool) $activeConversation) }"
    x-on:conversation-opened.window="chatOpen = true"
>
    <aside
        class="border-[#3D2B1F]/10 overflow-y-auto bg-[#FFF8F0] w-full md:w-72 md:border-r"
        x-bind:class="chatOpen ? 'hidden md:block' : 'block'"
    >
        <div class="p-4">
            <h1 class="text-lg font-bold text-[#3D2B1F] mb-3 md:sr-only">Messages</h1>
            <flux:input placeholder="Search chats..." />
        </div>

        @forelse ($conversations as $conv)
            @php
                if ($conv->isScheduleGroup()) {
                    $label = ($conv->schedule?->language?->name_en ?? 'Schedule').' group';
                    $initial = 'G';
                    $href = $conv->schedule
                        ? route('schedules.show', $conv->schedule)
                        : null;
                    $avatarUrl = null;
                } else {
                    $partner = $conv->user_a_id === auth()->id() ? $conv->userB : $conv->userA;
                    $label = $partner->profile->display_name ?? 'User';
                    $initial = strtoupper(substr($label, 0, 1));
                    $href = null;
                    $avatarUrl = $partner->profile?->profileImageUrl();
                }
            @endphp
            @if ($href)
                <a href="{{ $href }}" wire:navigate
                    class="block w-full text-left p-4 hover:bg-[#FFF0E0] transition-colors">
                    <div class="flex items-center gap-3">
                        @if ($avatarUrl)
                            <img
                                src="{{ $avatarUrl }}"
                                alt="Profile picture of {{ $label }}"
                                class="w-10 h-10 rounded-full object-cover ring-2 ring-[#FFF8F0]"
                            >
                        @else
                            <div class="w-10 h-10 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold">
                                {{ $initial }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F] text-sm truncate">{{ $label }}</p>
                            <p class="text-xs text-[#3D2B1F]/45">Group chat</p>
                        </div>
                    </div>
                </a>
            @else
                <button
                    wire:click="selectConversation({{ $conv->id }})"
                    x-on:click="chatOpen = true"
                    type="button"
                    class="w-full text-left p-4 hover:bg-[#FFF0E0] transition-colors {{ $activeConversation === $conv->id ? 'bg-[#FFF0E0]' : '' }}"
                >
                    <div class="flex items-center gap-3">
                        @if ($avatarUrl)
                            <img
                                src="{{ $avatarUrl }}"
                                alt="Profile picture of {{ $label }}"
                                class="w-10 h-10 rounded-full object-cover ring-2 ring-[#FFF8F0]"
                            >
                        @else
                            <div class="w-10 h-10 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold">
                                {{ $initial }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-[#3D2B1F] text-sm truncate">{{ $label }}</p>
                        </div>
                    </div>
                </button>
            @endif
        @empty
            <p class="p-6 text-sm text-[#3D2B1F]/45 text-center">No conversations yet.</p>
        @endforelse
    </aside>

    <main
        class="flex-1 flex-col min-w-0 bg-[#FFF8F0]"
        x-bind:class="chatOpen ? 'flex' : 'hidden md:flex'"
    >
        @if ($activeConversation)
            <div class="flex items-center gap-3 px-4 py-3 border-b border-[#3D2B1F]/10 shrink-0">
                <button
                    type="button"
                    x-on:click="chatOpen = false"
                    class="md:hidden text-sm font-medium text-[#FF8C42] hover:underline shrink-0"
                >
                    ← Chats
                </button>
                @if ($activeAvatarUrl)
                    <img
                        src="{{ $activeAvatarUrl }}"
                        alt="Profile picture of {{ $activeLabel }}"
                        class="w-9 h-9 rounded-full object-cover ring-2 ring-[#FFF8F0] shrink-0"
                    >
                @else
                    <div class="w-9 h-9 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold text-sm shrink-0">
                        {{ $activeInitial }}
                    </div>
                @endif
                <p class="font-semibold text-[#3D2B1F] text-sm truncate">{{ $activeLabel ?? 'Conversation' }}</p>
            </div>

            <div class="flex-1 overflow-y-auto p-4 md:p-6 space-y-4">
                @forelse ($messages as $msg)
                    <div class="flex {{ $msg->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] sm:max-w-sm px-4 py-2 rounded-2xl text-sm break-words {{ $msg->sender_id === auth()->id() ? 'bg-[#FF8C42] text-white' : 'bg-[#FFF0E0] text-[#3D2B1F]' }}">
                            {{ $msg->body }}
                        </div>
                    </div>
                @empty
                    <p class="text-center text-[#3D2B1F]/40 text-sm py-8">No messages yet. Say hello.</p>
                @endforelse
            </div>

            <div class="p-3 md:p-4 border-t border-[#3D2B1F]/10 flex gap-2 md:gap-3 shrink-0">
                <flux:textarea wire:model="newMessage" placeholder="Type a message..." rows="1" class="flex-1" />
                <flux:button wire:click="sendMessage" variant="primary">Send</flux:button>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center text-[#3D2B1F]/40 text-sm px-6 text-center">
                Select a conversation to start chatting
            </div>
        @endif
    </main>
</div>
