<?php

use function Livewire\Volt\{state, mount, computed};
use App\Actions\SendClaim;
use App\Models\Schedule;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Support\CountryCodes;

state([
    'profileSlug'           => '',
    'showDirectClaimModal'  => false,
    'showScheduleClaimModal'=> false,
    'claimScheduleId'       => null,
    'claimMessage'          => '',
]);

mount(function (string $profileSlug) {
    $this->profileSlug = $profileSlug;

    $user = app(IUserRepository::class)->findPublicProfileBySlug($profileSlug);

    if (! $user) {
        abort(404);
    }

    if (auth()->id() === $user->id) {
        $this->redirect(route('profile'), navigate: true);

        return;
    }

    if (auth()->check() && app(IUserRepository::class)->areBlocked(auth()->id(), $user->id)) {
        abort(404);
    }

    if (auth()->check() && session()->has('pending_claim_schedule_id')) {
        $this->claimScheduleId        = session()->pull('pending_claim_schedule_id');
        $this->showScheduleClaimModal = true;
    }

    if (auth()->check() && session()->has('pending_direct_claim')) {
        $pending = session()->pull('pending_direct_claim');

        if (($pending['receiver_id'] ?? null) === $user->id) {
            $this->showDirectClaimModal = true;
        }
    }
});

$profileUser = computed(function () {
    if ($this->profileSlug === '') {
        return null;
    }

    return app(IUserRepository::class)->findPublicProfileBySlug($this->profileSlug);
});

$conversation = computed(function () {
    if (! auth()->check() || ! $this->profileUser) {
        return null;
    }

    return app(IConversationRepository::class)->findBetweenUsers(
        auth()->id(),
        $this->profileUser->id,
    );
});

$directClaim = computed(function () {
    if (! auth()->check() || ! $this->profileUser) {
        return null;
    }

    return app(IClaimRepository::class)->findDirectClaimBetweenUsers(
        auth()->id(),
        $this->profileUser->id,
    );
});

$activeSchedules = computed(function () {
    if (! $this->profileUser) {
        return collect();
    }

    return app(IScheduleRepository::class)->openSchedulesForHost(
        $this->profileUser->id,
        auth()->id(),
    );
});

$claimTarget = computed(function () {
    if (! $this->claimScheduleId) {
        return null;
    }

    return Schedule::query()
        ->with(['user.profile', 'user.languages', 'language', 'recurringRule', 'oneTimeSlot'])
        ->withCount([
            'claims as accepted_claims_count' => fn ($q) => $q->where('status', 'accepted'),
        ])
        ->find($this->claimScheduleId);
});

$openDirectClaimModal = function () {
    if (! auth()->check()) {
        session([
            'pending_direct_claim' => [
                'receiver_id'   => $this->profileUser->id,
                'profile_slug'  => $this->profileSlug,
            ],
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    $this->claimMessage         = '';
    $this->showDirectClaimModal = true;
    $this->resetValidation();
};

$closeDirectClaimModal = function () {
    $this->showDirectClaimModal = false;
    $this->claimMessage         = '';
};

$openScheduleClaimModal = function (int $scheduleId) {
    if (! auth()->check()) {
        session([
            'pending_claim_schedule_id' => $scheduleId,
            'pending_claim_return'      => route('users.show', $this->profileSlug),
        ]);

        return $this->redirect(route('login'), navigate: true);
    }

    $this->claimScheduleId        = $scheduleId;
    $this->claimMessage           = '';
    $this->showScheduleClaimModal = true;
    $this->resetValidation();
};

$closeScheduleClaimModal = function () {
    $this->showScheduleClaimModal = false;
    $this->claimScheduleId        = null;
    $this->claimMessage           = '';
};

$sendDirectClaim = function (SendClaim $action) {
    if (! auth()->check()) {
        return $this->redirect(route('login'), navigate: true);
    }

    $user = $this->profileUser;

    if (! $user) {
        abort(404);
    }

    $this->validate([
        'claimMessage' => 'nullable|string|max:500',
    ]);

    $action->execute([
        'sender_id'   => auth()->id(),
        'receiver_id' => $user->id,
        'message'     => trim($this->claimMessage) ?: null,
    ]);

    $this->closeDirectClaimModal();
};

$sendScheduleClaim = function (SendClaim $action) {
    if (! auth()->check()) {
        return $this->redirect(route('login'), navigate: true);
    }

    $schedule = Schedule::query()
        ->where('status', 'active')
        ->where('user_id', $this->profileUser->id)
        ->where('user_id', '!=', auth()->id())
        ->findOrFail($this->claimScheduleId);

    $this->validate([
        'claimMessage' => 'nullable|string|max:500',
    ]);

    $action->execute([
        'sender_id'   => auth()->id(),
        'receiver_id' => $schedule->user_id,
        'schedule_id' => $schedule->id,
        'message'     => trim($this->claimMessage) ?: null,
    ]);

    $this->closeScheduleClaimModal();
};

$levelLabels = [
    'beginner' => 'A1 – Beginner',
    'elementary' => 'A2 – Elementary',
    'intermediate' => 'B1 – Intermediate',
    'upper_intermediate' => 'B2 – Upper Intermediate',
    'advanced' => 'C1 – Advanced',
    'fluent' => 'C2 – Fluent',
];

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    @if ($this->profileUser?->profile)
        @php
            $user = $this->profileUser;
            $profile = $user->profile;
            $countryName = CountryCodes::name($profile->country_code);
            $conversation = $this->conversation;
            $directClaim = $this->directClaim;
            $isPrivate = (bool) $profile->is_private;
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

                <div class="mt-5 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-[#3D2B1F]">{{ $profile->display_name }}</h1>

                        @if ($isPrivate)
                            <p class="text-sm text-[#3D2B1F]/50 mt-2">This profile is private.</p>
                        @endif
                    </div>

                    @if (! $isPrivate)
                        <div class="shrink-0 flex flex-wrap gap-2">
                            @auth
                                @if ($conversation)
                                    <flux:button href="{{ route('messages.show', $conversation->id) }}" variant="primary" size="sm">
                                        Open chat
                                    </flux:button>
                                @elseif ($directClaim?->status === 'pending')
                                    <flux:button type="button" variant="ghost" size="sm" disabled>Claim pending</flux:button>
                                @else
                                    <flux:button type="button" wire:click="openDirectClaimModal" variant="primary" size="sm">
                                        Send claim
                                    </flux:button>
                                @endif
                            @else
                                <flux:button type="button" wire:click="openDirectClaimModal" variant="primary" size="sm">
                                    Sign in to connect
                                </flux:button>
                            @endauth
                        </div>
                    @elseif ($conversation)
                        <div class="shrink-0">
                            <flux:button href="{{ route('messages.show', $conversation->id) }}" variant="primary" size="sm">
                                Open chat
                            </flux:button>
                        </div>
                    @endif
                </div>

                @unless ($isPrivate)
                    @if ($countryName)
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium bg-[#FFF8F0] text-[#3D2B1F] px-3 py-1.5 rounded-full ring-1 ring-[#3D2B1F]/10 mt-4">
                            <span class="uppercase tracking-wide text-[#FF8C42]">{{ $profile->country_code }}</span>
                            {{ $countryName }}
                        </span>
                    @endif

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
                                        <span class="text-[#3D2B1F]/45">· {{ $levelLabels[$userLanguage->level] ?? str_replace('_', ' ', $userLanguage->level) }}</span>
                                        <span class="text-[#3D2B1F]/35">· {{ $userLanguage->type === 'native' ? 'Native' : 'Learning' }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($user->interests->isNotEmpty())
                        <div class="mt-6">
                            <h2 class="text-sm font-semibold text-[#3D2B1F] mb-3">Interests</h2>
                            <x-interest-tags :interests="$user->interests" />
                        </div>
                    @endif
                @endunless
            </div>
        </flux:card>

        @unless ($isPrivate)
            <section class="mt-10">
                <h2 class="text-xl font-bold text-[#3D2B1F] mb-4">Open slots</h2>

                @if ($this->activeSchedules->isEmpty())
                    <p class="text-sm text-[#3D2B1F]/50">No open slots right now.</p>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-stretch">
                        @foreach ($this->activeSchedules as $schedule)
                            <x-open-schedule-card
                                :schedule="$schedule"
                                layout="grid"
                                claim-action="openScheduleClaimModal"
                            />
                        @endforeach
                    </div>
                @endif
            </section>
        @endunless
    @else
        <flux:card class="bg-[#FFF0E0] p-10 text-center">
            <p class="text-[#3D2B1F]/50">This profile could not be found.</p>
            <flux:button href="{{ route('discover') }}" variant="primary" class="mt-4">Browse open slots</flux:button>
        </flux:card>
    @endif

    @if ($showDirectClaimModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeDirectClaimModal"></div>

            <div class="relative w-full max-w-md rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">Send claim</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">
                        Ask to practice with {{ $this->profileUser?->profile?->display_name ?? 'this host' }}. They can accept or decline on Claims.
                    </p>

                    <form wire:submit="sendDirectClaim" class="mt-5 space-y-4">
                        <flux:textarea
                            wire:model="claimMessage"
                            label="Message (optional)"
                            rows="3"
                            placeholder="e.g. Hi! I'd love to practice conversation — I'm B1 in English."
                        />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeDirectClaimModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Send claim</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($showScheduleClaimModal && $this->claimTarget)
        @php $schedule = $this->claimTarget; @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-[#3D2B1F]/40" wire:click="closeScheduleClaimModal"></div>

            <div class="relative w-full max-w-lg rounded-xl bg-[#FFF8F0] shadow-xl ring-1 ring-black/10 max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-[#3D2B1F]">Send claim</h2>
                    <p class="text-sm text-[#3D2B1F]/60 mt-1">
                        Request to join this slot hosted by {{ $schedule->user->profile->display_name ?? 'the host' }}.
                    </p>

                    <x-schedule-details :schedule="$schedule" class="mt-4" />

                    <form wire:submit="sendScheduleClaim" class="mt-6 space-y-4">
                        <flux:textarea
                            wire:model="claimMessage"
                            label="Message to host (optional)"
                            rows="3"
                            placeholder="e.g. Hi! I'd love to join this slot."
                        />

                        <div class="flex justify-end gap-3 pt-2">
                            <flux:button type="button" wire:click="closeScheduleClaimModal" variant="ghost">Cancel</flux:button>
                            <flux:button type="submit" variant="primary">Send claim</flux:button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
