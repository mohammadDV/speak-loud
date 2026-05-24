<?php

use App\Models\Schedule;
use function Livewire\Volt\{mount, state};

state(['openSlots' => []]);

mount(function () {
    $this->openSlots = Schedule::with(['user.profile', 'language', 'oneTimeSlot', 'recurringRule', 'claims'])
        ->where('status', 'active')
        ->whereHas('user', fn($q) => $q->where('status', 'active'))
        ->latest()
        ->take(4)
        ->get();
});

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SpeakLoud — Practice languages with real people</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased">

{{-- ─── Navbar ─────────────────────────────────────────────── --}}
<header class="sticky top-0 z-50 bg-[#FFF8F0]/95 backdrop-blur border-b border-black/[0.06]">
    <div class="max-w-6xl mx-auto px-5 h-[52px] flex items-center justify-between gap-6">

        {{-- Logo --}}
        <a href="/" class="flex items-center gap-2 shrink-0">
            <span class="w-7 h-7 rounded-lg bg-[#FF8C42] text-white font-black text-sm flex items-center justify-center leading-none select-none">S</span>
            <span class="font-bold text-[15px] text-[#3D2B1F] tracking-tight">SpeakLoud</span>
        </a>

        {{-- Nav links --}}
        <nav class="hidden md:flex items-center gap-1">
            @foreach([
                ['discover', 'Discover',  '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z"/>'],
                ['schedule', 'Schedule',  '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
                ['messages', 'Messages',  '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9 4.5 2.25-4.5c.84-1.68 2.55-2.75 4.38-2.75h6.75a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3H9.75L3 18.75Z"/>'],
                ['blog.index','Blog',     '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>'],
            ] as [$route, $label, $icon])
            <a href="{{ route($route) }}" class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] text-[#3D2B1F]/60 hover:text-[#3D2B1F] hover:bg-black/[0.04] transition-all">
                <svg class="w-[15px] h-[15px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">{!! $icon !!}</svg>
                {{ $label }}
            </a>
            @endforeach
        </nav>

        {{-- Right --}}
        <div class="flex items-center gap-2 shrink-0">
            <button class="w-8 h-8 flex items-center justify-center text-[#3D2B1F]/40 hover:text-[#3D2B1F] rounded-lg hover:bg-black/[0.04] transition-all">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                </svg>
            </button>
            @auth
                <a href="{{ route('profile') }}" class="w-8 h-8 rounded-full bg-[#FF8C42] text-white text-[13px] font-bold flex items-center justify-center">
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

{{-- ─── Hero ───────────────────────────────────────────────── --}}
<section class="max-w-6xl mx-auto px-5 pt-16 pb-20 grid md:grid-cols-[1fr_420px] gap-10 items-start">

    {{-- Left --}}
    <div class="pt-2">
        <div class="inline-flex items-center gap-1.5 bg-[#FF8C42]/10 text-[#FF8C42] text-[11px] font-semibold px-3 py-1 rounded-full mb-6 tracking-wide uppercase">
            <span class="w-1.5 h-1.5 rounded-full bg-[#FF8C42]"></span>
            40+ languages
        </div>

        <h1 class="text-[3.25rem] font-black text-[#3D2B1F] leading-[1.05] tracking-tight mb-5">
            Speak any language<br>with <span class="text-[#FF8C42]">real people.</span>
        </h1>

        <p class="text-[#3D2B1F]/55 text-[15px] leading-relaxed max-w-sm mb-8">
            Drop your weekly times. Pick who joins. One-on-one conversations with patient partners — every Saturday at six, or one-off next Tuesday.
        </p>

        <div class="flex flex-wrap items-center gap-3 mb-10">
            <a href="{{ route('register') }}"
               class="bg-[#FF8C42] text-white text-[14px] font-semibold px-6 py-2.5 rounded-full hover:bg-[#e67a35] transition-colors shadow-[0_2px_12px_rgba(255,140,66,.35)]">
                Start free
            </a>
            <a href="{{ route('discover') }}"
               class="text-[14px] font-semibold text-[#3D2B1F]/70 px-6 py-2.5 rounded-full border border-[#3D2B1F]/15 hover:border-[#3D2B1F]/30 hover:text-[#3D2B1F] transition-all">
                Browse partners
            </a>
        </div>

        {{-- Social proof --}}
        <div class="flex items-center gap-2.5">
            <div class="flex -space-x-2">
                @foreach([['#FF8C42','M'],['#9B59B6','K'],['#1ABC9C','A'],['#E74C3C','L']] as [$c,$l])
                    <div class="w-7 h-7 rounded-full border-2 border-[#FFF8F0] text-white text-[11px] font-bold flex items-center justify-center" style="background:{{ $c }}">{{ $l }}</div>
                @endforeach
            </div>
            <p class="text-[13px] text-[#3D2B1F]/50">
                <span class="font-semibold text-[#3D2B1F]">12,400 speakers</span> · joined this week
            </p>
        </div>
    </div>

    {{-- Right: open slots panel --}}
    <div class="bg-white rounded-2xl border border-black/[0.07] shadow-[0_4px_24px_rgba(0,0,0,.07)] overflow-hidden">
        <div class="px-5 pt-5 pb-3 flex items-center justify-between">
            <span class="text-[11px] font-semibold text-[#3D2B1F]/40 uppercase tracking-widest">Open slots this week</span>
            <span class="text-[10px] font-semibold bg-[#FF8C42]/10 text-[#FF8C42] px-2 py-0.5 rounded-full">#Filter / search?</span>
        </div>

        @php
            $gradients = [
                ['from' => '#FF8C42', 'to' => '#FFD166'],
                ['from' => '#9B59B6', 'to' => '#6C3483'],
                ['from' => '#1ABC9C', 'to' => '#16A085'],
                ['from' => '#E67E22', 'to' => '#D35400'],
            ];
            $demo = [
                ['name'=>'Maya',  'lang'=>'EN', 'flag'=>'🇪🇸', 'time'=>'Sat 18:00', 'loc'=>'Spain', 'claims'=>3],
                ['name'=>'Kenji', 'lang'=>'EN', 'flag'=>'🇯🇵', 'time'=>'Thu 19:00', 'loc'=>'Japan', 'claims'=>1],
                ['name'=>'Aisha', 'lang'=>'EN', 'flag'=>'🇳🇬', 'time'=>'Mon 21:00', 'loc'=>'Egypt', 'claims'=>6],
                ['name'=>'Lucia', 'lang'=>'IT', 'flag'=>'🇮🇹', 'time'=>'Wed 12:00', 'loc'=>'Italy', 'claims'=>2],
            ];
            $items = $openSlots->isNotEmpty() ? null : $demo;
        @endphp

        <div class="grid grid-cols-2 gap-3 px-3 pb-4">
            @if($openSlots->isNotEmpty())
                @foreach($openSlots as $i => $slot)
                    @php
                        $profile = $slot->user?->profile;
                        $name    = $profile?->display_name ?? 'User';
                        $lang    = strtoupper($slot->language?->code ?? '??');
                        $loc     = $profile?->country_code ?? '';
                        $cnt     = $slot->claims->count();
                        $g       = $gradients[$i % count($gradients)];
                        if ($slot->oneTimeSlot) {
                            $time = \App\Support\UtcTime::format($slot->oneTimeSlot->start_datetime, 'D H:i').' UTC';
                        } elseif ($slot->recurringRule) {
                            $day  = Str::title(strtok($slot->recurringRule->day_of_week ?? 'Mon', ','));
                            $time = substr($day,0,3).' '.substr($slot->recurringRule->start_time??'',0,5).' UTC';
                        } else { $time = '—'; }
                    @endphp
                    <div class="rounded-xl overflow-hidden border border-black/[0.06]">
                        <div class="h-20 flex items-end px-3 pb-2.5 relative" style="background: linear-gradient(135deg, {{ $g['from'] }}, {{ $g['to'] }})">
                            <div class="w-9 h-9 rounded-full bg-white/90 flex items-center justify-center font-black text-sm" style="color:{{ $g['from'] }}">
                                {{ strtoupper(substr($name,0,1)) }}
                            </div>
                            <span class="absolute top-2.5 right-2.5 text-[10px] font-bold bg-white/20 text-white px-1.5 py-0.5 rounded-full backdrop-blur-sm">{{ $lang }}</span>
                        </div>
                        <div class="px-3 py-2.5">
                            <div class="font-semibold text-[13px] text-[#3D2B1F]">{{ $name }}</div>
                            <div class="text-[11px] text-[#3D2B1F]/50 mt-0.5">{{ $time }}{{ $loc ? ' · '.$loc : '' }}</div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[11px] text-[#3D2B1F]/40">{{ $cnt }} claims</span>
                                <a href="{{ route('login') }}" class="text-[11px] font-bold bg-[#FF8C42] text-white px-2.5 py-1 rounded-full hover:bg-[#e67a35] transition-colors">Claim</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach($demo as $i => $s)
                    @php $g = $gradients[$i % count($gradients)]; @endphp
                    <div class="rounded-xl overflow-hidden border border-black/[0.06]">
                        <div class="h-20 flex items-end px-3 pb-2.5 relative" style="background: linear-gradient(135deg, {{ $g['from'] }}, {{ $g['to'] }})">
                            <div class="w-9 h-9 rounded-full bg-white/90 flex items-center justify-center font-black text-sm" style="color:{{ $g['from'] }}">
                                {{ strtoupper(substr($s['name'],0,1)) }}
                            </div>
                            <span class="absolute top-2.5 right-2.5 text-[10px] font-bold bg-white/20 text-white px-1.5 py-0.5 rounded-full">{{ $s['lang'] }}</span>
                        </div>
                        <div class="px-3 py-2.5">
                            <div class="font-semibold text-[13px] text-[#3D2B1F]">{{ $s['name'] }} {{ $s['flag'] }}</div>
                            <div class="text-[11px] text-[#3D2B1F]/50 mt-0.5">{{ $s['time'] }} · {{ $s['loc'] }}</div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[11px] text-[#3D2B1F]/40">{{ $s['claims'] }} claims</span>
                                <a href="{{ route('register') }}" class="text-[11px] font-bold bg-[#FF8C42] text-white px-2.5 py-1 rounded-full hover:bg-[#e67a35] transition-colors">Claim</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ─── Video section ──────────────────────────────────────── --}}
<section class="border-t border-black/[0.06] py-20 bg-[#FFF8F0]">
    <div class="max-w-2xl mx-auto px-5 text-center">
        <div class="inline-flex items-center gap-1.5 bg-[#FF8C42]/10 text-[#FF8C42] text-[11px] font-semibold px-3 py-1 rounded-full mb-5 uppercase tracking-wide">
            How it works · 60s
        </div>
        <h2 class="text-[2rem] font-black text-[#3D2B1F] tracking-tight mb-8">Watch how a session goes.</h2>

        {{-- Video player placeholder --}}
        <div class="rounded-2xl overflow-hidden bg-[#1C1C1E] aspect-video flex items-center justify-center relative shadow-[0_8px_40px_rgba(0,0,0,.18)]">
            <div class="absolute inset-0 flex items-center justify-center">
                <button class="w-16 h-16 rounded-full bg-white/15 hover:bg-white/25 transition-all flex items-center justify-center backdrop-blur-sm border border-white/10">
                    <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                </button>
            </div>
            {{-- Progress bar --}}
            <div class="absolute bottom-0 left-0 right-0 px-4 py-3">
                <div class="h-1 bg-white/10 rounded-full">
                    <div class="h-1 bg-[#FF8C42] rounded-full" style="width:20%"></div>
                </div>
                <div class="flex justify-between text-[10px] text-white/40 mt-1">
                    <span>0:12</span>
                    <span>1:00</span>
                </div>
            </div>
        </div>

        {{-- Steps mini nav --}}
        <div class="flex items-center justify-center gap-6 mt-8 text-[13px]">
            <span class="font-semibold text-[#FF8C42]">1 · Set hours</span>
            <span class="text-[#3D2B1F]/40">2 · Get claims</span>
            <span class="text-[#3D2B1F]/40">3 · Pick partners</span>
            <span class="text-[#3D2B1F]/40">4 · Talk</span>
        </div>
    </div>
</section>

{{-- ─── How it works ───────────────────────────────────────── --}}
<section class="border-t border-black/[0.06] py-16">
    <div class="max-w-6xl mx-auto px-5 grid grid-cols-2 md:grid-cols-4 gap-8">
        @foreach([
            ['1','Set your hours',   'Mark when you are free, weekly or one-off.'],
            ['2','Get claims',       'Other learners apply to your slot with a note.'],
            ['3','Pick partners',    'Accept one or many. Decline the rest.'],
            ['4','Just talk',        'Chat opens. Hop on a call. Repeat next week.'],
        ] as [$n, $title, $body])
        <div>
            <div class="w-8 h-8 rounded-full border border-[#3D2B1F]/15 text-[#3D2B1F]/35 text-sm font-bold flex items-center justify-center mb-3">{{ $n }}</div>
            <h3 class="font-semibold text-[#3D2B1F] text-[13px] mb-1.5">{{ $title }}</h3>
            <p class="text-[12px] text-[#3D2B1F]/45 leading-relaxed">{{ $body }}</p>
        </div>
        @endforeach
    </div>
</section>

{{-- ─── Footer ─────────────────────────────────────────────── --}}
<footer class="border-t border-black/[0.06] py-6">
    <div class="max-w-6xl mx-auto px-5 flex items-center justify-between text-[12px] text-[#3D2B1F]/30">
        <span>© {{ date('Y') }} SpeakLoud</span>
        <div class="flex gap-5">
            <a href="{{ route('about') }}" class="hover:text-[#3D2B1F] transition-colors">About</a>
            <a href="{{ route('contact') }}" class="hover:text-[#3D2B1F] transition-colors">Contact</a>
            <a href="{{ route('support') }}" class="hover:text-[#3D2B1F] transition-colors">Support</a>
        </div>
    </div>
</footer>

</body>
</html>
