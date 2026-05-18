<?php

use App\Actions\RejectClaim;
use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Support\Str;

test('rejecting a claim creates a conversation with optional decline message', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-reject@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostreject',
        'display_name' => 'Host Reject',
    ]);

    $sender = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-reject@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $sender->id,
        'username'     => 'senderreject',
        'display_name' => 'Sender Reject',
    ]);

    $schedule = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[0],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 1,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    $claim = Claim::create([
        'sender_id'   => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'pending',
        'message'     => 'Would love to join your slot!',
    ]);

    $result = app(RejectClaim::class)->execute($claim->id, $host->id, 'Sorry, fully booked this week.');

    expect($result->status)->toBe('rejected')
        ->and($result->responded_at)->not->toBeNull()
        ->and($result->conversation)->not->toBeNull();

    $messages = $result->conversation->messages()->orderBy('id')->get();

    expect($messages)->toHaveCount(2)
        ->and($messages[0]->body)->toBe('Would love to join your slot!')
        ->and($messages[1]->body)->toBe('Sorry, fully booked this week.');
});
