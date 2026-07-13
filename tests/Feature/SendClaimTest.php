<?php

use App\Actions\SendClaim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ClaimLimits;
use App\Support\ScheduleDescription;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

test('claim limits reads max per hour from config', function () {
    config(['claims.max_per_hour' => 5]);

    expect(ClaimLimits::maxPerHour())->toBe(5);
});

test('claim limits enforces a minimum of one claim per hour', function () {
    config(['claims.max_per_hour' => 0]);

    expect(ClaimLimits::maxPerHour())->toBe(1);
});

test('send claim blocks a fourth claim within one hour', function () {
    config(['claims.max_per_hour' => 3]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $sender = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-limit@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $sender->id,
        'username'     => 'senderlimit',
        'display_name' => 'Sender Limit',
    ]);

    $hosts = collect(range(1, 4))->map(function (int $index) use ($language) {
        $host = User::create([
            'uuid'              => (string) Str::uuid(),
            'email'             => "host-limit-{$index}@speakloud.test",
            'password'          => '123456789',
            'role'              => 'user',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id'      => $host->id,
            'username'     => "hostlimit{$index}",
            'display_name' => "Host Limit {$index}",
        ]);

        $schedule = Schedule::create([
            'user_id'          => $host->id,
            'description'      => ScheduleDescription::EXAMPLES[0],
            'type'             => 'recurring',
            'language_id'      => $language->id,
            'max_participants' => 1,
            'status'           => 'active',
        ]);

        ScheduleRecurringRule::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => 'Mon',
            'start_time'  => '18:00:00',
            'end_time'    => '19:00:00',
        ]);

        return $schedule;
    });

    $action = app(SendClaim::class);

    foreach ($hosts->take(3) as $schedule) {
        $action->execute([
            'sender_id'   => $sender->id,
            'receiver_id' => $schedule->user_id,
            'schedule_id' => $schedule->id,
            'message'     => 'Would love to join!',
        ]);
    }

    expect(fn () => $action->execute([
        'sender_id'   => $sender->id,
        'receiver_id' => $hosts[3]->user_id,
        'schedule_id' => $hosts[3]->id,
        'message'     => 'One more please!',
    ]))->toThrow(function (ValidationException $e) {
        expect($e->errors()['claimMessage'][0])->toContain('You can send up to 3 claims per hour.');
    });
});

test('discover page shows hourly claim limit message on fourth attempt', function () {
    config(['claims.max_per_hour' => 3]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $viewer = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'viewer-limit@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $viewer->id,
        'username'     => 'viewerlimit',
        'display_name' => 'Viewer Limit',
    ]);

    $schedules = collect(range(1, 4))->map(function (int $index) use ($language) {
        $host = User::create([
            'uuid'              => (string) Str::uuid(),
            'email'             => "host-discover-limit-{$index}@speakloud.test",
            'password'          => '123456789',
            'role'              => 'user',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id'      => $host->id,
            'username'     => "hostdiscover{$index}",
            'display_name' => "Host Discover {$index}",
        ]);

        $schedule = Schedule::create([
            'user_id'          => $host->id,
            'description'      => ScheduleDescription::EXAMPLES[0],
            'type'             => 'recurring',
            'language_id'      => $language->id,
            'max_participants' => 1,
            'status'           => 'active',
        ]);

        ScheduleRecurringRule::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => 'Mon',
            'start_time'  => '18:00:00',
            'end_time'    => '19:00:00',
        ]);

        return $schedule;
    });

    foreach ($schedules->take(3) as $schedule) {
        Volt::actingAs($viewer)->test('discover.index')
            ->call('openClaimModal', $schedule->id)
            ->set('claimMessage', 'Interested!')
            ->call('sendClaim')
            ->assertHasNoErrors();
    }

    Volt::actingAs($viewer)->test('discover.index')
        ->call('openClaimModal', $schedules[3]->id)
        ->set('claimMessage', 'One more please!')
        ->call('sendClaim')
        ->assertHasErrors('claimMessage')
        ->assertSet('showClaimModal', true);
});

test('send claim allows another claim after the hourly window expires', function () {
    config([
        'claims.max_per_hour'              => 3,
        'claims.rate_limit_window_seconds' => 3600,
    ]);

    $language = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $sender = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'sender-reset@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $sender->id,
        'username'     => 'senderreset',
        'display_name' => 'Sender Reset',
    ]);

    $hosts = collect(range(1, 4))->map(function (int $index) use ($language) {
        $host = User::create([
            'uuid'              => (string) Str::uuid(),
            'email'             => "host-reset-{$index}@speakloud.test",
            'password'          => '123456789',
            'role'              => 'user',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        UserProfile::create([
            'user_id'      => $host->id,
            'username'     => "hostreset{$index}",
            'display_name' => "Host Reset {$index}",
        ]);

        $schedule = Schedule::create([
            'user_id'          => $host->id,
            'description'      => ScheduleDescription::EXAMPLES[0],
            'type'             => 'recurring',
            'language_id'      => $language->id,
            'max_participants' => 1,
            'status'           => 'active',
        ]);

        ScheduleRecurringRule::create([
            'schedule_id' => $schedule->id,
            'day_of_week' => 'Mon',
            'start_time'  => '18:00:00',
            'end_time'    => '19:00:00',
        ]);

        return $schedule;
    });

    $action = app(SendClaim::class);

    foreach ($hosts->take(3) as $schedule) {
        $action->execute([
            'sender_id'   => $sender->id,
            'receiver_id' => $schedule->user_id,
            'schedule_id' => $schedule->id,
        ]);
    }

    RateLimiter::clear(ClaimLimits::rateLimiterKey($sender->id));

    $claim = $action->execute([
        'sender_id'   => $sender->id,
        'receiver_id' => $hosts[3]->user_id,
        'schedule_id' => $hosts[3]->id,
    ]);

    expect($claim->schedule_id)->toBe($hosts[3]->id);
});
