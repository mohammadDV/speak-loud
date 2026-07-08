<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;

test('forgot password page sends reset link notification', function () {
    Notification::fake();

    $user = User::factory()->create(['email' => 'reset@example.com']);

    Volt::test('auth.forgot-password')
        ->set('email', 'reset@example.com')
        ->call('sendResetLink')
        ->assertHasNoErrors()
        ->assertSet('status', 'reset-link-sent');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('password can be reset with valid token', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    Volt::test('auth.reset-password', ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('Newpassword1!', $user->fresh()->password))->toBeTrue();
});
