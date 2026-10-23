<?php

use App\Actions\WithdrawClaim;
use App\Models\Claim;
use App\Models\Conversation;
use App\Models\Language;
use App\Models\Message;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleAccess;
use App\Support\ScheduleDescription;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Volt\Volt;

function isolationLanguage(): Language
{
    return Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);
}

function isolationUser(string $email, string $username): User
{
    $user = User::factory()->create([
        'email' => $email,
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $user->id,
        'username' => $username,
        'profile_slug' => $username,
        'display_name' => $username,
    ]);

    return $user;
}

test('user cannot withdraw another users claim', function () {
    $host = isolationUser('host-withdraw@speakloud.test', 'hostwithdraw');
    $sender = isolationUser('sender-withdraw@speakloud.test', 'senderwithdraw');
    $attacker = isolationUser('attacker-withdraw@speakloud.test', 'attackerwithdraw');

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => isolationLanguage()->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Mon',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
        'message' => 'Please accept me',
    ]);

    expect(fn () => app(WithdrawClaim::class)->execute($claim->id, $attacker->id))
        ->toThrow(AuthorizationException::class);

    expect($claim->fresh()->status)->toBe('pending');

    Volt::actingAs($attacker)->test('claims.index')
        ->call('withdraw', $claim->id)
        ->assertForbidden();

    expect($claim->fresh()->status)->toBe('pending');
});

test('sender can withdraw their own pending claim', function () {
    $host = isolationUser('host-own-withdraw@speakloud.test', 'hostownwithdraw');
    $sender = isolationUser('sender-own-withdraw@speakloud.test', 'senderownwithdraw');

    $claim = Claim::create([
        'sender_id' => $sender->id,
        'receiver_id' => $host->id,
        'type' => 'direct',
        'status' => 'pending',
        'message' => 'Hi',
    ]);

    $result = app(WithdrawClaim::class)->execute($claim->id, $sender->id);

    expect($result->status)->toBe('withdrawn');
});

test('user cannot open another users direct conversation', function () {
    $alice = isolationUser('alice-msg@speakloud.test', 'alicemsg');
    $bob = isolationUser('bob-msg@speakloud.test', 'bobmsg');
    $attacker = isolationUser('attacker-msg@speakloud.test', 'attackermsg');

    $conversation = Conversation::create([
        'type' => 'direct',
        'user_a_id' => min($alice->id, $bob->id),
        'user_b_id' => max($alice->id, $bob->id),
    ]);

    Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => $alice->id,
        'body' => 'Secret between Alice and Bob',
    ]);

    $this->actingAs($attacker)
        ->get(route('messages.show', $conversation->id))
        ->assertForbidden();
});

test('user cannot send a message into another users conversation via livewire state', function () {
    $alice = isolationUser('alice-send@speakloud.test', 'alicesend');
    $bob = isolationUser('bob-send@speakloud.test', 'bobsend');
    $attacker = isolationUser('attacker-send@speakloud.test', 'attackersend');

    $conversation = Conversation::create([
        'type' => 'direct',
        'user_a_id' => min($alice->id, $bob->id),
        'user_b_id' => max($alice->id, $bob->id),
    ]);

    Volt::actingAs($attacker)->test('messages.index')
        ->set('activeConversation', $conversation->id)
        ->set('newMessage', 'Injected message')
        ->call('sendMessage')
        ->assertForbidden();

    expect(Message::where('conversation_id', $conversation->id)->count())->toBe(0);
});

test('outsider can view an active schedule details page but not members or group chat', function () {
    $host = isolationUser('host-sched@speakloud.test', 'hostsched');
    $member = isolationUser('member-sched@speakloud.test', 'membersched');
    $outsider = isolationUser('outsider-sched@speakloud.test', 'outsidersched');

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => isolationLanguage()->id,
        'max_participants' => 2,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Tue',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    Claim::create([
        'sender_id' => $member->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'accepted',
    ]);

    expect(ScheduleAccess::canView($schedule, $outsider->id))->toBeTrue()
        ->and(ScheduleAccess::canSeeParticipants($schedule, $outsider->id))->toBeFalse()
        ->and(ScheduleAccess::canAccessGroupChat($schedule, $outsider->id))->toBeFalse()
        ->and(ScheduleAccess::canView($schedule, $host->id))->toBeTrue()
        ->and(ScheduleAccess::canSeeParticipants($schedule, $member->id))->toBeTrue();

    $this->actingAs($outsider)
        ->get(route('schedules.show', $schedule))
        ->assertSuccessful()
        ->assertDontSee('Members')
        ->assertSee('Send a claim and get accepted to join the group chat');
});

test('pending claimer can view schedule but not group chat', function () {
    $host = isolationUser('host-pending@speakloud.test', 'hostpending');
    $claimer = isolationUser('claimer-pending@speakloud.test', 'claimerpending');

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => isolationLanguage()->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    Claim::create([
        'sender_id' => $claimer->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
    ]);

    expect(ScheduleAccess::canView($schedule, $claimer->id))->toBeTrue()
        ->and(ScheduleAccess::canAccessGroupChat($schedule, $claimer->id))->toBeFalse();

    $this->actingAs($claimer)
        ->get(route('schedules.show', $schedule))
        ->assertSuccessful();
});
