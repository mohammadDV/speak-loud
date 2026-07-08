<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('user can change password with current password', function () {
    $user = User::factory()->create(['password' => 'Oldpassword1!']);

    Volt::actingAs($user)->test('profile.security')
        ->set('current_password', 'Oldpassword1!')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('status', 'password-changed');

    expect(Hash::check('Newpassword1!', $user->fresh()->password))->toBeTrue();
});

test('change password rejects incorrect current password', function () {
    $user = User::factory()->create(['password' => 'Oldpassword1!']);

    Volt::actingAs($user)->test('profile.security')
        ->set('current_password', 'wrongpassword')
        ->set('password', 'Newpassword1!')
        ->set('password_confirmation', 'Newpassword1!')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('Oldpassword1!', $user->fresh()->password))->toBeTrue();
});

test('unverified users are redirected from security page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.security'))
        ->assertRedirect(route('verification.notice'));
});
