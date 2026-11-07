<?php

use App\Support\ScheduleCardGradients;

it('returns a stable gradient for a given id', function () {
    expect(ScheduleCardGradients::forId(1))
        ->toBe(ScheduleCardGradients::forId(1))
        ->and(ScheduleCardGradients::forId(1))
        ->toBe(['from' => '#9B59B6', 'to' => '#6C3483', 'body' => '#F3EAF8']);
});

it('wraps gradients by modulo of the palette size', function () {
    expect(ScheduleCardGradients::forId(0))
        ->toBe(ScheduleCardGradients::forId(4))
        ->and(ScheduleCardGradients::forId(5))
        ->toBe(ScheduleCardGradients::forId(1));
});

it('maps indexes the same way as ids', function () {
    expect(ScheduleCardGradients::forIndex(2))
        ->toBe(ScheduleCardGradients::forId(2))
        ->and(ScheduleCardGradients::forIndex(2))
        ->toBe(['from' => '#1ABC9C', 'to' => '#16A085', 'body' => '#E7F7F3']);
});

it('includes a soft body color for each palette entry', function () {
    expect(ScheduleCardGradients::forId(0)['body'])->toBe('#FFF0E0')
        ->and(ScheduleCardGradients::forId(3))->toBe(['from' => '#3498DB', 'to' => '#2980B9', 'body' => '#E8F4FC']);
});
