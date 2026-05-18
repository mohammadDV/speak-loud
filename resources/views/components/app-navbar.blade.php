@php
    $authLinks = [
        ['discover', 'Discover', '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>'],
        ['schedule', 'Schedule', '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
        ['messages', 'Messages', '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9 4.5 2.25-4.5c.84-1.68 2.55-2.75 4.38-2.75h6.75a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3H9.75L3 18.75Z"/>'],
        ['claims', 'Claims', '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z"/>'],
    ];

    $publicLinks = [
        ['blog.index', 'Blog', '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>'],
    ];

    $navLinks = auth()->check()
        ? array_merge($authLinks, $publicLinks)
        : array_merge([['home', 'Home', '<path stroke-linecap="round" stroke-linejoin="round" d="m3 9 9-7 9 7v11a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1V9Z"/>']], $publicLinks);
@endphp

<header class="sticky top-0 z-50 bg-[#FFF8F0]/95 backdrop-blur border-b border-black/[0.06]">
    <div class="max-w-6xl mx-auto px-5 h-[52px] flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
            <span class="w-7 h-7 rounded-lg bg-[#FF8C42] text-white font-black text-sm flex items-center justify-center leading-none select-none">S</span>
            <span class="font-bold text-[15px] text-[#3D2B1F] tracking-tight">SpeakLoud</span>
        </a>

        <nav class="hidden md:flex items-center gap-1 overflow-x-auto">
            @foreach ($navLinks as [$route, $label, $icon])
                <a href="{{ route($route) }}"
                    @class([
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] transition-all whitespace-nowrap',
                        'text-[#FF8C42] bg-[#FF8C42]/10 font-medium' => request()->routeIs($route),
                        'text-[#3D2B1F]/60 hover:text-[#3D2B1F] hover:bg-black/[0.04]' => ! request()->routeIs($route),
                    ])>
                    <svg class="w-[15px] h-[15px] shrink-0" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">{!! $icon !!}</svg>
                    {{ $label }}
                </a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2 shrink-0">
            @auth
                <a href="{{ route('profile') }}"
                    @class([
                        'w-8 h-8 rounded-full flex items-center justify-center text-[13px] font-bold transition-all',
                        'bg-[#FF8C42] text-white ring-2 ring-[#FF8C42]/30' => request()->routeIs('profile*'),
                        'bg-[#FF8C42] text-white hover:ring-2 hover:ring-[#FF8C42]/20' => ! request()->routeIs('profile*'),
                    ])>
                    {{ strtoupper(substr(auth()->user()->email, 0, 1)) }}
                </a>
            @else
                <a href="{{ route('login') }}" class="text-[13px] text-[#3D2B1F]/60 hover:text-[#3D2B1F] px-2 transition-colors">Sign in</a>
                <a href="{{ route('register') }}" class="text-[13px] bg-[#FF8C42] text-white px-4 py-1.5 rounded-full font-semibold hover:bg-[#e67a35] transition-colors">
                    Start free
                </a>
            @endauth
        </div>
    </div>
</header>
