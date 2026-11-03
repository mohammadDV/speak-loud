<?php

use App\Actions\SendClaim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Volt\Volt;

test('unverified users cannot send a claim via action', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $sender = User::factory()->unverified()->create([
        'email' => 'unverified-sender@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $sender->id,
        'username' => 'unverifiedsender',
        'profile_slug' => 'unverifiedsender',
        'display_name' => 'Unverified Sender',
    ]);

    $host = User::factory()->create([
        'email' => 'host-verified-claim@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => 'hostverifiedclaim',
        'profile_slug' => 'hostverifiedclaim',
        'display_name' => 'Host Verified Claim',
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    expect(fn () => app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hello',
    ]))->toThrow(AuthorizationException::class);
});

test('unverified users are redirected to verify email when opening claim modal', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $viewer = User::factory()->unverified()->create([
        'email' => 'unverified-viewer@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $viewer->id,
        'username' => 'unverifiedviewer',
        'profile_slug' => 'unverifiedviewer',
        'display_name' => 'Unverified Viewer',
    ]);

    $host = User::factory()->create([
        'email' => 'host-discover-verify@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => 'hostdiscoververify',
        'profile_slug' => 'hostdiscoververify',
        'display_name' => 'Host Discover Verify',
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    Volt::actingAs($viewer)->test('discover.index')
        ->call('openClaimModal', $schedule->id)
        ->assertRedirect(route('verification.notice'));
});

test('unverified users with pending claim are redirected to verify email on discover', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $viewer = User::factory()->unverified()->create([
        'email' => 'unverified-pending@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $viewer->id,
        'username' => 'unverifiedpending',
        'profile_slug' => 'unverifiedpending',
        'display_name' => 'Unverified Pending',
    ]);

    $host = User::factory()->create([
        'email' => 'host-pending-verify@speakloudmate.test',
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => 'hostpendingverify',
        'profile_slug' => 'hostpendingverify',
        'display_name' => 'Host Pending Verify',
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    session(['pending_claim_schedule_id' => $schedule->id]);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertRedirect(route('verification.notice'))
        ->assertSet('showClaimModal', false);
});
