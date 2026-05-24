@props(['schedule'])

@php
    use App\Support\CountryCodes;
    use App\Support\UtcTime;

    $profile = $schedule->user?->profile;
    $hostCountry = CountryCodes::name($profile?->country_code);
    $accepted = $schedule->accepted_claims_count
        ?? $schedule->claims?->where('status', 'accepted')->count()
        ?? 0;
    $spotsLeft = max(0, $schedule->max_participants - $accepted);

    $hostLevel = $schedule->user?->languages
        ?->firstWhere('language_id', $schedule->language_id)
        ?->level;

    $levelLabels = [
        'beginner' => 'A1 – Beginner',
        'elementary' => 'A2 – Elementary',
        'intermediate' => 'B1 – Intermediate',
        'upper_intermediate' => 'B2 – Upper Intermediate',
        'advanced' => 'C1 – Advanced',
        'fluent' => 'C2 – Fluent',
    ];

    $when = UtcTime::scheduleWhenLong($schedule) ?? '—';

    if ($schedule->recurringRule) {
        $typeLabel = 'Weekly';
        $typeClass = 'bg-[#FF8C42]/20 text-[#FF8C42]';
    } elseif ($schedule->oneTimeSlot) {
        $typeLabel = 'One-off';
        $typeClass = 'bg-[#FFD166]/30 text-[#3D2B1F]';
    } else {
        $typeLabel = $schedule->type === 'recurring' ? 'Weekly' : 'One-off';
        $typeClass = $schedule->type === 'recurring' ? 'bg-[#FF8C42]/20 text-[#FF8C42]' : 'bg-[#FFD166]/30 text-[#3D2B1F]';
    }
@endphp

<div {{ $attributes->merge(['class' => 'rounded-lg bg-[#FFF0E0] p-4 space-y-3 text-sm text-[#3D2B1F]']) }}>
    <div class="flex items-start gap-3">
        <div class="w-11 h-11 shrink-0 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold text-base">
            {{ strtoupper(substr($profile->display_name ?? '?', 0, 1)) }}
        </div>
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-[#3D2B1F]">{{ $profile->display_name ?? 'Host' }}</p>
            @if ($hostCountry)
                <p class="text-xs text-[#3D2B1F]/50 mt-0.5">{{ $hostCountry }}</p>
            @endif
        </div>
        <span class="text-xs px-2 py-0.5 rounded-full shrink-0 {{ $typeClass }}">{{ $typeLabel }}</span>
    </div>

    @if ($schedule->isHost(auth()->id()) && $schedule->title)
        <p class="font-semibold text-[#3D2B1F]">{{ $schedule->title }}</p>
    @endif

    <dl class="grid gap-2 text-[#3D2B1F]/80">
        <div class="flex flex-wrap gap-x-2">
            <dt class="text-[#3D2B1F]/50 shrink-0">Language</dt>
            <dd class="font-medium text-[#3D2B1F]">{{ $schedule->language->name_en ?? '—' }}</dd>
        </div>

        @if ($hostLevel && isset($levelLabels[$hostLevel]))
            <div class="flex flex-wrap gap-x-2">
                <dt class="text-[#3D2B1F]/50 shrink-0">Host level</dt>
                <dd>{{ $levelLabels[$hostLevel] }}</dd>
            </div>
        @endif

        <div class="flex flex-wrap gap-x-2">
            <dt class="text-[#3D2B1F]/50 shrink-0">When</dt>
            <dd>{{ $when }}</dd>
        </div>

        @if ($schedule->user?->interests?->isNotEmpty())
            <div>
                <dt class="text-[#3D2B1F]/50 text-xs mb-1">Host interests</dt>
                <dd><x-interest-tags :interests="$schedule->user->interests" :limit="6" size="xs" /></dd>
            </div>
        @endif

        <div class="flex flex-wrap gap-x-2">
            <dt class="text-[#3D2B1F]/50 shrink-0">Capacity</dt>
            <dd>
                {{ $spotsLeft }} {{ Str::plural('spot', $spotsLeft) }} left
                <span class="text-[#3D2B1F]/45">(max {{ $schedule->max_participants }} {{ Str::plural('participant', $schedule->max_participants) }})</span>
            </dd>
        </div>
    </dl>

    @if ($schedule->description)
        <div>
            <p class="text-xs text-[#3D2B1F]/50 mb-1">Session rules</p>
            <p class="text-[#3D2B1F]/80 whitespace-pre-wrap">{{ $schedule->description }}</p>
        </div>
    @endif
</div>
