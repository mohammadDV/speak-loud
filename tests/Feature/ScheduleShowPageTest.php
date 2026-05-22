<?php

use App\Actions\AcceptClaim;
use App\Actions\SyncScheduleGroupChat;
use App\Models\Claim;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('schedule show page displays details and members for host', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-show@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $host->id,
        'username'     => 'hostshow',
        'display_name' => 'Host Show',
    ]);

    $schedule = Schedule::create([
        'user_id'          => $host->id,
        'description'      => 'Weekly practice with video on.',
        'type'             => 'recurring',
        'language_id'      => $language->id,
        'max_participants' => 2,
        'status'           => 'active',
    ]);

    $schedule->recurringRule()->create([
        'day_of_week' => 'Mon',
        'start_time'  => '18:00:00',
        'end_time'    => '19:00:00',
    ]);

    app(SyncScheduleGroupChat::class)->execute($schedule);

    Volt::actingAs($host)->test('schedule.show', ['schedule' => $schedule->id])
        ->assertSee('English practice slot')
        ->assertSee('Host Show')
        ->assertSee('Members')
        ->assertSee('Group chat');
});

test('accepted member can access group chat on schedule page', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'host-member@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $member = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'member-show@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create(['user_id' => $host->id, 'username' => 'hostm', 'display_name' => 'Host M']);
    UserProfile::create(['user_id' => $member->id, 'username' => 'memberm', 'display_name' => 'Member M']);

    $schedule = Schedule::create([
        'user_id'          => $host->id,
        'description'      => 'One-off session rules here.',
        'type'             => 'one_time',
        'language_id'      => $language->id,
        'max_participants' => 1,
        'status'           => 'active',
    ]);

    $schedule->oneTimeSlot()->create([
        'start_datetime' => now()->addDays(2)->setTime(18, 0),
        'end_datetime'   => now()->addDays(2)->setTime(19, 0),
    ]);

    $claim = Claim::create([
        'sender_id'   => $member->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'pending',
    ]);

    app(AcceptClaim::class)->execute($claim->id, $host->id);

    $group = Conversation::where('schedule_id', $schedule->id)->where('type', 'schedule_group')->first();

    expect($group)->not->toBeNull()
        ->and(ConversationParticipant::where('conversation_id', $group->id)->where('user_id', $member->id)->exists())->toBeTrue();

    Volt::actingAs($member)->test('schedule.show', ['schedule' => $schedule->id])
        ->assertSee('Member M')
        ->assertSee('Group chat')
        ->set('newMessage', 'Hello everyone')
        ->call('sendMessage')
        ->assertHasNoErrors();
});
