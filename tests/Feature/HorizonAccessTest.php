<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('active admins can view horizon', function () {
    $admin = User::factory()->admin()->create([
        'status' => 'active',
    ]);

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeTrue();
});

test('non-admin users cannot view horizon', function () {
    $user = User::factory()->create([
        'role' => 'user',
        'status' => 'active',
    ]);

    expect(Gate::forUser($user)->allows('viewHorizon'))->toBeFalse();
});

test('inactive admins cannot view horizon', function () {
    $admin = User::factory()->admin()->inactive()->create();

    expect(Gate::forUser($admin)->allows('viewHorizon'))->toBeFalse();
});

test('guests cannot view horizon', function () {
    expect(Gate::inspect('viewHorizon')->allowed())->toBeFalse();
});
