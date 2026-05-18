<?php

use App\Support\CountryCodes;
use function Livewire\Volt\{computed};

$profile = computed(fn () => auth()->user()?->load(['profile', 'languages.language', 'tags']));

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    @if ($this->profile?->profile)
        @php
            $user = $this->profile;
            $profile = $user->profile;
            $countryName = CountryCodes::name($profile->country_code);
        @endphp

        <flux:card class="overflow-hidden bg-[#FFF0E0] border-0 shadow-sm">
            <div class="h-36 sm:h-44" style="background: linear-gradient(135deg, #FF8C42 0%, #FFD166 100%);"></div>

            <div class="px-5 sm:px-8 pb-8 -mt-14 sm:-mt-16">
                <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                    <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-[#FF8C42] border-4 border-[#FFF8F0] flex items-center justify-center text-white text-3xl font-bold shadow-md shrink-0">
                        {{ strtoupper(substr($profile->display_name, 0, 1)) }}
                    </div>
                    <flux:button href="{{ route('profile.edit') }}" variant="primary" size="sm" class="shrink-0 self-start sm:self-auto">
                        Edit profile
                    </flux:button>
                </div>

                <div class="mt-5">
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#3D2B1F]">{{ $profile->display_name }}</h1>
                    <p class="text-sm text-[#3D2B1F]/55 mt-1">{{ '@'.$profile->username }}</p>

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
                                        <span class="text-[#3D2B1F]/45">· {{ str_replace('_', ' ', $userLanguage->level) }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($user->tags->isNotEmpty())
                        <div class="mt-6">
                            <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Interests</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($user->tags as $tag)
                                    <span class="text-xs bg-[#FF8C42]/15 text-[#FF8C42] px-3 py-1 rounded-full">#{{ $tag->tag }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
