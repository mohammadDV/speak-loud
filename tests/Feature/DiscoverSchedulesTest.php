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

test('guests can browse discover without logging in', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-guest@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostguest',
        'display_name' => 'Host Guest',
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
        'day_of_week' => 'Wed',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('Open slots')
        ->assertSee('Host Guest')
        ->assertSee(ScheduleDescription::EXAMPLES[0]);

    Volt::test('discover.index')
        ->assertSee('Sign in to send a claim')
        ->call('openClaimModal', $schedule->id)
        ->assertRedirect(route('login'));

    expect(session('pending_claim_schedule_id'))->toBe($schedule->id);
});

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

    Volt::actingAs($viewer)->test('discover.index')
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

    session(['pending_claim_schedule_id' => $schedule->id]);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertSet('showClaimModal', true)
        ->assertSet('claimScheduleId', $schedule->id)
        ->assertSee('Send claim')
        ->assertSee('Host Claim')
        ->assertSee('English')
        ->assertSee('Mon')
        ->assertSee('18:00')
        ->assertSee('Session rules')
        ->assertSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee('1 spot left')
        ->set('claimMessage', 'Would love to join!')
        ->call('sendClaim')
        ->assertSet('showClaimModal', false);

    expect($viewer->sentClaims()->where('schedule_id', $schedule->id)->where('status', 'pending')->exists())->toBeTrue();
});

test('discover page hides schedules with no remaining spots', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-full@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostfull',
        'display_name' => 'Host Full',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-full@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerfull',
        'display_name' => 'Viewer Full',
    ]);

    $full = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[0],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 1,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $full->id,
        'day_of_week' => 'Mon',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    $applicant = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'applicant-full@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $applicant->id,
        'username'     => 'applicantfull',
        'display_name' => 'Applicant Full',
    ]);

    Claim::create([
        'sender_id'   => $applicant->id,
        'receiver_id' => $host->id,
        'schedule_id' => $full->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    $open = Schedule::create([
        'user_id'          => $host->id,
        'description'      => ScheduleDescription::EXAMPLES[1],
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $open->id,
        'day_of_week' => 'Tue',
        'start_time'  => '20:00:00',
        'end_time'    => '21:00:00',
    ]);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertDontSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee(ScheduleDescription::EXAMPLES[1]);
});

test('discover page hides schedules while claim is pending or accepted', function () {
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

    Volt::actingAs($viewer)->test('discover.index')
        ->assertDontSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee(ScheduleDescription::EXAMPLES[1]);

    Claim::where('schedule_id', $claimed->id)->update(['status' => 'accepted']);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertDontSee(ScheduleDescription::EXAMPLES[0]);
});

test('discover page shows schedule again after claim is declined', function () {
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

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-reject@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerreject',
        'display_name' => 'Viewer Reject',
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
        'day_of_week' => 'Wed',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    Claim::create([
        'sender_id'   => $viewer->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'pending',
    ]);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertDontSee(ScheduleDescription::EXAMPLES[0]);

    Claim::where('schedule_id', $schedule->id)->update(['status' => 'rejected']);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee('Send claim');
});
