<?php

use App\Support\ScheduleLimits;

test('schedule limits reads max slots from config', function () {
    config(['schedules.max_slots_per_user' => 5]);

    expect(ScheduleLimits::maxSlotsForUser())->toBe(5)
        ->and(ScheduleLimits::limitReachedMessage())->toBe('You can have up to 5 slots. Delete an existing slot to create a new one.');
});

test('schedule limits enforces a minimum of one slot', function () {
    config(['schedules.max_slots_per_user' => 0]);

    expect(ScheduleLimits::maxSlotsForUser())->toBe(1);
});
