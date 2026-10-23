<?php

use App\Actions\AcceptClaim;
use App\Actions\RejectClaim;
use App\Actions\SendClaim;
use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;

function claimAuthLanguage(): Language
{
    return Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);
}

function claimAuthUser(string $email, string $username, array $profile = []): User
{
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);

    UserProfile::create(array_merge([
        'user_id' => $user->id,
        'username' => $username,
        'profile_slug' => $username,
        'display_name' => $username,
        'is_private' => false,
    ], $profile));

    return $user->fresh('profile');
}

function claimAuthSchedule(User $host): Schedule
{
    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => claimAuthLanguage()->id,
        'max_participants' => 2,
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

test('user cannot accept another users claim', function () {
    $host = claimAuthUser('host-accept@speakloud.test', 'hostaccept');
    $sender = claimAuthUser('sender-accept@speakloud.test', 'senderaccept');
    $attacker = claimAuthUser('attacker-accept@speakloud.test', 'attackeraccept');
    $schedule = claimAuthSchedule($host);

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    expect(fn () => app(AcceptClaim::class)->execute($claim->id, $attacker->id))
        ->toThrow(AuthorizationException::class);

    expect($claim->fresh()->status)->toBe('pending');
});

test('user cannot reject another users claim', function () {
    $host = claimAuthUser('host-reject@speakloud.test', 'hostreject');
    $sender = claimAuthUser('sender-reject@speakloud.test', 'senderreject');
    $attacker = claimAuthUser('attacker-reject@speakloud.test', 'attackerreject');
    $schedule = claimAuthSchedule($host);

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    expect(fn () => app(RejectClaim::class)->execute($claim->id, $attacker->id))
        ->toThrow(AuthorizationException::class);

    expect($claim->fresh()->status)->toBe('pending');
});

test('cannot send claim to blocked user', function () {
    $host = claimAuthUser('host-block@speakloud.test', 'hostblock');
    $sender = claimAuthUser('sender-block@speakloud.test', 'senderblock');
    $schedule = claimAuthSchedule($host);

    DB::table('user_blocks')->insert([
        'blocker_id' => $host->id,
        'blocked_id' => $sender->id,
        'created_at' => now(),
    ]);

    expect(fn () => app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hi',
    ]))->toThrow(AuthorizationException::class);
});

test('cannot send direct message to private profile', function () {
    $host = claimAuthUser('host-private@speakloud.test', 'hostprivate', ['is_private' => true]);
    $sender = claimAuthUser('sender-private@speakloud.test', 'senderprivate');

    expect(fn () => app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'message' => 'Hi',
    ]))->toThrow(AuthorizationException::class);
});

test('can send schedule claim to private host', function () {
    $host = claimAuthUser('host-private-slot@speakloud.test', 'hostprivateslot', ['is_private' => true]);
    $sender = claimAuthUser('sender-private-slot@speakloud.test', 'senderprivateslot');
    $schedule = claimAuthSchedule($host);

    $claim = app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hi',
    ]);

    expect($claim->fresh()->type)->toBe('schedule')
        ->and($claim->fresh()->status)->toBe('pending');
});

test('cannot send claim with mismatched schedule and receiver', function () {
    $host = claimAuthUser('host-mismatch@speakloud.test', 'hostmismatch');
    $other = claimAuthUser('other-mismatch@speakloud.test', 'othermismatch');
    $sender = claimAuthUser('sender-mismatch@speakloud.test', 'sendermismatch');
    $schedule = claimAuthSchedule($host);

    expect(fn () => app(SendClaim::class)->execute([
        'sender_id' => $sender->id,
        'receiver_id' => $other->id,
        'schedule_id' => $schedule->id,
        'message' => 'Hi',
    ]))->toThrow(AuthorizationException::class);
});

test('private host schedules still appear on discover', function () {
    $host = claimAuthUser('host-discover-private@speakloud.test', 'hostdiscpriv', [
        'is_private' => true,
        'display_name' => 'Private Discover Host',
    ]);
    $viewer = claimAuthUser('viewer-discover-private@speakloud.test', 'viewerdiscpriv');
    claimAuthSchedule($host);

    Volt::actingAs($viewer)->test('discover.index')
        ->assertSee('Private Discover Host');
});

test('non admin cannot access filament admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
