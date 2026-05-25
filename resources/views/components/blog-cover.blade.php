@props([
    'post',
    'class' => '',
    'aspect' => 'aspect-[16/9]',
])

@php
    $alt = $post->coverAlt();
    $src = $post->coverUrl();
@endphp

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    loading="lazy"
    decoding="async"
    {{ $attributes->merge(['class' => trim("w-full object-cover {$aspect} {$class}")]) }}
>
