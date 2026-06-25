@php
    $items = [
        ['route' => 'profile', 'label' => 'Profile', 'active' => request()->routeIs('profile')],
        ['route' => 'profile.edit', 'label' => 'Edit profile', 'active' => request()->routeIs('profile.edit')],
        ['route' => 'profile.security', 'label' => 'Security', 'active' => request()->routeIs('profile.security')],
        ['route' => 'tickets.index', 'label' => 'Support', 'active' => request()->routeIs('tickets.*')],
    ];
@endphp

<nav class="flex flex-wrap gap-2" aria-label="Profile menu">
    @foreach ($items as $item)
        <flux:button
            href="{{ route($item['route']) }}"
            wire:navigate
            size="sm"
            variant="{{ $item['active'] ? 'primary' : 'ghost' }}"
        >
            {{ $item['label'] }}
        </flux:button>
    @endforeach

    {{ $slot }}
</nav>
