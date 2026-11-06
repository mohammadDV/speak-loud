<?php

use App\Actions\AcceptClaim;
use App\Actions\ChangePassword;
use App\Actions\CreateTicket;
use App\Actions\RegisterUser;
use App\Actions\RejectClaim;
use App\Actions\ReplyToTicket;
use App\Actions\SendClaim;
use App\Actions\WithdrawClaim;
use App\Jobs\SendTelegramMessage;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;

function enableTelegramActionConfig(): void
{
    config([
        'services.telegram.enabled' => true,
        'services.telegram.bot_token' => 'test-bot-token',
        'services.telegram.notif_chat_id' => '@notif',
        'services.telegram.error_chat_id' => '@error',
        'services.telegram.queue' => 'low',
    ]);
}

function createTelegramClaimPair(string $suffix): array
{
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::factory()->create([
        'email' => "host-tg-{$suffix}@speakloudmate.test",
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => "hosttg{$suffix}",
        'display_name' => "Host {$suffix}",
    ]);

    $sender = User::factory()->create([
        'email' => "sender-tg-{$suffix}@speakloudmate.test",
    ]);

    UserProfile::create([
        'user_id' => $sender->id,
        'username' => "sendertg{$suffix}",
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

    return compact('host', 'sender', 'schedule');
}

test('registering a user queues a telegram notification', function () {
    Bus::fake();
    enableTelegramActionConfig();

    app(RegisterUser::class)->execute([
        'email' => 'new-user@speakloudmate.test',
        'password' => 'Password1!',
        'username' => 'newtelegramuser',
        'display_name' => 'New User',
    ]);

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@notif'
            && str_contains($job->text, 'New registration')
            && str_contains($job->text, '@newtelegramuser')
            && str_contains($job->text, 'new-user@speakloudmate.test');
    });
});

test('creating a ticket queues a telegram notification', function () {
    Bus::fake();
    enableTelegramActionConfig();

    $user = User::factory()->create(['email' => 'ticket-tg@speakloudmate.test']);

    app(CreateTicket::class)->execute(
        $user,
        null,
        'Need help',
        'Something is wrong with my account.',
    );

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@notif'
            && str_contains($job->text, 'Ticket created')
            && str_contains($job->text, 'Need help')
            && str_contains($job->text, 'ticket-tg@speakloudmate.test');
    });
});

test('user ticket reply queues a telegram notification but staff reply does not', function () {
    Bus::fake();
    enableTelegramActionConfig();

    $user = User::factory()->create(['email' => 'reply-user@speakloudmate.test']);
    $admin = User::factory()->create([
        'email' => 'reply-admin@speakloudmate.test',
        'role' => 'admin',
    ]);

    $ticket = Ticket::create([
        'user_id' => $user->id,
        'subject' => 'Help',
        'status' => 'waiting_user',
        'priority' => 'normal',
    ]);

    app(ReplyToTicket::class)->execute($ticket, $admin, 'We are looking into it.');

    Bus::assertNotDispatched(SendTelegramMessage::class);

    app(ReplyToTicket::class)->execute($ticket->fresh(), $user, 'Thanks, still broken.');

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@notif'
            && str_contains($job->text, 'Ticket reply')
            && str_contains($job->text, 'reply-user@speakloudmate.test');
    });
});

test('changing a password queues a telegram notification', function () {
    Bus::fake();
    enableTelegramActionConfig();

    $user = User::factory()->create([
        'email' => 'password-tg@speakloudmate.test',
        'password' => Hash::make('OldPassword1!'),
    ]);

    $this->actingAs($user);

    app(ChangePassword::class)->execute($user, 'OldPassword1!', 'NewPassword1!');

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job): bool {
        return $job->chatId === '@notif'
            && str_contains($job->text, 'Password changed')
            && str_contains($job->text, 'password-tg@speakloudmate.test')
            && ! str_contains($job->text, 'NewPassword1!');
    });
});

test('claim lifecycle events queue telegram notifications', function () {
    Bus::fake();
    enableTelegramActionConfig();

    ['host' => $host, 'sender' => $sender, 'schedule' => $schedule] = createTelegramClaimPair('life');

    $claim = app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'I would like to join.',
    ]);

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job) use ($claim): bool {
        return str_contains($job->text, 'Claim sent')
            && str_contains($job->text, "#{$claim->id}");
    });

    Bus::fake();

    app(AcceptClaim::class)->execute($claim->id, $host->id);

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job) use ($claim): bool {
        return str_contains($job->text, 'Claim accepted')
            && str_contains($job->text, "#{$claim->id}");
    });

    Bus::fake();
    ['host' => $host2, 'sender' => $sender2, 'schedule' => $schedule2] = createTelegramClaimPair('rej');

    $rejected = app(SendClaim::class)->execute([
        'sender_id' => $sender2->id,
        'receiver_id' => $host2->id,
        'schedule_id' => $schedule2->id,
        'message' => 'Please accept.',
    ]);

    Bus::fake();
    app(RejectClaim::class)->execute($rejected->id, $host2->id);

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job) use ($rejected): bool {
        return str_contains($job->text, 'Claim rejected')
            && str_contains($job->text, "#{$rejected->id}");
    });

    Bus::fake();
    ['host' => $host3, 'sender' => $sender3, 'schedule' => $schedule3] = createTelegramClaimPair('wd');

    $withdrawn = app(SendClaim::class)->execute([
        'sender_id' => $sender3->id,
        'receiver_id' => $host3->id,
        'schedule_id' => $schedule3->id,
        'message' => 'Joining.',
    ]);

    Bus::fake();
    app(WithdrawClaim::class)->execute($withdrawn->id, $sender3->id);

    Bus::assertDispatched(SendTelegramMessage::class, function (SendTelegramMessage $job) use ($withdrawn): bool {
        return str_contains($job->text, 'Claim withdrawn')
            && str_contains($job->text, "#{$withdrawn->id}");
    });
});
