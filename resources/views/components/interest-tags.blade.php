@props(['interests', 'limit' => null, 'size' => 'sm'])

@php
    $items = $limit ? $interests->take($limit) : $interests;
    $extra = $limit && $interests->count() > $limit ? $interests->count() - $limit : 0;
    $sizeClass = $size === 'xs'
        ? 'text-[10px] px-2 py-0.5'
        : 'text-xs px-3 py-1';
@endphp

@if ($items->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1.5']) }}>
        @foreach ($items as $interest)
            <span class="{{ $sizeClass }} bg-[#FF8C42]/15 text-[#FF8C42] rounded-full">{{ $interest->name_en }}</span>
        @endforeach
        @if ($extra > 0)
            <span class="{{ $sizeClass }} bg-[#3D2B1F]/5 text-[#3D2B1F]/50 rounded-full">+{{ $extra }}</span>
        @endif
    </div>
@endif
