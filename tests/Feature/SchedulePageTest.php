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

    Volt::test('schedule.index')
        ->actingAs($user)
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

    Volt::test('schedule.index')
        ->actingAs($user)
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('selected_days', ['Sat', 'Sun'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect($user->schedules()->first()->description)->toBe('Video call on Google Meet. 50/50 language split.');
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

    Volt::test('schedule.index')
        ->actingAs($user)
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('selected_days', ['Sat'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Camera on. 50/50 language split.')
        ->call('saveSlot');

    $schedule = $user->schedules()->first();

    Volt::test('schedule.index')
        ->actingAs($user)
        ->call('editSchedule', $schedule->id)
        ->assertSet('editingScheduleId', $schedule->id)
        ->set('selected_days', ['Mon', 'Wed'])
        ->set('start_time', '20:00')
        ->set('end_time', '21:00')
        ->set('description', 'Updated rules: voice call only, beginner-friendly.')
        ->call('saveSlot')
        ->assertHasNoErrors();

    $schedule->refresh();
    expect($schedule->recurringRule->day_of_week)->toBe('Mon,Wed')
        ->and(substr((string) $schedule->recurringRule->start_time, 0, 5))->toBe('20:00')
        ->and($schedule->description)->toBe('Updated rules: voice call only, beginner-friendly.');
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

    Volt::test('schedule.index')
        ->actingAs($user)
        ->set('showModal', true)
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('selected_days', ['Fri'])
        ->set('start_time', '18:00')
        ->set('end_time', '19:00')
        ->set('description', 'Video call. Be on time.')
        ->call('saveSlot');

    $schedule = $user->schedules()->first();

    Volt::test('schedule.index')
        ->actingAs($user)
        ->call('deleteSchedule', $schedule->id);

    expect($user->schedules()->count())->toBe(0);
});
