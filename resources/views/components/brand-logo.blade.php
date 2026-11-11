@props([
    'size' => 28,
    'showWordmark' => true,
    'wordmarkClass' => 'font-bold text-[15px] text-[#3D2B1F] tracking-tight',
    'alt' => 'SpeakLoudMate',
])

@php
    // The mark is wider than tall (~1.434 aspect), so size is its height and the width follows.
    $height = (int) $size;
    $width = (int) round($height * 367 / 256);
@endphp

<span {{ $attributes->class('inline-flex items-center gap-2 shrink-0') }}>
    <img
        src="{{ asset('images/logo-mark.webp') }}"
        width="{{ $width }}"
        height="{{ $height }}"
        alt="{{ $alt }}"
        class="object-contain shrink-0"
        decoding="async"
    >
    @if ($showWordmark)
        <span class="{{ $wordmarkClass }}">SpeakLoudMate</span>
    @endif
</span>
