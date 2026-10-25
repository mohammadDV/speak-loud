<?php

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Support\UserWantsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('missing preference defaults to enabled', function () {
    $user = User::factory()->create();

    expect(UserWantsNotification::email($user, NotificationType::ClaimReceived))->toBeTrue();
});

test('enabled preference allows email', function () {
    $user = User::factory()->create();

    UserNotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => NotificationType::ClaimReceived->value,
        'channel' => 'email',
        'is_enabled' => true,
    ]);

    expect(UserWantsNotification::email($user, NotificationType::ClaimReceived))->toBeTrue();
});

test('disabled preference blocks email', function () {
    $user = User::factory()->create();

    UserNotificationPreference::create([
        'user_id' => $user->id,
        'notification_type' => NotificationType::ClaimAccepted->value,
        'channel' => 'email',
        'is_enabled' => false,
    ]);

    expect(UserWantsNotification::email($user, NotificationType::ClaimAccepted))->toBeFalse();
});
