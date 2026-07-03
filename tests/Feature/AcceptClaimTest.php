<?php

use App\Actions\AcceptClaim;
use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('accepting a claim beyond schedule capacity shows a validation error', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-capacity@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostcap',
        'display_name' => 'Host Capacity',
    ]);

    $senderOne = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-one@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderTwo = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-two@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderThree = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-three@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create(['user_id' => $senderOne->id, 'username' => 'sender1', 'display_name' => 'Sender One']);
    UserProfile::create(['user_id' => $senderTwo->id, 'username' => 'sender2', 'display_name' => 'Sender Two']);
    UserProfile::create(['user_id' => $senderThree->id, 'username' => 'sender3', 'display_name' => 'Sender Three']);

    $schedule = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[0],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    Claim::create([
        'sender_id'   => $senderOne->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    Claim::create([
        'sender_id'   => $senderTwo->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    $pendingClaim = Claim::create([
        'sender_id'   => $senderThree->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'pending',
    ]);

    expect(fn () => app(AcceptClaim::class)->execute($pendingClaim->id, $host->id))
        ->toThrow(ValidationException::class, 'You have already accepted 2 of 2 claims for this slot.');

    expect($pendingClaim->fresh()->status)->toBe('pending');
});

test('claims page disables accept when schedule is at capacity', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-claims-ui@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderOne = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-ui-one@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderTwo = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-ui-two@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderThree = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-ui-three@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create(['user_id' => $host->id, 'username' => 'hostui', 'display_name' => 'Host UI']);
    UserProfile::create(['user_id' => $senderOne->id, 'username' => 'sui1', 'display_name' => 'Sender UI One']);
    UserProfile::create(['user_id' => $senderTwo->id, 'username' => 'sui2', 'display_name' => 'Sender UI Two']);
    UserProfile::create(['user_id' => $senderThree->id, 'username' => 'sui3', 'display_name' => 'Sender UI Three']);

    $schedule = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[0],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Tue',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    Claim::create([
        'sender_id'   => $senderOne->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    Claim::create([
        'sender_id'   => $senderTwo->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    Claim::create([
        'sender_id'   => $senderThree->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'pending',
    ]);

    Volt::actingAs($host)->test('claims.index')
        ->assertSee('Slot full (2/2). Increase max claims or decline.');
});
