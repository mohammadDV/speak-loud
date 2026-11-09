@props([
    'label' => 'Screenshot',
    'accent' => '#FF8C42',
    'src' => null,
    'alt' => null,
    'width' => 1200,
    'height' => 645,
])

@php
    $imageAlt = $alt ?? $label;
@endphp

<figure {{ $attributes->class(['w-full']) }}>
    <div class="overflow-hidden rounded-2xl border border-[#3D2B1F]/10 bg-white shadow-[0_16px_40px_rgba(61,43,31,0.10)]">
        {{-- Compact macOS title bar (no URL bar — screenshots already show app chrome) --}}
        <div class="flex h-9 items-center gap-1.5 border-b border-[#3D2B1F]/08 bg-[#F3EDE6] px-3.5" aria-hidden="true">
            <span class="block h-2.5 w-2.5 rounded-full bg-[#FF5F57]"></span>
            <span class="block h-2.5 w-2.5 rounded-full bg-[#FEBC2E]"></span>
            <span class="block h-2.5 w-2.5 rounded-full bg-[#28C840]"></span>
        </div>

        <div class="relative overflow-hidden bg-[#FFF8F0]">
            @if (filled($src))
                <div class="aspect-[16/10]">
                    <img
                        src="{{ asset($src) }}"
                        alt="{{ $imageAlt }}"
                        width="{{ $width }}"
                        height="{{ $height }}"
                        class="h-full w-full object-cover object-top"
                        loading="lazy"
                        decoding="async"
                    />
                </div>
            @else
                <div class="relative aspect-[16/10] bg-[linear-gradient(160deg,#FFF8F0_0%,#FFE8D4_55%,#FFF0E0_100%)]">
                    <div class="absolute inset-4 flex flex-col gap-2.5 rounded-xl border border-[#3D2B1F]/08 bg-white/70 p-3 sm:inset-5 sm:p-4">
                        <div class="flex items-center justify-between gap-3">
                            <div class="h-2.5 w-24 rounded-full" style="background:{{ $accent }}33"></div>
                            <div class="h-6 w-16 rounded-full" style="background:{{ $accent }}"></div>
                        </div>
                        <div class="h-2 w-[60%] rounded-full bg-[#3D2B1F]/08"></div>
                        <div class="mt-1 grid flex-1 grid-cols-2 gap-2">
                            <div class="rounded-lg border border-[#3D2B1F]/06 bg-black/[0.04]"></div>
                            <div class="rounded-lg border border-[#3D2B1F]/06 bg-black/[0.04]"></div>
                            <div class="col-span-2 rounded-lg border border-[#3D2B1F]/06 bg-black/[0.04]"></div>
                        </div>
                    </div>
                    <div class="absolute bottom-3 left-3 right-3 flex items-center justify-between">
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-[#3D2B1F]/40 sm:text-[11px]">
                            Screenshot placeholder
                        </span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold text-white" style="background:{{ $accent }}">
                            UI
                        </span>
                    </div>
                </div>
            @endif
        </div>
    </div>
    <figcaption class="sr-only">{{ $imageAlt }}</figcaption>
</figure>
