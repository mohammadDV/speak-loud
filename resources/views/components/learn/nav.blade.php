@props([
    'active' => 'dashboard',
])

@php
    $tabs = [
        [
            'key' => 'dashboard',
            'label' => 'Dashboard',
            'route' => 'learn.index',
            'auth' => false,
            'enabled' => true,
        ],
        [
            'key' => 'leaderboard',
            'label' => 'Leaderboard',
            'route' => 'learn.leaderboard',
            'auth' => false,
            'enabled' => (bool) config('gamification.leaderboard.enabled', true),
        ],
        [
            'key' => 'progress',
            'label' => 'Progress',
            'route' => 'learn.progress',
            'auth' => true,
            'enabled' => true,
        ],
    ];
@endphp

<nav aria-label="Learn sections" class="mt-4">
    <div class="inline-flex w-full sm:w-auto rounded-2xl bg-[#3D2B1F]/[0.06] p-1.5 gap-1">
        @foreach ($tabs as $tab)
            @continue(! $tab['enabled'])
            @continue($tab['auth'] && ! auth()->check())

            @php($isActive = $active === $tab['key'])

            <a
                href="{{ route($tab['route']) }}"
                wire:navigate
                @class([
                    'flex-1 sm:flex-none text-center rounded-xl px-4 py-2.5 text-sm font-semibold transition-all duration-200',
                    'bg-white text-[#3D2B1F] shadow-sm ring-1 ring-[#3D2B1F]/10' => $isActive,
                    'text-[#3D2B1F]/55 hover:text-[#3D2B1F] hover:bg-white/50' => ! $isActive,
                ])
                @if ($isActive) aria-current="page" @endif
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
