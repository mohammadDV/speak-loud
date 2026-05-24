<?php

use function Livewire\Volt\{state, mount, computed};
use App\Actions\SyncScheduleGroupChat;
use App\Models\Schedule;
use App\Repositories\Contracts\IMessageRepository;
use App\Support\ScheduleAccess;

state([
    'schedule'     => null,
    'messages'     => [],
    'newMessage'   => '',
    'canViewChat'  => false,
]);

mount(function (Schedule $schedule, SyncScheduleGroupChat $syncGroupChat) {
    $schedule->load([
        'user.profile',
        'user.languages',
        'language',
        'recurringRule',
        'oneTimeSlot',
        'claims.sender.profile',
        'groupConversation',
    ]);

    $userId = auth()->id();

    if (! ScheduleAccess::canView($schedule, $userId)) {
        abort(403);
    }

    $this->schedule    = $schedule;
    $this->canViewChat = $userId && ScheduleAccess::canAccessGroupChat($schedule, $userId);

    if ($this->canViewChat) {
        $syncGroupChat->execute($schedule);
        $schedule->load('groupConversation');
        $this->schedule = $schedule;
        $this->loadMessages();
    }
});

$members = computed(function () {
    if (! $this->schedule) {
        return collect();
    }

    $host = collect([[
        'user'   => $this->schedule->user,
        'role'   => 'Host',
        'status' => null,
    ]]);

    $accepted = $this->schedule->claims
        ->where('status', 'accepted')
        ->map(fn ($claim) => [
            'user'   => $claim->sender,
            'role'   => 'Member',
            'status' => 'accepted',
        ]);

    return $host->concat($accepted)->values();
});

$pendingClaims = computed(function () {
    if (! $this->schedule || ! $this->schedule->isHost(auth()->id())) {
        return collect();
    }

    return $this->schedule->claims
        ->where('status', 'pending')
        ->values();
});

$loadMessages = function () {
    if (! $this->canViewChat || ! $this->schedule?->groupConversation) {
        return;
    }

    $repo = app(IMessageRepository::class);
    $this->messages = $repo->forConversation($this->schedule->groupConversation->id);
    $repo->markRead($this->schedule->groupConversation->id, auth()->id());
};

$sendMessage = function () {
    if (! trim($this->newMessage) || ! $this->canViewChat || ! $this->schedule?->groupConversation) {
        return;
    }

    $conversation = $this->schedule->groupConversation;

    app(IMessageRepository::class)->create([
        'conversation_id' => $conversation->id,
        'sender_id'       => auth()->id(),
        'body'            => trim($this->newMessage),
    ]);

    $conversation->update(['last_message_at' => now()]);
    $this->newMessage = '';
    $this->loadMessages();
};

?>

<div class="max-w-5xl mx-auto px-4 py-8">
    @if ($schedule)
        <div class="mb-6">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('discover') }}" wire:navigate class="text-sm text-[#FF8C42] hover:underline">← Back</a>
            <h1 class="text-2xl font-bold text-[#3D2B1F] mt-2">
                {{ $schedule->language->name_en }} practice slot
            </h1>
            @if ($schedule->isHost(auth()->id()) && $schedule->title)
                <p class="text-[#3D2B1F]/60 mt-1">{{ $schedule->title }}</p>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <div class="lg:col-span-2 space-y-6">
                <x-schedule-details :schedule="$schedule" />

                <flux:card class="bg-[#FFF0E0] p-4">
                    <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Members</h2>
                    <ul class="space-y-3">
                        @foreach ($this->members as $member)
                            @php $profile = $member['user']?->profile; @endphp
                            <li class="flex items-center gap-3">
                                <div class="w-10 h-10 shrink-0 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold">
                                    {{ strtoupper(substr($profile->display_name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    @if ($profile?->profile_slug)
                                        <a href="{{ route('users.show', $profile->profile_slug) }}" wire:navigate class="font-medium text-[#3D2B1F] hover:text-[#FF8C42] truncate block">
                                            {{ $profile->display_name ?? 'User' }}
                                        </a>
                                    @else
                                        <p class="font-medium text-[#3D2B1F] truncate">{{ $profile->display_name ?? 'User' }}</p>
                                    @endif
                                    <p class="text-xs text-[#3D2B1F]/50">{{ $member['role'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @if ($this->pendingClaims->isNotEmpty())
                        <div class="mt-5 pt-4 border-t border-[#3D2B1F]/10">
                            <h3 class="text-xs font-semibold text-[#3D2B1F]/50 uppercase tracking-wide mb-2">Pending claims</h3>
                            <ul class="space-y-2">
                                @foreach ($this->pendingClaims as $claim)
                                    @php $profile = $claim->sender?->profile; @endphp
                                    <li class="flex items-center gap-2 text-sm text-[#3D2B1F]/70">
                                        <span class="w-8 h-8 shrink-0 rounded-full bg-[#FFD166]/50 flex items-center justify-center text-xs font-bold text-[#3D2B1F]">
                                            {{ strtoupper(substr($profile->display_name ?? '?', 0, 1)) }}
                                        </span>
                                        {{ $profile->display_name ?? 'User' }}
                                    </li>
                                @endforeach
                            </ul>
                            <a href="{{ route('claims') }}" wire:navigate class="inline-block mt-3 text-sm text-[#FF8C42] hover:underline">Manage on Claims →</a>
                        </div>
                    @endif
                </flux:card>

                @if ($schedule->isHost(auth()->id()))
                    <flux:button href="{{ route('schedule') }}" wire:navigate variant="ghost" class="w-full">Edit in My Schedule</flux:button>
                @endif
            </div>

            <div class="lg:col-span-3">
                <flux:card class="bg-[#FFF8F0] !flex !flex-col min-h-[420px] lg:min-h-[520px]">
                    <div class="px-4 py-3 border-b border-[#3D2B1F]/10">
                        <h2 class="font-semibold text-[#3D2B1F]">Group chat</h2>
                        <p class="text-xs text-[#3D2B1F]/50 mt-0.5">Host and accepted members only</p>
                    </div>

                    @if ($canViewChat)
                        <div class="flex-1 overflow-y-auto p-4 space-y-3 min-h-[280px]">
                            @forelse ($messages as $msg)
                                @php
                                    $isMine = $msg->sender_id === auth()->id();
                                    $senderName = $msg->sender?->profile?->display_name ?? 'User';
                                @endphp
                                <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-[85%]">
                                        @if (! $isMine)
                                            <p class="text-[10px] text-[#3D2B1F]/45 mb-0.5 px-1">{{ $senderName }}</p>
                                        @endif
                                        <div class="px-3 py-2 rounded-2xl text-sm {{ $isMine ? 'bg-[#FF8C42] text-white' : 'bg-[#FFF0E0] text-[#3D2B1F]' }}">
                                            {{ $msg->body }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-center text-[#3D2B1F]/40 text-sm py-8">No messages yet. Say hello to the group.</p>
                            @endforelse
                        </div>

                        <div class="p-4 border-t border-[#3D2B1F]/10 flex gap-3">
                            <flux:textarea wire:model="newMessage" placeholder="Message the group..." rows="1" class="flex-1" />
                            <flux:button wire:click="sendMessage" variant="primary">Send</flux:button>
                        </div>
                    @else
                        <div class="flex-1 flex items-center justify-center p-8 text-center text-sm text-[#3D2B1F]/50">
                            @if ($schedule->hasAcceptedMember(auth()->id()))
                                <p>Group chat is loading…</p>
                            @elseif ($schedule->claims->where('sender_id', auth()->id())->where('status', 'pending')->isNotEmpty())
                                <p>Group chat opens once the host accepts your claim.</p>
                            @else
                                <p>Send a claim and get accepted to join the group chat.</p>
                                <a href="{{ route('discover') }}" wire:navigate class="mt-3 inline-block text-[#FF8C42] hover:underline">Browse open slots</a>
                            @endif
                        </div>
                    @endif
                </flux:card>
            </div>
        </div>
    @endif
</div>
