@props(['schedule', 'long' => false])

@php
    use App\Support\ScheduleTime;

    $when = $long
        ? ScheduleTime::scheduleWhenLong($schedule)
        : ScheduleTime::scheduleWhen($schedule);
@endphp

@if ($when)
    <span {{ $attributes }}>{{ $when }}</span>
@endif
