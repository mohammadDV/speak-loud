<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;

test('forgot password shows same success for unknown email', function () {
    Notification::fake();

    Volt::test('auth.forgot-password')
        ->set('email', 'unknown@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('status', 'reset-link-sent');

    Notification::assertNothingSent();
});

test('forgot password shows same success for known email', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'known@example.com']);

    Volt::test('auth.forgot-password')
        ->set('email', 'known@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('status', 'reset-link-sent');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password is rate limited by ip', function () {
    RateLimiter::clear('password-reset|'.request()->ip());

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('password-reset|'.request()->ip(), 3600);
    }

    Volt::test('auth.forgot-password')
        ->set('email', 'anyone@example.com')
        ->call('sendResetLink')
        ->assertHasErrors('email');
});

test('password reset submit is rate limited by ip', function () {
    RateLimiter::clear('password-reset-submit|'.request()->ip());

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('password-reset-submit|'.request()->ip(), 60);
    }

    Volt::test('auth.reset-password', ['token' => 'test-token'])
        ->set('email', 'anyone@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->call('resetPassword')
        ->assertHasErrors('email');
});
