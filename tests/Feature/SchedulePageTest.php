<?php

use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

function createScheduleTestUser(string $email, string $username): User
{
    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => $email,
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => $username,
        'display_name' => ucfirst($username),
    ]);

    return $user;
}

function saveRecurringSlotViaSchedulePage(User $user, Language $language, string $title): void
{
    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', $title)
        ->set('selected_days', ['Sat'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasNoErrors();
}

test('schedule page can open and close new slot modal', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-test@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduletest',
        'display_name' => 'Schedule Test',
    ]);

    Volt::actingAs($user)->test('schedule.index')
        ->assertSet('showModal', false)
        ->call('openModal')
        ->assertSet('showModal', true)
        ->assertSee('New time slot')
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('schedule page can save a recurring slot', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-save@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'schedulesave',
        'display_name' => 'Schedule Save',
    ]);

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Weekend English chat')
        ->set('selected_days', ['Sat', 'Sun'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $schedule = $user->schedules()->first();

    expect($schedule->title)->toBe('Weekend English chat')
        ->and($schedule->description)->toBe('Video call on Google Meet. 50/50 language split.');
});

test('schedule page can edit a recurring slot', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-edit@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleedit',
        'display_name' => 'Schedule Edit',
    ]);

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Saturday slot')
        ->set('selected_days', ['Sat'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Camera on. 50/50 language split.')
        ->call('saveSlot');

    $schedule = $user->schedules()->first();

    Volt::actingAs($user)->test('schedule.index')
        ->call('editSchedule', $schedule->id)
        ->assertSet('editingScheduleId', $schedule->id)
        ->set('title', 'Mon & Wed evenings')
        ->set('selected_days', ['Mon', 'Wed'])
        ->set('start_time', '20:00')
        ->set('end_time', '21:00')
        ->set('description', 'Updated rules: voice call only, beginner-friendly.')
        ->call('saveSlot')
        ->assertHasNoErrors();

    $schedule->refresh();
    expect($schedule->title)->toBe('Mon & Wed evenings')
        ->and($schedule->recurringRule->day_of_week)->toBe('Mon,Wed')
        ->and(substr((string) $schedule->recurringRule->start_time, 0, 5))->toBe('20:00')
        ->and($schedule->description)->toBe('Updated rules: voice call only, beginner-friendly.');
});

test('schedule page can save a one-off slot starting tomorrow or later', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-onetime@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleonetime',
        'display_name' => 'Schedule One Time',
    ]);

    $start = now()->addDay()->setTime(18, 0)->format('Y-m-d\TH:i');
    $end   = now()->addDay()->setTime(19, 0)->format('Y-m-d\TH:i');

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Trial session')
        ->set('start_datetime', $start)
        ->set('end_datetime', $end)
        ->set('description', 'One-off video call. Camera on.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $schedule = $user->schedules()->with('oneTimeSlot')->first();

    expect($schedule->title)->toBe('Trial session')
        ->and($schedule->type)->toBe('one_time')
        ->and($schedule->oneTimeSlot)->not->toBeNull();
});

test('schedule page rejects one-off slot starting before tomorrow', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-onetime-past@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleonetimepast',
        'display_name' => 'Schedule One Time Past',
    ]);

    $start = now()->setTime(18, 0)->format('Y-m-d\TH:i');
    $end   = now()->setTime(19, 0)->format('Y-m-d\TH:i');

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Too soon')
        ->set('start_datetime', $start)
        ->set('end_datetime', $end)
        ->set('description', 'One-off video call. Camera on.')
        ->call('saveSlot')
        ->assertHasErrors(['start_datetime']);

    expect($user->schedules()->count())->toBe(0);
});

test('schedule page rejects one-off slot on a different day than start', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-onetime-day@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleoneday',
        'display_name' => 'Schedule One Day',
    ]);

    $start = now('UTC')->addDay()->setTime(18, 0)->format('Y-m-d\TH:i');
    $end   = now('UTC')->addDays(2)->setTime(19, 0)->format('Y-m-d\TH:i');

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Cross-day slot')
        ->set('start_datetime', $start)
        ->set('end_datetime', $end)
        ->set('description', 'One-off video call. Camera on.')
        ->call('saveSlot')
        ->assertHasErrors(['end_datetime']);

    expect($user->schedules()->count())->toBe(0);
});

test('schedule page rejects one-off slot shorter than 15 minutes', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-onetime-short@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleoneshort',
        'display_name' => 'Schedule One Short',
    ]);

    $day = now('UTC')->addDay();
    $start = $day->copy()->setTime(18, 0)->format('Y-m-d\TH:i');
    $end   = $day->copy()->setTime(18, 10)->format('Y-m-d\TH:i');

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Short slot')
        ->set('start_datetime', $start)
        ->set('end_datetime', $end)
        ->set('description', 'One-off video call. Camera on.')
        ->call('saveSlot')
        ->assertHasErrors(['end_datetime']);

    expect($user->schedules()->count())->toBe(0);
});

test('schedule page accepts one-off slot that is exactly 15 minutes', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-onetime-15@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduleone15',
        'display_name' => 'Schedule One 15',
    ]);

    $day = now('UTC')->addDay();
    $start = $day->copy()->setTime(18, 0)->format('Y-m-d\TH:i');
    $end   = $day->copy()->setTime(18, 15)->format('Y-m-d\TH:i');

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Fifteen minute slot')
        ->set('start_datetime', $start)
        ->set('end_datetime', $end)
        ->set('description', 'One-off video call. Camera on.')
        ->call('saveSlot')
        ->assertHasNoErrors();

    $slot = $user->schedules()->with('oneTimeSlot')->first()->oneTimeSlot;

    expect($slot->start_datetime->diffInMinutes($slot->end_datetime))->toEqual(15);
});

test('schedule page requires a title when saving a slot', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-title@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduletitle',
        'display_name' => 'Schedule Title',
    ]);

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('selected_days', ['Sat'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->call('saveSlot')
        ->assertHasErrors(['title']);

    expect($user->schedules()->count())->toBe(0);
});

test('schedule page can delete a slot', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'schedule-delete@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'scheduledelete',
        'display_name' => 'Schedule Delete',
    ]);

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Friday chat')
        ->set('selected_days', ['Fri'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call. Be on time.')
        ->call('saveSlot');

    $schedule = $user->schedules()->first();

    Volt::actingAs($user)->test('schedule.index')
        ->call('deleteSchedule', $schedule->id);

    expect($user->schedules()->count())->toBe(0);
});

test('schedule page allows up to the configured slot limit', function () {
    config(['schedules.max_slots_per_user' => 3]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = createScheduleTestUser('schedule-limit@speakloud.test', 'schedulelimit');

    foreach (['First slot', 'Second slot', 'Third slot'] as $title) {
        saveRecurringSlotViaSchedulePage($user, $language, $title);
    }

    expect($user->schedules()->count())->toBe(3);

    Volt::actingAs($user)->test('schedule.index')
        ->assertSee('3 of 3 slots used')
        ->assertSee('You can have up to 3 slots. Delete an existing slot to create a new one.');
});

test('schedule page rejects creating a slot beyond the limit', function () {
    config(['schedules.max_slots_per_user' => 3]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = createScheduleTestUser('schedule-limit-block@speakloud.test', 'schedulelimitblock');

    foreach (['Slot A', 'Slot B', 'Slot C'] as $title) {
        saveRecurringSlotViaSchedulePage($user, $language, $title);
    }

    Volt::actingAs($user)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Fourth slot')
        ->set('selected_days', ['Sun'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->call('saveSlot')
        ->assertHasErrors(['title']);

    expect($user->schedules()->count())->toBe(3);
});

test('schedule page rejects lowering max claims below accepted count', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = createScheduleTestUser('schedule-max-claims@speakloud.test', 'schedulemaxclaims');

    $senderOne = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-max-one@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    $senderTwo = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-max-two@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create(['user_id' => $senderOne->id, 'username' => 'smax1', 'display_name' => 'Sender Max One']);
    UserProfile::create(['user_id' => $senderTwo->id, 'username' => 'smax2', 'display_name' => 'Sender Max Two']);

    Volt::actingAs($host)->test('schedule.index')
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Busy slot')
        ->set('selected_days', ['Sat'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 3)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $schedule = $host->schedules()->first();

    \App\Models\Claim::create([
        'sender_id'   => $senderOne->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    \App\Models\Claim::create([
        'sender_id'   => $senderTwo->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type'        => 'schedule',
        'status'      => 'accepted',
    ]);

    Volt::actingAs($host)->test('schedule.index')
        ->call('editSchedule', $schedule->id)
        ->assertSet('min_max_participants', 2)
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasErrors(['max_participants']);

    expect($schedule->fresh()->max_participants)->toBe(3);
});

test('schedule page allows creating a slot after deleting one at the limit', function () {
    config(['schedules.max_slots_per_user' => 3]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = createScheduleTestUser('schedule-limit-delete@speakloud.test', 'schedulelimitdelete');

    foreach (['Slot A', 'Slot B', 'Slot C'] as $title) {
        saveRecurringSlotViaSchedulePage($user, $language, $title);
    }

    $schedule = $user->schedules()->where('title', 'Slot A')->first();

    Volt::actingAs($user)->test('schedule.index')
        ->call('deleteSchedule', $schedule->id);

    saveRecurringSlotViaSchedulePage($user, $language, 'Replacement slot');

    expect($user->schedules()->count())->toBe(3)
        ->and($user->schedules()->where('title', 'Replacement slot')->exists())->toBeTrue();
});
