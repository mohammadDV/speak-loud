<?php

use App\Support\SpeechLocale;
use Tests\TestCase;

uses(TestCase::class);

test('speech locale maps known language codes to bcp 47 tags', function () {
    expect(SpeechLocale::for('en'))->toBe('en-US')
        ->and(SpeechLocale::for('de'))->toBe('de-DE')
        ->and(SpeechLocale::for('fa'))->toBe('fa-IR')
        ->and(SpeechLocale::german())->toBe('de-DE');
});

test('speech locale falls back safely for blank or unknown codes', function () {
    expect(SpeechLocale::for(null))->toBe('en-US')
        ->and(SpeechLocale::for(''))->toBe('en-US')
        ->and(SpeechLocale::for('zz'))->toBe('zz');
});
