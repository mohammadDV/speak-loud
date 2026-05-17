<?php

use function Livewire\Volt\{state, mount};

state(['user' => null]);

mount(function () {
    $this->user = auth()->user()->load(['profile', 'languages.language', 'tags']);
});

?>

<div class="max-w-2xl mx-auto px-4 py-8">
    @if ($user?->profile)
        <div class="relative rounded-xl overflow-hidden mb-6" style="height: 160px; background: linear-gradient(135deg, #FF8C42, #FFD166);"></div>

        <div class="-mt-12 px-4 mb-6 flex items-end justify-between">
            <div class="w-20 h-20 rounded-full bg-[#FF8C42] border-4 border-[#FFF8F0] flex items-center justify-center text-white text-2xl font-bold">
                {{ strtoupper(substr($user->profile->display_name, 0, 1)) }}
            </div>
            <flux:button href="{{ route('profile.edit') }}" variant="ghost" size="sm">Edit profile</flux:button>
        </div>

        <div class="px-2">
            <h1 class="text-2xl font-bold text-[#3D2B1F]">{{ $user->profile->display_name }}</h1>
            <p class="text-sm text-[#3D2B1F]/50">@{{ $user->profile->username }}</p>

            @if ($user->profile->bio)
                <p class="mt-4 text-[#3D2B1F]/70">{{ $user->profile->bio }}</p>
            @endif

            @if ($user->tags->isNotEmpty())
                <div class="flex flex-wrap gap-2 mt-4">
                    @foreach ($user->tags as $tag)
                        <span class="text-xs bg-[#FFF0E0] text-[#3D2B1F] px-3 py-1 rounded-full">#{{ $tag->tag }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-12">
            <p class="text-[#3D2B1F]/50 mb-4">Your profile isn't set up yet.</p>
            <flux:button href="{{ route('profile.edit') }}" variant="primary">Set up profile</flux:button>
        </div>
    @endif
</div>
