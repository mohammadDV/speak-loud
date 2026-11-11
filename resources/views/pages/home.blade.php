<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" style="color-scheme: light only;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <x-google-tag-manager />
    <x-seo-head />
    <x-viewer-timezone />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-[#FFF8F0] text-[#3D2B1F] antialiased pb-20 md:pb-0">

<x-app-splash />
<x-app-request-loader />
<x-app-navbar />

{{-- ─── Hero ───────────────────────────────────────────────── --}}
<section class="max-w-6xl mx-auto px-5 pt-8 sm:pt-12 md:pt-16 pb-10 sm:pb-16 md:pb-20 grid md:grid-cols-[1fr_420px] gap-8 md:gap-10 items-start">

    <div class="pt-0 md:pt-2">
        <div class="inline-flex items-center gap-1.5 bg-[#FF8C42]/10 text-[#FF8C42] text-[10px] sm:text-[11px] font-semibold px-2.5 sm:px-3 py-1 rounded-full mb-4 sm:mb-6 tracking-wide uppercase">
            <span class="w-1.5 h-1.5 rounded-full bg-[#FF8C42]"></span>
            40+ languages
        </div>

        <h1 class="text-[2rem] sm:text-[2.75rem] md:text-[3.25rem] font-black text-[#3D2B1F] leading-[1.1] sm:leading-[1.05] tracking-tight mb-3 sm:mb-5">
            Speak any language with <span class="text-[#FF8C42]">real people.</span>
        </h1>

        <p class="text-[#3D2B1F]/55 text-[14px] sm:text-[15px] leading-relaxed max-w-sm mb-6 sm:mb-8">
            Drop your weekly times. Pick who joins. One-on-one conversations with patient partners.
        </p>

        <div class="flex flex-col sm:flex-row sm:flex-wrap items-stretch sm:items-center gap-2.5 sm:gap-3 mb-7 sm:mb-10">
            @auth
                <a href="{{ route('discover') }}"
                   class="inline-flex items-center justify-center bg-[#FF8C42] text-white text-[14px] font-semibold px-6 py-3 sm:py-2.5 rounded-full hover:bg-[#e67a35] transition-colors shadow-[0_2px_12px_rgba(255,140,66,.35)]">
                    Claim a slot
                </a>
                <a href="{{ route('schedule') }}"
                   class="inline-flex items-center justify-center text-[14px] font-semibold text-[#3D2B1F]/70 px-6 py-3 sm:py-2.5 rounded-full border border-[#3D2B1F]/15 hover:border-[#3D2B1F]/30 hover:text-[#3D2B1F] transition-all">
                    My schedule
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center bg-[#FF8C42] text-white text-[14px] font-semibold px-6 py-3 sm:py-2.5 rounded-full hover:bg-[#e67a35] transition-colors shadow-[0_2px_12px_rgba(255,140,66,.35)]">
                    Start free
                </a>
                <a href="{{ route('discover') }}"
                   class="inline-flex items-center justify-center text-[14px] font-semibold text-[#3D2B1F]/70 px-6 py-3 sm:py-2.5 rounded-full border border-[#3D2B1F]/15 hover:border-[#3D2B1F]/30 hover:text-[#3D2B1F] transition-all">
                    Browse partners
                </a>
            @endauth
            <a href="{{ route('donate') }}"
               class="md:hidden inline-flex items-center justify-center text-[14px] font-semibold text-[#FF8C42] px-6 py-3 rounded-full border border-[#FF8C42]/40 hover:border-[#FF8C42] hover:bg-[#FF8C42]/10 transition-all">
                Donate
            </a>
        </div>

        <div class="flex items-center gap-2.5">
            <div class="flex -space-x-2">
                @foreach([['#FF8C42','M'],['#9B59B6','K'],['#1ABC9C','A'],['#E74C3C','L']] as [$c,$l])
                    <div class="w-7 h-7 rounded-full border-2 border-[#FFF8F0] text-white text-[11px] font-bold flex items-center justify-center" style="background:{{ $c }}">{{ $l }}</div>
                @endforeach
            </div>
            <p class="text-[12px] sm:text-[13px] text-[#3D2B1F]/50">
                <span class="font-semibold text-[#3D2B1F]">12,400 speakers</span>
            </p>
        </div>
    </div>

    {{-- Open slots panel --}}
    <div class="bg-white rounded-2xl border border-black/[0.07] shadow-[0_4px_24px_rgba(0,0,0,.07)] overflow-hidden">
        <div class="px-4 sm:px-5 pt-4 sm:pt-5 pb-3 flex items-center justify-between gap-3">
            <span class="text-[11px] font-semibold text-[#3D2B1F]/40 uppercase tracking-widest">Open slots this week</span>
            <a href="{{ route('discover') }}" class="text-[11px] font-semibold text-[#FF8C42] hover:underline shrink-0">See all</a>
        </div>

        @php
            $demo = [
                ['name'=>'Maya',  'lang'=>'EN→FA', 'flag'=>'🇪🇸', 'time'=>'Sat 18:00', 'loc'=>'Spain',  'claims'=>3],
                ['name'=>'Kenji', 'lang'=>'EN→JA', 'flag'=>'🇯🇵', 'time'=>'Thu 19:00', 'loc'=>'Japan',  'claims'=>1],
                ['name'=>'Aisha', 'lang'=>'EN→AR', 'flag'=>'🇳🇬', 'time'=>'Mon 21:00', 'loc'=>'Egypt',  'claims'=>6],
                ['name'=>'Lucia', 'lang'=>'EN→IT', 'flag'=>'🇮🇹', 'time'=>'Wed 12:00', 'loc'=>'Italy',  'claims'=>2],
            ];
            $openSlots = \App\Support\LookupCache::homeOpenSlots(4);
        @endphp

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 px-3 pb-4">
            @if($openSlots->isNotEmpty())
                @foreach($openSlots as $i => $slot)
                    @php
                        $profile = $slot->user?->profile;
                        $name    = $profile?->display_name ?? 'User';
                        $lang    = strtoupper($slot->language?->code ?? '??');
                        $loc     = $profile?->country_code ?? '';
                        $cnt     = $slot->claims_count ?? 0;
                        $g       = \App\Support\ScheduleCardGradients::forIndex($i);
                        $time = \App\Support\ScheduleTime::scheduleWhen($slot) ?? '—';
                    @endphp
                    <div @class(['rounded-xl overflow-hidden border border-black/[0.06] flex sm:block', 'hidden sm:block' => $i >= 2])>
                        <div class="w-16 sm:w-auto sm:h-20 shrink-0 flex items-end px-2.5 sm:px-3 py-2.5 sm:pb-2.5 relative" style="background:linear-gradient(135deg,{{ $g['from'] }},{{ $g['to'] }})">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/90 flex items-center justify-center font-black text-sm" style="color:{{ $g['from'] }}">
                                {{ strtoupper(substr($name,0,1)) }}
                            </div>
                            <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 text-[10px] font-bold bg-black/20 text-white px-1.5 py-0.5 rounded-full">{{ $lang }}</span>
                        </div>
                        <div class="px-3 py-2.5 min-w-0 flex-1">
                            <div class="font-semibold text-[13px] text-[#3D2B1F] truncate">{{ $name }}</div>
                            <div class="text-[11px] text-[#3D2B1F]/50 mt-0.5 truncate">{{ $time }}{{ $loc ? ' · '.$loc : '' }}</div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[11px] text-[#3D2B1F]/35">{{ $cnt }} claims</span>
                                <a href="{{ route('login') }}" class="text-[11px] font-bold text-white px-2.5 py-1 rounded-full transition-colors hover:opacity-90" style="background-color: {{ $g['from'] }};">Claim</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                @foreach($demo as $i => $s)
                    @php $g = \App\Support\ScheduleCardGradients::forIndex($i); @endphp
                    <div @class(['rounded-xl overflow-hidden border border-black/[0.06] flex sm:block', 'hidden sm:block' => $i >= 2])>
                        <div class="w-16 sm:w-auto sm:h-20 shrink-0 flex items-end px-2.5 sm:px-3 py-2.5 sm:pb-2.5 relative" style="background:linear-gradient(135deg,{{ $g['from'] }},{{ $g['to'] }})">
                            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-white/90 flex items-center justify-center font-black text-sm" style="color:{{ $g['from'] }}">
                                {{ strtoupper(substr($s['name'],0,1)) }}
                            </div>
                            <span class="absolute top-1.5 right-1.5 sm:top-2 sm:right-2 text-[10px] font-bold bg-black/20 text-white px-1.5 py-0.5 rounded-full">{{ $s['lang'] }}</span>
                        </div>
                        <div class="px-3 py-2.5 min-w-0 flex-1">
                            <div class="font-semibold text-[13px] text-[#3D2B1F] truncate">{{ $s['name'] }}</div>
                            <div class="text-[11px] text-[#3D2B1F]/50 mt-0.5 truncate">{{ $s['time'] }} · {{ $s['loc'] }}</div>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-[11px] text-[#3D2B1F]/35">{{ $s['claims'] }} claims</span>
                                <a href="{{ route('register') }}" class="text-[11px] font-bold text-white px-2.5 py-1 rounded-full transition-colors hover:opacity-90" style="background-color: {{ $g['from'] }};">Claim</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</section>

{{-- ─── How to use ─────────────────────────────────────────── --}}
<section class="border-t border-black/[0.06] py-14 sm:py-20">
    <div class="max-w-6xl mx-auto px-5">
        <div class="max-w-2xl mb-10 sm:mb-14">
            <span class="text-[10px] sm:text-[11px] font-semibold text-[#FF8C42] uppercase tracking-widest">How to use</span>
            <h2 class="text-[1.75rem] sm:text-[2rem] font-black text-[#3D2B1F] tracking-tight mt-2">
                Get from signup to your first practice session.
            </h2>
            <p class="text-sm sm:text-[15px] text-[#3D2B1F]/55 mt-3 leading-relaxed">
                Four steps. Publish when you are free, match with a partner, and start talking.
            </p>
        </div>

        <div class="space-y-12 sm:space-y-16">
            @foreach ($howToSteps as $i => $step)
                <div @class([
                    'grid md:grid-cols-2 gap-6 md:gap-10 items-center',
                ])>
                    <div @class(['md:order-2' => $i % 2 === 1])>
                        <div class="flex items-center gap-3">
                            <div class="inline-flex shrink-0 items-center justify-center w-9 h-9 rounded-full border border-[#3D2B1F]/15 text-[13px] font-bold text-[#3D2B1F]/45">
                                {{ $step['n'] }}
                            </div>
                            <h3 class="text-xl sm:text-[1.35rem] font-bold text-[#3D2B1F] tracking-tight">
                                {{ $step['title'] }}
                            </h3>
                        </div>
                        <p class="text-sm sm:text-[15px] text-[#3D2B1F]/55 mt-2.5 leading-relaxed max-w-md">
                            {{ $step['body'] }}
                        </p>
                    </div>

                    <div @class(['md:order-1' => $i % 2 === 1])>
                        <x-home-screenshot-placeholder
                            :label="$step['label']"
                            :accent="$step['accent']"
                            :src="$step['src']"
                            :alt="$step['title']"
                        />
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ─── Video section ──────────────────────────────────────── --}}
@php
    $demoVideo = \App\Support\HomeDemoVideo::embed();
@endphp

<section class="border-t border-black/[0.06] py-14 sm:py-20">
    <div class="max-w-2xl mx-auto px-5 text-center">
        <div class="inline-flex items-center gap-1.5 bg-[#FF8C42]/10 text-[#FF8C42] text-[11px] font-semibold px-3 py-1 rounded-full mb-5 uppercase tracking-wide">
            Watch · 60s
        </div>
        <h2 class="text-[1.75rem] sm:text-[2rem] font-black text-[#3D2B1F] tracking-tight mb-3">
            See it in 60 seconds.
        </h2>
        <p class="text-sm text-[#3D2B1F]/50 mb-8 max-w-md mx-auto">
            A quick walkthrough of publishing a slot, claiming a partner, and starting the conversation.
        </p>

        <div class="rounded-2xl overflow-hidden bg-[#1C1C1E] aspect-video relative shadow-[0_8px_40px_rgba(0,0,0,.18)]">
            @if ($demoVideo !== null && $demoVideo['type'] === 'file')
                <video
                    class="absolute inset-0 h-full w-full object-cover"
                    controls
                    playsinline
                    preload="metadata"
                    src="{{ $demoVideo['src'] }}"
                ></video>
            @elseif ($demoVideo !== null)
                <iframe
                    class="absolute inset-0 h-full w-full"
                    src="{{ $demoVideo['src'] }}"
                    title="SpeakLoudMate demo video"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            @else
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-16 h-16 rounded-full bg-white/15 flex items-center justify-center border border-white/10" aria-hidden="true">
                        <svg class="w-6 h-6 text-white ml-1" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="absolute bottom-0 left-0 right-0 px-4 py-3">
                    <div class="h-1 bg-white/10 rounded-full">
                        <div class="h-1 bg-[#FF8C42] rounded-full w-1/5"></div>
                    </div>
                    <div class="flex justify-between text-[10px] text-white/40 mt-1.5">
                        <span>Demo coming soon</span>
                        <span>1:00</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ─── Blog ───────────────────────────────────────────────── --}}
@php
    $recentPosts = app(\App\Repositories\Contracts\IBlogPostRepository::class)->recentPublished(3);
@endphp

@if ($recentPosts->isNotEmpty())
<section class="border-t border-black/[0.06] py-10 sm:py-16">
    <div class="max-w-6xl mx-auto px-5">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
            <div>
                <span class="text-[10px] sm:text-[11px] font-semibold text-[#FF8C42] uppercase tracking-widest">From the blog</span>
                <h2 class="text-xl sm:text-[1.75rem] font-black text-[#3D2B1F] tracking-tight mt-1.5 sm:mt-2">Tips for better practice</h2>
                <p class="text-xs sm:text-sm text-[#3D2B1F]/50 mt-1 max-w-md">Guides on finding partners, running sessions, and getting the most from SpeakLoudMate.</p>
            </div>
            <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-[#FF8C42] hover:underline shrink-0">
                View all posts →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
            @foreach ($recentPosts as $post)
                <a href="{{ route('blog.show', $post->slug) }}" class="group block rounded-xl border border-black/[0.06] bg-white overflow-hidden hover:shadow-[0_4px_20px_rgba(0,0,0,.08)] transition-shadow">
                    <x-blog-cover
                        :post="$post"
                        aspect="aspect-[2/1] md:aspect-[16/9]"
                        class="rounded-none"
                    />
                    <div class="p-4 md:p-5">
                        @if ($post->category)
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-[#FF8C42]">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="text-[15px] md:text-base font-semibold text-[#3D2B1F] mt-1.5 group-hover:text-[#FF8C42] transition-colors leading-snug line-clamp-2">{{ $post->title }}</h3>
                        @if ($post->excerpt)
                            <p class="text-sm text-[#3D2B1F]/55 mt-1.5 line-clamp-2">{{ strip_tags($post->excerpt) }}</p>
                        @endif
                        <p class="text-xs text-[#3D2B1F]/40 mt-3">
                            {{ $post->author?->profile?->display_name ?? 'SpeakLoudMate' }}
                            · {{ $post->published_at?->format('M j, Y') }}
                        </p>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<x-site-footer />

@livewireScripts
</body>
</html>
