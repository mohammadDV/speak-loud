<?php

use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

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
