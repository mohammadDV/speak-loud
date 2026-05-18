<?php

use App\Support\ScheduleDayOfWeek;

it('normalizes short day codes in weekday order', function () {
    expect(ScheduleDayOfWeek::normalize('Sun,Sat,Mon'))->toBe('Mon,Sat,Sun');
});

it('normalizes full day names', function () {
    expect(ScheduleDayOfWeek::normalize('Monday'))->toBe('Mon');
    expect(ScheduleDayOfWeek::normalize('Saturday, Sunday'))->toBe('Sat,Sun');
});

it('normalizes arrays of codes', function () {
    expect(ScheduleDayOfWeek::normalize(['Wed', 'Mon']))->toBe('Mon,Wed');
});

it('rejects unknown day values', function () {
    ScheduleDayOfWeek::normalize('Funday');
})->throws(InvalidArgumentException::class);
