<?php

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

uses(TestCase::class);

test('mail markdown theme is speakloudmate and theme stylesheet exists', function () {
    expect(config('mail.markdown.theme'))->toBe('speakloudmate')
        ->and(config('mail.markdown.paths'))->toContain(resource_path('views/vendor/mail'))
        ->and(resource_path('views/vendor/mail/html/themes/speakloudmate.css'))->toBeFile()
        ->and(file_get_contents(resource_path('views/vendor/mail/html/themes/speakloudmate.css')))
        ->toContain('#FF8C42')
        ->toContain('#FFF8F0')
        ->toContain('Instrument Sans');
});

test('markdown mail messages render with speakloudmate brand styles', function () {
    $html = (string) (new MailMessage)
        ->greeting('Hi Ada,')
        ->line('Thanks for signing up!')
        ->action('Verify email address', 'https://example.com/verify')
        ->salutation("Talk soon,\nThe SpeakLoudMate team")
        ->render();

    expect($html)
        ->toContain('#FF8C42')
        ->toContain('#FFF8F0')
        ->toContain('SpeakLoudMate')
        ->toContain('Instrument Sans')
        ->toContain('Hi Ada,')
        ->toContain('The SpeakLoudMate team')
        ->toContain('brand-badge')
        ->toContain('button-primary');
});
