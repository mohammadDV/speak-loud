@props(['schedule', 'long' => false])

@php
    use App\Support\UtcTime;

    $when = $long
        ? UtcTime::scheduleWhenLong($schedule)
        : UtcTime::scheduleWhen($schedule);
@endphp

@if ($when)
    <span {{ $attributes }}>{{ $when }}</span>
@endif
