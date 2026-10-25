<?php

use App\Actions\AcceptClaim;
use App\Actions\RejectClaim;
use App\Actions\SendClaim;
use App\Actions\WithdrawClaim;
use App\Enums\NotificationType;
use App\Events\ClaimSent;
use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Models\UserProfile;
use App\Notifications\ClaimAcceptedNotification;
use App\Notifications\ClaimReceivedNotification;
use App\Notifications\ClaimRejectedNotification;
use App\Notifications\ClaimWithdrawnNotification;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use App\Support\ScheduleDescription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function createClaimPair(string $suffix): array
{
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid' => (string) Str::uuid(),
        'email' => "host-{$suffix}@speakloud.test",
        'password' => '123456789',
        'role' => 'user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => "host{$suffix}",
        'display_name' => "Host {$suffix}",
    ]);

    $sender = User::create([
        'uuid' => (string) Str::uuid(),
        'email' => "sender-{$suffix}@speakloud.test",
        'password' => '123456789',
        'role' => 'user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $sender->id,
        'username' => "sender{$suffix}",
        'display_name' => "Sender {$suffix}",
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 2,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    return compact('host', 'sender', 'schedule', 'language');
}

test('auth email notifications are queued', function () {
    expect(new VerifyEmail)->toBeInstanceOf(ShouldQueue::class)
        ->and(new ResetPassword('token'))->toBeInstanceOf(ShouldQueue::class);
});

test('sending a claim notifies the receiver', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('sent');

    app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'I would like to join.',
    ]);

    Notification::assertSentTo($host, ClaimReceivedNotification::class);
    Notification::assertNotSentTo($sender, ClaimReceivedNotification::class);
});

test('reopening a claim notifies the receiver again', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('reopen');

    Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'rejected',
        'message' => 'Earlier claim',
        'responded_at' => now(),
    ]);

    app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Trying again.',
    ]);

    Notification::assertSentTo($host, ClaimReceivedNotification::class);
});

test('accepting a claim notifies the sender', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('accept');

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
        'message' => 'Please accept',
    ]);

    app(AcceptClaim::class)->execute($claim->id, $host->id);

    Notification::assertSentTo($sender, ClaimAcceptedNotification::class);
    Notification::assertNotSentTo($host, ClaimAcceptedNotification::class);
});

test('rejecting a claim notifies the sender', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('reject');

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
        'message' => 'Please accept',
    ]);

    app(RejectClaim::class)->execute($claim->id, $host->id);

    Notification::assertSentTo($sender, ClaimRejectedNotification::class);
    Notification::assertNotSentTo($host, ClaimRejectedNotification::class);
});

test('withdrawing a claim notifies the receiver', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('withdraw');

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
        'message' => 'Please accept',
    ]);

    app(WithdrawClaim::class)->execute($claim->id, $sender->id);

    Notification::assertSentTo($host, ClaimWithdrawnNotification::class);
    Notification::assertNotSentTo($sender, ClaimWithdrawnNotification::class);
});

test('disabled claim received preference skips email', function () {
    Notification::fake();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('optout');

    UserNotificationPreference::create([
        'user_id' => $host->id,
        'notification_type' => NotificationType::ClaimReceived->value,
        'channel' => 'email',
        'is_enabled' => false,
    ]);

    app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hello',
    ]);

    Notification::assertNotSentTo($host, ClaimReceivedNotification::class);
});

test('send claim dispatches ClaimSent event', function () {
    Event::fake([ClaimSent::class]);

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createClaimPair('event');

    app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hello',
    ]);

    Event::assertDispatched(ClaimSent::class);
});
