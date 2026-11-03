@props([
    'label',
    'hourModel',
    'minuteModel',
    'description' => null,
])

@php
    use App\Support\UtcTime;
@endphp

<div {{ $attributes->class('space-y-1') }}>
    <flux:label>{{ $label }}</flux:label>
    <div class="flex gap-2">
        <flux:select wire:model.live="{{ $hourModel }}" class="flex-1" aria-label="{{ $label }} hour">
            @foreach (UtcTime::hours() as $value => $text)
                <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="{{ $minuteModel }}" class="flex-1" aria-label="{{ $label }} minute">
            @foreach (UtcTime::minutes() as $value => $text)
                <flux:select.option value="{{ $value }}">{{ $text }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>
    @if ($description)
        <flux:description>{{ $description }}</flux:description>
    @endif
</div>
