<?php

use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('discover page lists other users open schedules', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-discover@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostdiscover',
        'display_name' => 'Host Discover',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-discover@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerdiscover',
        'display_name' => 'Viewer Discover',
    ]);

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
        'day_of_week' => 'Sat,Sun',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    Volt::test('discover.index')
        ->actingAs($viewer)
        ->assertSee('Open slots')
        ->assertSee('Host Discover')
        ->assertSee(ScheduleDescription::EXAMPLES[0]);
});

test('discover page can send a claim for a schedule', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-claim@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostclaim',
        'display_name' => 'Host Claim',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-claim@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerclaim',
        'display_name' => 'Viewer Claim',
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

    Volt::test('discover.index')
        ->actingAs($viewer)
        ->call('openClaimModal', $schedule->id)
        ->set('claimMessage', 'Would love to join!')
        ->call('sendClaim')
        ->assertSet('showClaimModal', false);

    expect($viewer->sentClaims()->where('schedule_id', $schedule->id)->where('status', 'pending')->exists())->toBeTrue();
});

test('discover page hides schedules the user already claimed', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-hide@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hosthide',
        'display_name' => 'Host Hide',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-hide@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerhide',
        'display_name' => 'Viewer Hide',
    ]);

    $claimed = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[0],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $claimed->id,
        'day_of_week' => 'Mon',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    Claim::create([
        'sender_id'   => $viewer->id,
        'receiver_id' => $host->id,
        'schedule_id' => $claimed->id,
        'type'        => 'schedule',
        'status'      => 'pending',
        'message'     => 'Already applied',
    ]);

    $available = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[1],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $available->id,
        'day_of_week' => 'Tue',
        'start_time'  => '20:00:00',
        'end_time'    => '21:00:00',
    ]);

    Volt::test('discover.index')
        ->actingAs($viewer)
        ->assertDontSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee(ScheduleDescription::EXAMPLES[1]);
});
