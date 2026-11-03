<?php

use App\Support\ViewerTimezone;
use Tests\TestCase;

uses(TestCase::class);

test('viewer timezone falls back to utc for invalid values', function () {
    expect(ViewerTimezone::normalize(null))->toBe('UTC')
        ->and(ViewerTimezone::normalize(''))->toBe('UTC')
        ->and(ViewerTimezone::normalize('Not/AZone'))->toBe('UTC');
});

test('viewer timezone accepts valid iana identifiers', function () {
    expect(ViewerTimezone::normalize('Europe/Berlin'))->toBe('Europe/Berlin')
        ->and(ViewerTimezone::normalize('Asia/Tokyo'))->toBe('Asia/Tokyo');
});

test('viewer timezone label includes offset', function () {
    expect(ViewerTimezone::label('UTC'))->toContain('UTC')
        ->and(ViewerTimezone::label('Europe/Berlin'))->toContain('Europe/Berlin');
});
