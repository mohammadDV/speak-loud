<?php

use App\Actions\DeleteSchedule;
use App\Actions\UpdateSchedule;
use App\Models\Claim;
use App\Models\Language;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Support\ScheduleDescription;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Volt\Volt;

function scheduleAuthLanguage(): Language
{
    return Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);
}

function scheduleAuthUser(string $email, string $username): User
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

function scheduleAuthSchedule(User $host, string $title = 'Host private title'): Schedule
{
    $schedule = Schedule::create([
        'user_id' => $host->id,
        'title' => $title,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => scheduleAuthLanguage()->id,
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

test('user cannot update another users schedule', function () {
    $host = scheduleAuthUser('host-update@speakloud.test', 'hostupdate');
    $attacker = scheduleAuthUser('attacker-update@speakloud.test', 'attackerupdate');
    $schedule = scheduleAuthSchedule($host);

    expect(fn () => app(UpdateSchedule::class)->execute($attacker->id, $schedule->id, [
        'type' => 'recurring',
        'language_id' => $schedule->language_id,
        'title' => 'Hacked',
        'description' => ScheduleDescription::EXAMPLES[0],
        'max_participants' => 2,
        'day_of_week' => 'Mon',
        'start_time' => '18:00',
        'end_time' => '19:00',
    ]))->toThrow(AuthorizationException::class);

    expect($schedule->fresh()->title)->toBe('Host private title');
});

test('user cannot delete another users schedule', function () {
    $host = scheduleAuthUser('host-delete@speakloud.test', 'hostdelete');
    $attacker = scheduleAuthUser('attacker-delete@speakloud.test', 'attackerdelete');
    $schedule = scheduleAuthSchedule($host);

    expect(fn () => app(DeleteSchedule::class)->execute($attacker->id, $schedule->id))
        ->toThrow(AuthorizationException::class);

    expect(Schedule::find($schedule->id))->not->toBeNull();
});

test('outsider schedule show snapshot does not leak claims or host title', function () {
    $host = scheduleAuthUser('host-leak@speakloud.test', 'hostleak');
    $claimer = scheduleAuthUser('claimer-leak@speakloud.test', 'claimerleak');
    $outsider = scheduleAuthUser('outsider-leak@speakloud.test', 'outsiderleak');
    $schedule = scheduleAuthSchedule($host, 'Secret Host Title');

    Claim::create([
        'sender_id' => $claimer->id,
        'receiver_id' => $host->id,
        'schedule_id' => $schedule->id,
        'type' => 'schedule',
        'status' => 'pending',
        'message' => 'Please let me in',
    ]);

    $component = Volt::actingAs($outsider)->test('schedule.show', ['schedule' => $schedule->id]);

    $html = $component->html();

    expect($html)->not->toContain('Secret Host Title')
        ->and($html)->not->toContain('claimerleak')
        ->and($html)->not->toContain('claimer-leak@speakloud.test');

    $payload = $component->get('schedule');
    expect($payload->relationLoaded('claims'))->toBeFalse();
    expect($payload->title)->toBeNull();
});
