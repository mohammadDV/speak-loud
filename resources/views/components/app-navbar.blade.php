@php
    $authLinks = [
        ['discover', 'Discover', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>'],
        ['schedule', 'Schedule', '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
        ['messages', 'Messages', '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9 4.5 2.25-4.5c.84-1.68 2.55-2.75 4.38-2.75h6.75a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3H9.75L3 18.75Z"/>'],
        ['claims', 'Claims', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/>'],
    ];

    $publicLinks = [
        ['blog.index', 'Blog', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>'],
        ['faq.index', 'FAQ', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>'],
        ['donate', 'Donate', '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/>'],
    ];

    $guestLinks = [
        ['home', 'Home', '<path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9Z"/>'],
        ['discover', 'Discover', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>'],
    ];

    $navLinks = auth()->check()
        ? array_merge($authLinks, $publicLinks)
        : array_merge($guestLinks, $publicLinks);

    $drawerAppLinks = [
        ['schedule', 'Schedule'],
        ['claims', 'Claims'],
        ['discover', 'Discover'],
        ['messages', 'Messages'],
    ];

    $drawerAccountLinks = [
        ['profile', 'Profile'],
        ['profile.edit', 'Edit profile'],
        ['profile.security', 'Security'],
        ['tickets.index', 'Support tickets'],
    ];

    $drawerExploreLinks = [
        ['blog.index', 'Blog'],
        ['faq.index', 'FAQ'],
        ['about', 'About'],
        ['contact', 'Contact'],
        ['donate', 'Donate'],
        ['support', 'Support'],
        ['terms', 'Terms'],
    ];

    $navRouteIs = function (string $route): bool {
        $patterns = match ($route) {
            'schedule' => ['schedule', 'schedules.*'],
            'messages' => ['messages', 'messages.*'],
            'claims' => ['claims', 'claims.*'],
            'blog.index' => ['blog.index', 'blog.*'],
            'faq.index' => ['faq.index', 'faq.*'],
            'tickets.index' => ['tickets.*'],
            'profile' => ['profile'],
            'profile.edit' => ['profile.edit'],
            'profile.security' => ['profile.security'],
            default => [$route],
        };

        return request()->routeIs(...$patterns);
    };

    $drawerLinkClass = function (string $route) use ($navRouteIs): string {
        return $navRouteIs($route)
            ? 'flex items-center rounded-lg px-3 py-2.5 text-sm font-medium text-[#FF8C42] bg-[#FF8C42]/10'
            : 'flex items-center rounded-lg px-3 py-2.5 text-sm text-[#3D2B1F]/75 hover:bg-black/[0.04] hover:text-[#3D2B1F]';
    };
@endphp

<div data-mobile-nav data-open="false">
    <header class="sticky top-0 z-50 bg-[#FFF8F0]/95 backdrop-blur border-b border-black/[0.06]">
        <div class="max-w-6xl mx-auto px-5 h-[52px] flex items-center justify-between gap-4">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0" aria-label="SpeakLoudMate home">
                <x-brand-logo :size="28" />
            </a>

            <nav class="hidden md:flex items-center gap-1 overflow-x-auto">
                @foreach ($navLinks as [$route, $label, $icon])
                    <a href="{{ route($route) }}"
                        wire:navigate
                        @class([
                            'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] transition-all whitespace-nowrap',
                            'text-[#FF8C42] bg-[#FF8C42]/10 font-medium' => $navRouteIs($route),
                            'text-[#3D2B1F]/60 hover:text-[#3D2B1F] hover:bg-black/[0.04]' => ! $navRouteIs($route),
                        ])>
                        <svg class="w-[15px] h-[15px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">{!! $icon !!}</svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2 shrink-0">
                <button
                    type="button"
                    class="md:hidden inline-flex items-center justify-center w-9 h-9 rounded-lg text-[#3D2B1F]/70 hover:bg-black/[0.04] hover:text-[#3D2B1F] transition-colors"
                    data-mobile-nav-toggle
                    aria-expanded="false"
                    aria-controls="mobile-nav-drawer"
                    aria-label="Open menu"
                >
                    <svg data-mobile-nav-icon="open" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                    <svg data-mobile-nav-icon="close" class="w-5 h-5 hidden" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>

                @auth
                    <a href="{{ route('profile') }}"
                        wire:navigate
                        aria-label="Profile"
                        @class([
                            'hidden md:inline-flex w-8 h-8 rounded-full items-center justify-center transition-all',
                            'bg-[#FF8C42] text-white ring-2 ring-[#FF8C42]/30' => request()->routeIs('profile*', 'tickets.*'),
                            'bg-[#FF8C42] text-white hover:ring-2 hover:ring-[#FF8C42]/20' => ! request()->routeIs('profile*', 'tickets.*'),
                        ])>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v1.2h19.2v-1.2c0-3.2-6.4-4.8-9.6-4.8z"/>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-[13px] text-[#3D2B1F]/60 hover:text-[#3D2B1F] px-2 transition-colors">Sign in</a>
                    <a href="{{ route('register') }}" wire:navigate class="hidden md:inline-flex text-[13px] bg-[#FF8C42] text-white px-4 py-1.5 rounded-full font-semibold hover:bg-[#e67a35] transition-colors">
                        Start free
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <div
        data-mobile-nav-drawer
        id="mobile-nav-drawer"
        class="fixed inset-0 z-[60] md:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Mobile menu"
        hidden
    >
        <div class="absolute inset-0 bg-[#3D2B1F]/40" data-mobile-nav-close></div>

        <div class="absolute top-0 end-0 flex h-full w-[min(100%,20rem)] flex-col bg-[#FFF8F0] shadow-xl">
            <div class="flex h-[52px] items-center justify-between border-b border-black/[0.06] px-5 shrink-0">
                <span class="font-bold text-[15px] text-[#3D2B1F]">Menu</span>
                <button
                    type="button"
                    class="inline-flex items-center justify-center w-9 h-9 rounded-lg text-[#3D2B1F]/70 hover:bg-black/[0.04]"
                    data-mobile-nav-close
                    aria-label="Close menu"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-4 pb-24 space-y-6">
                @auth
                    <div>
                        <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#3D2B1F]/40">App</p>
                        <div class="space-y-0.5">
                            @foreach ($drawerAppLinks as [$route, $label])
                                <a href="{{ route($route) }}" wire:navigate class="{{ $drawerLinkClass($route) }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#3D2B1F]/40">Account</p>
                        <div class="space-y-0.5">
                            @foreach ($drawerAccountLinks as [$route, $label])
                                <a href="{{ route($route) }}" wire:navigate class="{{ $drawerLinkClass($route) }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div>
                        <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#3D2B1F]/40">Get started</p>
                        <div class="space-y-0.5">
                            <a href="{{ route('home') }}" wire:navigate class="{{ $drawerLinkClass('home') }}">Home</a>
                            <a href="{{ route('discover') }}" wire:navigate class="{{ $drawerLinkClass('discover') }}">Discover</a>
                            <a href="{{ route('login') }}" wire:navigate class="{{ $drawerLinkClass('login') }}">Sign in</a>
                            <a href="{{ route('register') }}" wire:navigate class="flex items-center rounded-lg px-3 py-2.5 text-sm font-semibold text-[#FF8C42]">Start free</a>
                        </div>
                    </div>
                @endauth

                <div>
                    <p class="px-3 mb-1.5 text-[11px] font-semibold uppercase tracking-wide text-[#3D2B1F]/40">Explore</p>
                    <div class="space-y-0.5">
                        @foreach ($drawerExploreLinks as [$route, $label])
                            <a href="{{ route($route) }}" wire:navigate class="{{ $drawerLinkClass($route) }}">{{ $label }}</a>
                        @endforeach
                    </div>
                </div>

                @auth
                    <div class="border-t border-black/[0.06] pt-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium text-[#3D2B1F]/75 hover:bg-black/[0.04] hover:text-[#3D2B1F]"
                            >
                                Log out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </div>

    <x-mobile-bottom-nav />
</div>
