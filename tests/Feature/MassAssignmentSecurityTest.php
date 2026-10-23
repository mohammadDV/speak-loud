<?php

use App\Models\User;
use Illuminate\Support\Str;

test('role and status are not mass assignable on user create', function () {
    $user = User::create([
        'uuid' => (string) Str::uuid(),
        'email' => 'massassign@example.com',
        'password' => 'Password1!',
        'role' => 'admin',
        'status' => 'banned',
        'terms_accepted_at' => now(),
        'terms_version' => config('legal.terms_version'),
    ]);

    expect($user->fresh()->role)->toBe('user')
        ->and($user->fresh()->status)->toBe('active');
});

test('role can still be set via force fill for admin tooling', function () {
    $user = User::factory()->create();

    $user->forceFill(['role' => 'admin', 'status' => 'active'])->save();

    expect($user->fresh()->role)->toBe('admin');
});
