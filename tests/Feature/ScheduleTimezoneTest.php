<?php

use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ViewerTimezone;
use Carbon\Carbon;
use Livewire\Livewire;
use Livewire\Volt\Volt;

function timezoneTestUser(string $email, string $username): User
{
    $user = User::factory()->create(['email' => $email]);

    UserProfile::create([
        'user_id' => $user->id,
        'username' => $username,
        'profile_slug' => $username,
        'display_name' => ucfirst($username),
    ]);

    return $user;
}

test('schedule modal shows browser timezone banner without utc field labels', function () {
    $user = timezoneTestUser('tz-banner@speakloudmate.test', 'tzbanner');

    Livewire::withCookie(ViewerTimezone::COOKIE, 'Europe/Berlin');

    Volt::actingAs($user)->test('schedule.index')
        ->call('openModal')
        ->assertSee('Your timezone:')
        ->assertSee('Europe/Berlin')
        ->assertSee('Start')
        ->assertSee('End')
        ->assertDontSee('Start (UTC)')
        ->assertDontSee('End (UTC)');
});

test('creating a recurring slot stores viewer timezone and wall clock', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = timezoneTestUser('tz-recurring@speakloudmate.test', 'tzrecurring');

    Livewire::withCookie(ViewerTimezone::COOKIE, 'Europe/Berlin');

    Volt::actingAs($user)->test('schedule.index')
        ->call('openModal')
        ->set('type', 'recurring')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Berlin evening')
        ->set('selected_days', ['Mon'])
        ->set('start_hour', '18')
        ->set('start_minute', '00')
        ->set('end_hour', '19')
        ->set('end_minute', '00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $rule = $user->schedules()->with('recurringRule')->first()->recurringRule;

    expect($rule->timezone)->toBe('Europe/Berlin')
        ->and(substr((string) $rule->start_time, 0, 5))->toBe('18:00')
        ->and(substr((string) $rule->end_time, 0, 5))->toBe('19:00');
});

test('creating a one-off slot converts local input to utc storage', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = timezoneTestUser('tz-onetime@speakloudmate.test', 'tzonetime');
    $day = now('Europe/Berlin')->addDay()->toDateString();

    Livewire::withCookie(ViewerTimezone::COOKIE, 'Europe/Berlin');

    Volt::actingAs($user)->test('schedule.index')
        ->call('openModal')
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Berlin one-off')
        ->set('slot_date', $day)
        ->set('start_hour', '18')
        ->set('start_minute', '00')
        ->set('end_hour', '19')
        ->set('end_minute', '00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $slot = $user->schedules()->with('oneTimeSlot')->first()->oneTimeSlot;
    $expectedUtc = Carbon::parse($day.' 18:00', 'Europe/Berlin')->utc()->format('Y-m-d H:i');

    expect($slot->start_datetime->timezone('UTC')->format('Y-m-d H:i'))->toBe($expectedUtc);
});

test('discover shows local timezone footer for viewer cookie', function () {
    $this->withUnencryptedCookie(ViewerTimezone::COOKIE, 'Asia/Tokyo')
        ->get(route('discover'))
        ->assertOk()
        ->assertSee('Times shown in your local timezone (Asia/Tokyo).')
        ->assertDontSee('All times are shown in UTC.');
});

test('editing a one-off that crosses midnight in viewer tz preserves utc instants', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = timezoneTestUser('tz-midnight@speakloudmate.test', 'tzmidnight');

    Livewire::withCookie(ViewerTimezone::COOKIE, 'UTC');

    $day = now('UTC')->addDays(2)->toDateString();

    Volt::actingAs($user)->test('schedule.index')
        ->call('openModal')
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Long afternoon')
        ->set('slot_date', $day)
        ->set('start_hour', '08')
        ->set('start_minute', '00')
        ->set('end_hour', '18')
        ->set('end_minute', '00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $schedule = $user->schedules()->with('oneTimeSlot')->first();
    $originalStart = $schedule->oneTimeSlot->start_datetime->timezone('UTC')->format('Y-m-d H:i');
    $originalEnd = $schedule->oneTimeSlot->end_datetime->timezone('UTC')->format('Y-m-d H:i');

    // Pacific/Kiritimati (UTC+14) shows this 08:00–18:00 UTC slot as 22:00–08:00 next day.
    Livewire::withCookie(ViewerTimezone::COOKIE, 'Pacific/Kiritimati');

    Volt::actingAs($user)->test('schedule.index')
        ->call('editSchedule', $schedule->id)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $schedule->refresh()->load('oneTimeSlot');

    expect($schedule->oneTimeSlot->start_datetime->timezone('UTC')->format('Y-m-d H:i'))->toBe($originalStart)
        ->and($schedule->oneTimeSlot->end_datetime->timezone('UTC')->format('Y-m-d H:i'))->toBe($originalEnd);
});

test('creating overnight one-off in viewer timezone is rejected', function () {
    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = timezoneTestUser('tz-overnight@speakloudmate.test', 'tzovernight');
    $day = now('Europe/Berlin')->addDay()->toDateString();

    Livewire::withCookie(ViewerTimezone::COOKIE, 'Europe/Berlin');

    Volt::actingAs($user)->test('schedule.index')
        ->call('openModal')
        ->set('type', 'one_time')
        ->set('language_id', (string) $language->id)
        ->set('title', 'Overnight attempt')
        ->set('slot_date', $day)
        ->set('start_hour', '22')
        ->set('start_minute', '00')
        ->set('end_hour', '06')
        ->set('end_minute', '00')
        ->set('description', 'Video call on Google Meet. 50/50 language split.')
        ->set('max_participants', 1)
        ->call('saveSlot')
        ->assertHasErrors(['end_datetime']);

    expect($user->schedules()->count())->toBe(0);
});
