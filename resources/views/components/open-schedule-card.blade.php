@props([
    'schedule',
    'layout' => 'grid',
    'claimAction' => 'openClaimModal',
])

@php
    use App\Support\CountryCodes;
    use App\Support\UtcTime;

    $profile = $schedule->user?->profile;
    $hostCountry = CountryCodes::name($profile?->country_code);
    $accepted = $schedule->accepted_claims_count ?? $schedule->claims->where('status', 'accepted')->count();
    $spotsLeft = max(0, $schedule->max_participants - $accepted);
    $myClaim = $schedule->claims->first();
    $isFull = $spotsLeft === 0;

    $when = UtcTime::scheduleWhen($schedule) ?? '—';

    if ($schedule->recurringRule) {
        $badge = 'Weekly';
    } elseif ($schedule->oneTimeSlot) {
        $badge = 'One-off';
    } else {
        $badge = $schedule->type === 'recurring' ? 'Weekly' : 'One-off';
    }
@endphp

@if ($layout === 'list')
    <flux:card class="bg-[#FFF0E0]">
        <div class="flex flex-col sm:flex-row sm:items-start gap-4 p-4">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <div class="w-12 h-12 shrink-0 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold text-lg">
                    {{ strtoupper(substr($profile->display_name ?? '?', 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        @if ($profile?->profile_slug)
                            <a href="{{ route('users.show', $profile->profile_slug) }}" wire:navigate class="font-semibold text-[#3D2B1F] hover:text-[#FF8C42]">{{ $profile->display_name ?? 'Host' }}</a>
                        @else
                            <p class="font-semibold text-[#3D2B1F]">{{ $profile->display_name ?? 'Host' }}</p>
                        @endif
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]' }}">{{ $badge }}</span>
                        <span class="text-xs text-[#3D2B1F]/50">{{ $schedule->language->name_en }}</span>
                        @if ($hostCountry)
                            <span class="text-xs text-[#3D2B1F]/45">· {{ $hostCountry }}</span>
                        @endif
                    </div>
                    <p class="text-sm text-[#3D2B1F]/60 mt-0.5">{{ $when }}</p>
                    @if ($schedule->description)
                        <p class="text-sm text-[#3D2B1F]/70 mt-2 line-clamp-2">{{ $schedule->description }}</p>
                    @endif
                    @if ($schedule->user?->interests?->isNotEmpty())
                        <x-interest-tags :interests="$schedule->user->interests" :limit="4" size="xs" class="mt-2" />
                    @endif
                    <p class="text-xs text-[#3D2B1F]/45 mt-2">{{ $spotsLeft }} {{ Str::plural('spot', $spotsLeft) }} left</p>
                    <a href="{{ route('schedules.show', $schedule) }}" wire:navigate class="inline-block mt-2 text-sm text-[#FF8C42] hover:underline">View details</a>
                </div>
            </div>
            <div class="shrink-0 sm:pt-1">
                @if ($myClaim?->status === 'pending')
                    <flux:button type="button" variant="ghost" size="sm" disabled>Claim pending</flux:button>
                @elseif ($myClaim?->status === 'accepted')
                    <flux:button href="{{ route('schedules.show', $schedule) }}" wire:navigate variant="primary" size="sm">Open slot</flux:button>
                @elseif ($isFull)
                    <flux:button type="button" variant="ghost" size="sm" disabled>Full</flux:button>
                @else
                    <flux:button type="button" variant="primary" size="sm" wire:click="{{ $claimAction }}({{ $schedule->id }})">Send claim</flux:button>
                @endif
            </div>
        </div>
    </flux:card>
@else
    <div class="h-full">
        <flux:card class="bg-[#FFF0E0] hover:shadow-md transition-shadow !flex !flex-col h-full min-h-[280px]">
            <div class="h-14 shrink-0 rounded-t-lg" style="background: linear-gradient(135deg, #FF8C42, #FFD166);"></div>
            <div class="px-4 pb-4 flex flex-col flex-1 -mt-8 min-h-0">
                <div class="w-12 h-12 shrink-0 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold text-lg border-2 border-[#FFF0E0]">
                    {{ strtoupper(substr($profile->display_name ?? '?', 0, 1)) }}
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                    @if ($profile?->profile_slug)
                        <a href="{{ route('users.show', $profile->profile_slug) }}" wire:navigate class="font-semibold text-[#3D2B1F] leading-snug hover:text-[#FF8C42]">{{ $profile->display_name ?? 'Host' }}</a>
                    @else
                        <p class="font-semibold text-[#3D2B1F] leading-snug">{{ $profile->display_name ?? 'Host' }}</p>
                    @endif
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]' }}">{{ $badge }}</span>
                </div>
                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">
                    {{ $schedule->language->name_en }} · {{ $when }}@if ($hostCountry) · {{ $hostCountry }}@endif
                </p>

                <div class="flex-1 mt-2 min-h-[3.75rem] space-y-2">
                    @if ($schedule->description)
                        <p class="text-xs text-[#3D2B1F]/65 line-clamp-3">{{ $schedule->description }}</p>
                    @endif
                    @if ($schedule->user?->interests?->isNotEmpty())
                        <x-interest-tags :interests="$schedule->user->interests" :limit="3" size="xs" />
                    @endif
                </div>

                <div class="mt-auto shrink-0 pt-3">
                    <p class="text-[11px] text-[#3D2B1F]/45 mb-2">{{ $spotsLeft }} {{ Str::plural('spot', $spotsLeft) }} left</p>
                    <a href="{{ route('schedules.show', $schedule) }}" wire:navigate class="inline-block mb-2 text-xs text-[#FF8C42] hover:underline">View details</a>
                    @if ($myClaim?->status === 'pending')
                        <flux:button type="button" variant="ghost" size="sm" class="w-full" disabled>Claim pending</flux:button>
                    @elseif ($myClaim?->status === 'accepted')
                        <flux:button href="{{ route('schedules.show', $schedule) }}" wire:navigate variant="primary" size="sm" class="w-full">Open slot</flux:button>
                    @elseif ($isFull)
                        <flux:button type="button" variant="ghost" size="sm" class="w-full" disabled>Full</flux:button>
                    @else
                        <flux:button type="button" variant="primary" size="sm" class="w-full" wire:click="{{ $claimAction }}({{ $schedule->id }})">Send claim</flux:button>
                    @endif
                </div>
            </div>
        </flux:card>
    </div>
@endif
