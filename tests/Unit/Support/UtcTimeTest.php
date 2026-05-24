<?php

use App\Models\Schedule;
use App\Models\ScheduleOneTimeSlot;
use App\Models\ScheduleRecurringRule;
use App\Support\UtcTime;
use Carbon\Carbon;

test('scheduleWhen formats one-off datetimes in UTC', function () {
    $schedule = new Schedule(['type' => 'one_time']);
    $schedule->setRelation('oneTimeSlot', new ScheduleOneTimeSlot([
        'start_datetime' => Carbon::parse('2026-05-24 08:00:00', 'UTC'),
        'end_datetime'   => Carbon::parse('2026-05-24 09:00:00', 'UTC'),
    ]));

    expect(UtcTime::scheduleWhen($schedule))->toBe('Sun, May 24 · 08:00 – 09:00 UTC');
});

test('scheduleWhen formats recurring times with UTC label', function () {
    $schedule = new Schedule(['type' => 'recurring']);
    $schedule->setRelation('recurringRule', new ScheduleRecurringRule([
        'day_of_week' => 'Mon,Wed',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]));

    expect(UtcTime::scheduleWhen($schedule))->toBe('Mon, Wed · 18:00–19:00 UTC');
});
