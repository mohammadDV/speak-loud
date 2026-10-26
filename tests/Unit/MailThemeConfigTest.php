<?php

use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

uses(TestCase::class);

test('mail markdown theme is speakloud and theme stylesheet exists', function () {
    expect(config('mail.markdown.theme'))->toBe('speakloud')
        ->and(config('mail.markdown.paths'))->toContain(resource_path('views/vendor/mail'))
        ->and(resource_path('views/vendor/mail/html/themes/speakloud.css'))->toBeFile()
        ->and(file_get_contents(resource_path('views/vendor/mail/html/themes/speakloud.css')))
        ->toContain('#FF8C42')
        ->toContain('#FFF8F0')
        ->toContain('Instrument Sans');
});

test('markdown mail messages render with speakloud brand styles', function () {
    $html = (string) (new MailMessage)
        ->greeting('Hi Ada,')
        ->line('Thanks for signing up!')
        ->action('Verify email address', 'https://example.com/verify')
        ->salutation("Talk soon,\nThe SpeakLoud team")
        ->render();

    expect($html)
        ->toContain('#FF8C42')
        ->toContain('#FFF8F0')
        ->toContain('SpeakLoud')
        ->toContain('Instrument Sans')
        ->toContain('Hi Ada,')
        ->toContain('The SpeakLoud team')
        ->toContain('brand-badge')
        ->toContain('button-primary');
});
