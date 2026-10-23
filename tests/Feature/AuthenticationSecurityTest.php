<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Volt\Volt;

test('banned user cannot log in', function () {
    $user = User::factory()->banned()->create([
        'email' => 'banned@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    Volt::test('auth.login')
        ->set('email', 'banned@example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
    expect($user->fresh()->status)->toBe('banned');
});

test('inactive user cannot log in', function () {
    User::factory()->inactive()->create([
        'email' => 'inactive@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    Volt::test('auth.login')
        ->set('email', 'inactive@example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('banned session is logged out when visiting authenticated routes', function () {
    $user = User::factory()->banned()->create();

    $this->actingAs($user)
        ->get(route('schedule'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('active user can log in', function () {
    User::factory()->create([
        'email' => 'active@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    Volt::test('auth.login')
        ->set('email', 'active@example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('discover'));

    $this->assertAuthenticated();
});

test('login ignores external pending claim return urls', function () {
    User::factory()->create([
        'email' => 'redirect@example.com',
        'password' => Hash::make('Password1!'),
    ]);

    session(['pending_claim_return' => 'https://evil.example/phish']);

    Volt::test('auth.login')
        ->set('email', 'redirect@example.com')
        ->set('password', 'Password1!')
        ->call('login')
        ->assertRedirect(route('discover'));
});

test('unverified users cannot access profile edit', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('verification.notice'));
});

test('registration is rate limited by ip', function () {
    RateLimiter::clear('register|'.request()->ip());

    for ($i = 0; $i < 5; $i++) {
        RateLimiter::hit('register|'.request()->ip(), 60);
    }

    Volt::test('auth.register')
        ->set('username', 'ratelimiteduser')
        ->set('email', 'ratelimited@example.com')
        ->set('password', 'Password1!')
        ->set('password_confirmation', 'Password1!')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasErrors('email');

    expect(User::where('email', 'ratelimited@example.com')->exists())->toBeFalse();
});
