<?php

use App\Models\User;
use App\Models\UserProfile;
use App\Notifications\VerifyEmail;

test('verify email renders speakloud branded html', function () {
    $user = User::factory()->unverified()->create();

    UserProfile::create([
        'user_id' => $user->id,
        'username' => 'mailtheme',
        'profile_slug' => 'mailtheme',
        'display_name' => 'Mail Theme',
    ]);

    $html = (string) (new VerifyEmail)->toMail($user->fresh('profile'))->render();

    expect($html)
        ->toContain('#FF8C42')
        ->toContain('#FFF8F0')
        ->toContain('SpeakLoud')
        ->toContain('Instrument Sans')
        ->toContain('Hi Mail Theme,')
        ->toContain('The SpeakLoud team')
        ->toContain('brand-badge');
});
