<?php

use App\Models\Schedule;
use App\Models\ScheduleOneTimeSlot;
use App\Models\ScheduleRecurringRule;
use App\Support\UtcTime;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('scheduleWhen formats one-off datetimes for the viewer timezone', function () {
    $schedule = new Schedule(['type' => 'one_time']);
    $schedule->setRelation('oneTimeSlot', new ScheduleOneTimeSlot([
        'start_datetime' => Carbon::parse('2026-05-24 08:00:00', 'UTC'),
        'end_datetime' => Carbon::parse('2026-05-24 09:00:00', 'UTC'),
    ]));

    expect(UtcTime::scheduleWhen($schedule))->toBe('Sun, May 24 · 08:00 – 09:00 UTC');
});

test('scheduleWhen formats recurring times with local label', function () {
    $schedule = new Schedule(['type' => 'recurring']);
    $schedule->setRelation('recurringRule', new ScheduleRecurringRule([
        'day_of_week' => 'Mon,Wed',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'timezone' => 'UTC',
    ]));

    expect(UtcTime::scheduleWhen($schedule))->toBe('Mon, Wed · 18:00–19:00 UTC');
});

test('hours lists twenty four hour values from 00 to 23', function () {
    $hours = UtcTime::hours();

    expect($hours)->toHaveCount(24)
        ->and($hours['00'])->toBe('00')
        ->and($hours['13'])->toBe('13')
        ->and($hours['23'])->toBe('23')
        ->and($hours)->not->toHaveKey('24');
});

test('minutes lists values from 00 to 59', function () {
    $minutes = UtcTime::minutes();

    expect($minutes)->toHaveCount(60)
        ->and($minutes['00'])->toBe('00')
        ->and($minutes['59'])->toBe('59');
});

test('split and join normalize hour and minute pairs', function () {
    expect(UtcTime::split('9:05'))->toBe(['09', '05'])
        ->and(UtcTime::join('9', '5'))->toBe('09:05')
        ->and(UtcTime::join('23', '59'))->toBe('23:59');
});

test('datetime local helpers round trip date and time', function () {
    expect(UtcTime::dateFromDatetimeLocal('2026-05-24T18:30'))->toBe('2026-05-24')
        ->and(UtcTime::timeFromDatetimeLocal('2026-05-24T18:30'))->toBe('18:30')
        ->and(UtcTime::composeDatetimeLocal('2026-05-24', '18:30'))->toBe('2026-05-24T18:30');
});
