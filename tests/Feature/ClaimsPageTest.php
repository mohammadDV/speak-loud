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

function createClaimsPageUser(string $email, string $username): User
{
    $user = User::create([
        'uuid' => (string) Str::uuid(),
        'email' => $email,
        'password' => '123456789',
        'role' => 'user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $user->id,
        'username' => $username,
        'display_name' => ucfirst($username),
    ]);

    return $user;
}

function createClaimsPageSchedule(User $host, Language $language): Schedule
{
    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 3,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    return $schedule;
}

test('claims page shows received and sent labels', function () {
    $host = createClaimsPageUser('claims-labels@speakloud.test', 'claimslabels');

    Volt::actingAs($host)->test('claims.index')
        ->assertSee('Received')
        ->assertSee('Sent')
        ->assertSee('Sort')
        ->assertSee('Status');
});

test('claims page filters received claims by status', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = createClaimsPageUser('claims-filter-host@speakloud.test', 'claimsfilterhost');
    $pendingSender = createClaimsPageUser('claims-filter-pending@speakloud.test', 'claimsfilterpending');
    $acceptedSender = createClaimsPageUser('claims-filter-accepted@speakloud.test', 'claimsfilteraccepted');

    $schedule = createClaimsPageSchedule($host, $language);

    Claim::create([
        'sender_id' => $pendingSender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    Claim::create([
        'sender_id' => $acceptedSender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'accepted',
    ]);

    Volt::actingAs($host)->test('claims.index')
        ->assertSee('Claimsfilterpending')
        ->assertSee('Claimsfilteraccepted')
        ->set('status', 'pending')
        ->assertSee('Claimsfilterpending')
        ->assertDontSee('Claimsfilteraccepted')
        ->assertSee('Received (1)')
        ->set('status', 'accepted')
        ->assertSee('Claimsfilteraccepted')
        ->assertDontSee('Claimsfilterpending')
        ->assertSee('Received (1)');
});

test('claims page sorts received claims by newest and oldest', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = createClaimsPageUser('claims-sort-host@speakloud.test', 'claimssorthost');
    $olderSender = createClaimsPageUser('claims-sort-older@speakloud.test', 'claimssortolder');
    $newerSender = createClaimsPageUser('claims-sort-newer@speakloud.test', 'claimssortnewer');

    $schedule = createClaimsPageSchedule($host, $language);

    $olderClaim = Claim::create([
        'sender_id' => $olderSender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    $newerClaim = Claim::create([
        'sender_id' => $newerSender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    // created_at is not fillable, so set timestamps explicitly for deterministic sorting.
    $olderClaim->forceFill([
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ])->saveQuietly();

    $newerClaim->forceFill([
        'created_at' => now(),
        'updated_at' => now(),
    ])->saveQuietly();

    Volt::actingAs($host)->test('claims.index')
        ->assertSet('sort', 'newest')
        ->assertSeeInOrder(['Claimssortnewer', 'Claimssortolder'])
        ->set('sort', 'oldest')
        ->assertSeeInOrder(['Claimssortolder', 'Claimssortnewer']);
});
