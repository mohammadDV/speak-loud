<?php

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;

test('registration sends verification email and redirects to notice page', function () {
    Notification::fake();

    Volt::test('auth.register')
        ->set('username', 'verifyuser')
        ->set('email', 'verifyuser@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'verifyuser@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('unverified users cannot access verified-only routes', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('schedule'))
        ->assertRedirect(route('verification.notice'));
});

test('verified users can access schedule', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('schedule'))
        ->assertOk();
});

test('email can be verified via signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('profile.edit'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
