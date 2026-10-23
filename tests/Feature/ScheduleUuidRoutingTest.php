<?php

use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;

test('schedule show routes use uuid instead of numeric id', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::factory()->create([
        'email' => 'host-uuid-route@speakloud.test',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => 'hostuuidroute',
        'profile_slug' => 'hostuuidroute',
        'display_name' => 'Host UUID Route',
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => 'UUID routing practice slot.',
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

    $url = route('schedules.show', $schedule);

    expect($schedule->uuid)->not->toBeEmpty()
        ->and($url)->toContain($schedule->uuid)
        ->and($url)->not->toContain('/schedules/'.$schedule->id);

    $this->actingAs($host)
        ->get($url)
        ->assertSuccessful();

    $this->actingAs($host)
        ->get('/schedules/'.$schedule->id)
        ->assertNotFound();
});

test('new schedules receive a uuid automatically', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $host = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => 'Auto uuid',
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 1,
        'status' => 'active',
    ]);

    expect($schedule->fresh()->uuid)
        ->toBeString()
        ->toMatch('/^[0-9a-fA-F-]{36}$/');
});
