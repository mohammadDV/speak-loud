<?php

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
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
    $user = User::factory()->create([
        'email' => 'reset@example.com',
        'remember_token' => 'old-remember-token',
        'session_version' => 0,
    ]);

    DB::table('sessions')->insert([
        'id' => 'old-session-id',
        'user_id' => $user->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
        'payload' => 'payload',
        'last_activity' => time(),
    ]);

    $token = Password::createToken($user);

    Volt::test('auth.reset-password', ['token' => $token])
        ->set('email', 'reset@example.com')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('resetPassword')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    $user->refresh();

    expect(Hash::check('Newpassword1!', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe('old-remember-token')
        ->and($user->session_version)->toBe(1)
        ->and(DB::table('sessions')->where('id', 'old-session-id')->exists())->toBeFalse();
});
