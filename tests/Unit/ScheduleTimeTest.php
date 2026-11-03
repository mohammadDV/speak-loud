<?php

use App\Models\Schedule;
use App\Models\ScheduleOneTimeSlot;
use App\Models\ScheduleRecurringRule;
use App\Support\ScheduleTime;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

test('one-off schedule formats into the viewer timezone', function () {
    $schedule = new Schedule(['type' => 'one_time']);
    $schedule->setRelation('oneTimeSlot', new ScheduleOneTimeSlot([
        'start_datetime' => Carbon::parse('2026-05-24 08:00:00', 'UTC'),
        'end_datetime' => Carbon::parse('2026-05-24 09:00:00', 'UTC'),
    ]));

    $when = ScheduleTime::scheduleWhen($schedule, 'Asia/Tokyo');

    expect($when)->toContain('17:00')
        ->and($when)->toContain('18:00')
        ->and($when)->not->toContain(' UTC');
});

test('recurring wall clock converts weekdays across timezones', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'UTC'));

    $schedule = new Schedule(['type' => 'recurring']);
    $schedule->setRelation('recurringRule', new ScheduleRecurringRule([
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'timezone' => 'America/New_York',
    ]));

    $when = ScheduleTime::scheduleWhen($schedule, 'Asia/Tokyo');

    expect($when)->toMatch('/Tue/')
        ->and($when)->toContain('07:00')
        ->and($when)->toContain('08:00');

    Carbon::setTestNow();
});

test('recurring wall clock stays stable across dst for creator timezone', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-15 12:00:00', 'Europe/Berlin'));

    $winter = ScheduleTime::nextOccurrence('Mon', '18:00', 'Europe/Berlin');
    expect($winter->format('H:i'))->toBe('18:00');

    Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'Europe/Berlin'));

    $summer = ScheduleTime::nextOccurrence('Mon', '18:00', 'Europe/Berlin');
    expect($summer->format('H:i'))->toBe('18:00');

    Carbon::setTestNow();
});

test('convert recurring wall clock round trips same timezone', function () {
    $converted = ScheduleTime::convertRecurringWallClock(
        ['Mon', 'Wed'],
        '18:00',
        '19:00',
        'Europe/Berlin',
        'Europe/Berlin',
    );

    expect($converted['days'])->toBe(['Mon', 'Wed'])
        ->and($converted['start'])->toBe('18:00')
        ->and($converted['end'])->toBe('19:00');
});

test('legacy recurring rules backfilled as utc still render for viewers', function () {
    $schedule = new Schedule(['type' => 'recurring']);
    $schedule->setRelation('recurringRule', new ScheduleRecurringRule([
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
        'timezone' => 'UTC',
    ]));

    expect(ScheduleTime::scheduleWhen($schedule, 'UTC'))->toBe('Mon · 18:00–19:00 UTC');
});
