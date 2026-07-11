<?php

use App\Support\CountryCodes;
use App\Support\UserLanguageLevels;
use function Livewire\Volt\{computed};

$profile = computed(fn () => auth()->user()?->load(['profile', 'languages.language', 'interests']));

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-profile-nav>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm">
                    Log out
                </flux:button>
            </form>
        </x-profile-nav>
    </div>

    @if (! auth()->user()->hasVerifiedEmail())
        <div class="mb-6 p-4 bg-amber-50 text-amber-900 rounded-lg text-sm ring-1 ring-amber-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <span>Please verify your email to use schedules, claims, and messages.</span>
            <a href="{{ route('verification.notice') }}" wire:navigate class="text-[#FF8C42] font-semibold hover:underline shrink-0">
                Verify email
            </a>
        </div>
    @endif

    @if ($this->profile?->profile)
        @php
            $user = $this->profile;
            $profile = $user->profile;
            $countryName = CountryCodes::name($profile->country_code);
            $backgroundUrl = $profile->backgroundImageUrl();
            $avatarUrl = $profile->profileImageUrl();
        @endphp

        <flux:card class="overflow-hidden bg-[#FFF0E0] border-0 shadow-sm">
            @if ($backgroundUrl)
                <div
                    class="h-36 sm:h-44 bg-center bg-cover"
                    style="background-image: url('{{ $backgroundUrl }}');"
                    role="img"
                    aria-label="Profile background"
                ></div>
            @else
                <div class="h-36 sm:h-44" style="background: linear-gradient(135deg, #FF8C42 0%, #FFD166 100%);"></div>
            @endif

            <div class="px-5 sm:px-8 pb-8 -mt-14 sm:-mt-16">
                <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                    @if ($avatarUrl)
                        <img
                            src="{{ $avatarUrl }}"
                            alt="Profile picture of {{ $profile->display_name }}"
                            class="w-24 h-24 sm:w-28 sm:h-28 rounded-full object-cover border-4 border-[#FFF8F0] shadow-md shrink-0"
                        >
                    @else
                        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-[#FF8C42] border-4 border-[#FFF8F0] flex items-center justify-center text-white text-3xl font-bold shadow-md shrink-0">
                            {{ strtoupper(substr($profile->display_name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div class="mt-5">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#3D2B1F]">{{ $profile->display_name }}</h1>

                    <div class="flex flex-wrap items-center gap-2 mt-3">
                        @if ($profile->profile_slug)
                            <a href="{{ route('users.show', $profile->profile_slug) }}" wire:navigate class="text-sm text-[#FF8C42] hover:underline break-all">
                                {{ route('users.show', $profile->profile_slug) }}
                            </a>
                        @endif
                        @if ($profile->is_private)
                            <span class="text-xs font-medium bg-[#3D2B1F]/10 text-[#3D2B1F]/70 px-2.5 py-1 rounded-full">Private</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center gap-2 mt-4">
                        @if ($countryName)
                            <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-[#FFF8F0] text-[#3D2B1F] px-3 py-1.5 rounded-full ring-1 ring-[#3D2B1F]/10">
                                <span class="uppercase tracking-wide text-[#FF8C42]">{{ $profile->country_code }}</span>
                                {{ $countryName }}
                            </span>
                        @else
                            <span class="text-xs text-[#3D2B1F]/45 bg-[#FFF8F0] px-3 py-1.5 rounded-full ring-1 ring-[#3D2B1F]/10">
                                Add your country in Edit profile
                            </span>
                        @endif
                    </div>

                    @if ($profile->bio)
                        <p class="mt-5 text-[#3D2B1F]/75 leading-relaxed max-w-prose">{{ $profile->bio }}</p>
                    @endif

                    @if ($user->languages->isNotEmpty())
                        <div class="mt-8">
                            <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Languages</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($user->languages as $userLanguage)
                                    <span class="text-xs bg-[#FFF8F0] text-[#3D2B1F] px-3 py-1.5 rounded-lg ring-1 ring-[#FF8C42]/20">
                                        {{ $userLanguage->language->name_en }}
                                        <span class="text-[#3D2B1F]/45">· {{ UserLanguageLevels::labels()[$userLanguage->level] ?? str_replace('_', ' ', $userLanguage->level) }}</span>
                                        <span class="text-[#3D2B1F]/35">· {{ UserLanguageLevels::typeLabel($userLanguage->type) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="mt-8">
                            <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Languages</h2>
                            <p class="text-sm text-[#3D2B1F]/50">
                                No languages yet.
                                <a href="{{ route('profile.edit') }}" wire:navigate class="text-[#FF8C42] hover:underline">Add languages</a>
                                so partners can see your level on Discover.
                            </p>
                        </div>
                    @endif

                    <div class="mt-6">
                        <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Interests</h2>
                        @if ($user->interests->isNotEmpty())
                            <x-interest-tags :interests="$user->interests" />
                        @else
                            <p class="text-sm text-[#3D2B1F]/50">
                                No interests yet.
                                <a href="{{ route('profile.edit') }}" wire:navigate class="text-[#FF8C42] hover:underline">Add interests</a>
                                so partners can find you on Discover.
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>
    @else
        <flux:card class="bg-[#FFF0E0] p-10 text-center">
            <p class="text-[#3D2B1F]/50 mb-4">Your profile isn't set up yet.</p>
            <flux:button href="{{ route('profile.edit') }}" variant="primary">Set up profile</flux:button>
        </flux:card>
    @endif
</div>
