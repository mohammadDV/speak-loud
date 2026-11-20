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
            'route' => 'learn.index',
            'label' => 'Learn',
            'active' => request()->routeIs('learn.index', 'learn.*'),
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.908.216-1.79.496-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342"/>',
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
                wire:navigate
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

<a
    href="{{ route('schedule') }}"
    wire:navigate
    class="md:hidden fixed z-50 end-4 bottom-[calc(3.75rem+env(safe-area-inset-bottom))] inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#FF8C42] text-white shadow-[0_8px_24px_rgba(255,140,66,.45)] hover:bg-[#e67a35] transition-colors"
    aria-label="Open schedule"
>
    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
    </svg>
</a>
