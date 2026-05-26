<?php

use App\Models\User;
use App\Support\Legal;
use Livewire\Volt\Volt;

test('terms page is publicly accessible', function () {
    $this->get(route('terms'))
        ->assertOk()
        ->assertSee('Terms of Service', false)
        ->assertSee('User Agreement', false)
        ->assertSee('SpeakLoud is not involved in payments between users', false)
        ->assertSee('six (6) months', false);
});

test('registration requires accepting terms', function () {
    Volt::test('auth.register')
        ->set('username', 'newuser')
        ->set('email', 'newuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', false)
        ->call('register')
        ->assertHasErrors(['accepted_terms']);

    expect(User::where('email', 'newuser@example.com')->exists())->toBeFalse();
});

test('registration stores terms acceptance when checkbox is checked', function () {
    Volt::test('auth.register')
        ->set('username', 'termsuser')
        ->set('email', 'termsuser@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    $user = User::where('email', 'termsuser@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->terms_version)->toBe(Legal::termsVersion());
});
