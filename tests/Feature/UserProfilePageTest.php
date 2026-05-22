<?php

use App\Models\Claim;
use App\Models\Conversation;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('public user profile page shows profile details and open schedules', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-profile@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostprofile',
        'profile_slug' => 'hostprofile',
        'display_name' => 'Host Profile',
        'bio'          => 'Friendly conversation partner.',
        'country_code' => 'DE',
    ]);

    $host->languages()->create([
        'language_id' => $language->id,
        'type'        => 'native',
        'level'       => 'fluent',
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
        'day_of_week' => 'Sat',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    $this->get(route('users.show', 'hostprofile'))
        ->assertOk()
        ->assertSee('Host Profile')
        ->assertSee('Friendly conversation partner.')
        ->assertSee('Languages')
        ->assertSee(ScheduleDescription::EXAMPLES[0])
        ->assertSee('Sign in to connect');
});

test('profile page shows open chat when conversation exists', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-chat@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostchat',
        'profile_slug' => 'hostchat',
        'display_name' => 'Host Chat',
        'country_code' => 'US',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-chat@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerchat',
        'display_name' => 'Viewer Chat',
    ]);

    $conversation = Conversation::create([
        'type'      => 'direct',
        'user_a_id' => min($host->id, $viewer->id),
        'user_b_id' => max($host->id, $viewer->id),
    ]);

    Volt::actingAs($viewer)->test('users.show', ['profileSlug' => 'hostchat'])
        ->assertSee('Open chat')
        ->assertSeeHtml('href="'.route('messages.show', $conversation->id).'"');
});

test('profile page can send a direct claim when no conversation exists', function () {
    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-direct@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostdirect',
        'profile_slug' => 'hostdirect',
        'display_name' => 'Host Direct',
    ]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-direct@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerdirect',
        'display_name' => 'Viewer Direct',
    ]);

    Volt::actingAs($viewer)->test('users.show', ['profileSlug' => 'hostdirect'])
        ->call('openDirectClaimModal')
        ->set('claimMessage', 'Let us practice!')
        ->call('sendDirectClaim')
        ->assertSet('showDirectClaimModal', false);

    expect(Claim::query()
        ->where('sender_id', $viewer->id)
        ->where('receiver_id', $host->id)
        ->where('type', 'direct')
        ->where('status', 'pending')
        ->exists())->toBeTrue();
});

test('guest is redirected to login when trying to claim from profile', function () {
    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-guest-claim@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostguestclaim',
        'profile_slug' => 'hostguestclaim',
        'display_name' => 'Host Guest Claim',
    ]);

    Volt::test('users.show', ['profileSlug' => 'hostguestclaim'])
        ->call('openDirectClaimModal')
        ->assertRedirect(route('login'));

    expect(session('pending_direct_claim'))->toMatchArray([
        'receiver_id'  => $host->id,
        'profile_slug' => 'hostguestclaim',
    ]);
});

test('private profile only shows display name to visitors', function () {
    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-private@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostprivate',
        'profile_slug' => '12345678',
        'display_name' => 'Private Host',
        'bio'          => 'Secret bio',
        'country_code' => 'DE',
        'is_private'   => true,
    ]);

    $this->get(route('users.show', '12345678'))
        ->assertOk()
        ->assertSee('Private Host')
        ->assertSee('This profile is private')
        ->assertDontSee('Secret bio')
        ->assertDontSee('Open slots')
        ->assertDontSee('@hostprivate');
});

test('user can toggle private profile on edit page', function () {
    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'private-toggle@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'privatetoggle',
        'profile_slug' => 'privatetoggle',
        'display_name' => 'Toggle User',
        'is_private'   => false,
    ]);

    Volt::actingAs($user)->test('profile.edit')
        ->assertSet('is_private', false)
        ->set('is_private', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($user->profile->fresh()->is_private)->toBeTrue();
});

test('user can change profile slug when unique', function () {
    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'slug-edit@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'user9',
        'profile_slug' => 'user9',
        'display_name' => 'Slug Editor',
    ]);

    Volt::actingAs($user)->test('profile.edit')
        ->set('profile_slug', '12345678')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->profile->fresh()->profile_slug)->toBe('12345678');

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'slug-viewer@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'slugviewer',
        'profile_slug' => 'slugviewer',
        'display_name' => 'Slug Viewer',
    ]);

    $this->actingAs($viewer)->get(route('users.show', '12345678'))
        ->assertOk()
        ->assertSee('Slug Editor');

    $this->actingAs($viewer)->get(route('users.show', 'user9'))->assertNotFound();
});
