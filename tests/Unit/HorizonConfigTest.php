<?php

use Tests\TestCase;

uses(TestCase::class);

test('horizon supervisors process mail before default on redis', function () {
    expect(config('horizon.defaults.supervisor-1.queue'))->toBe(['mail', 'default'])
        ->and(config('horizon.defaults.supervisor-1.connection'))->toBe('redis')
        ->and(config('horizon.defaults.supervisor-1.tries'))->toBe(3)
        ->and(config('horizon.defaults.supervisor-1.timeout'))->toBe(60)
        ->and(config('horizon.waits'))->toHaveKeys(['redis:mail', 'redis:default']);
});
