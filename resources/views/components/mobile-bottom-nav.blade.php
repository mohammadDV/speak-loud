@php
    $tabs = [
        [
            'route' => 'home',
            'label' => 'Home',
            'active' => request()->routeIs('home'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9Z"/>',
        ],
        [
            'route' => 'discover',
            'label' => 'Discover',
            'active' => request()->routeIs('discover'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>',
        ],
        [
            'route' => 'schedule',
            'label' => 'Schedule',
            'active' => request()->routeIs('schedule', 'schedules.*'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>',
        ],
        [
            'route' => 'messages',
            'label' => 'Messages',
            'active' => request()->routeIs('messages*'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9 4.5 2.25-4.5c.84-1.68 2.55-2.75 4.38-2.75h6.75a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3H9.75L3 18.75Z"/>',
        ],
        [
            'route' => 'profile',
            'label' => 'Profile',
            'active' => request()->routeIs('profile*', 'tickets.*'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>',
        ],
    ];
@endphp

<nav
    class="fixed bottom-0 inset-x-0 z-40 md:hidden border-t border-black/[0.06] bg-[#FFF8F0]/95 backdrop-blur pb-[env(safe-area-inset-bottom)]"
    aria-label="Quick access"
>
    <div class="grid grid-cols-5 h-14 max-w-lg mx-auto">
        @foreach ($tabs as $tab)
            <a
                href="{{ route($tab['route']) }}"
                @class([
                    'flex flex-col items-center justify-center gap-0.5 text-[10px] font-medium transition-colors',
                    'text-[#FF8C42]' => $tab['active'],
                    'text-[#3D2B1F]/45 hover:text-[#3D2B1F]' => ! $tab['active'],
                ])
            >
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">{!! $tab['icon'] !!}</svg>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
