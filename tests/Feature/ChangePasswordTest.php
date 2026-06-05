<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

test('user can change password with current password', function () {
    $user = User::factory()->create(['password' => 'oldpassword']);

    Volt::actingAs($user)->test('profile.security')
        ->set('current_password', 'oldpassword')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('updatePassword')
        ->assertHasNoErrors()
        ->assertSet('status', 'password-changed');

    expect(Hash::check('newpassword123', $user->fresh()->password))->toBeTrue();
});

test('change password rejects incorrect current password', function () {
    $user = User::factory()->create(['password' => 'oldpassword']);

    Volt::actingAs($user)->test('profile.security')
        ->set('current_password', 'wrongpassword')
        ->set('password', 'newpassword123')
        ->set('password_confirmation', 'newpassword123')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('oldpassword', $user->fresh()->password))->toBeTrue();
});

test('unverified users are redirected from security page', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('profile.security'))
        ->assertRedirect(route('verification.notice'));
});
