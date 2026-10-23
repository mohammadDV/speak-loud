<?php

use App\Models\Claim;
use App\Models\Conversation;
use App\Models\Language;
use App\Models\Message;
use App\Models\Schedule;
use App\Models\ScheduleRecurringRule;
use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IMessageRepository;
use App\Repositories\Contracts\IScheduleRepository;
use App\Support\LookupCache;
use App\Support\ScheduleDescription;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function createActiveHostWithSchedule(string $suffix, Language $language): array
{
    $host = User::create([
        'uuid' => (string) Str::uuid(),
        'email' => "host-{$suffix}@speakloud.test",
        'password' => '123456789',
        'role' => 'user',
        'status' => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id' => $host->id,
        'username' => "host{$suffix}",
        'display_name' => "Host {$suffix}",
    ]);

    $schedule = Schedule::create([
        'user_id' => $host->id,
        'description' => ScheduleDescription::EXAMPLES[0],
        'type' => 'recurring',
        'language_id' => $language->id,
        'max_participants' => 3,
        'status' => 'active',
    ]);

    ScheduleRecurringRule::create([
        'schedule_id' => $schedule->id,
        'day_of_week' => 'Wed',
        'start_time' => '18:00:00',
        'end_time' => '19:00:00',
    ]);

    return [$host, $schedule];
}

test('discover search uses simple pagination without a full count query', function () {
    $language = Language::create([
        'code' => 'en',
        'name_en' => 'English',
        'name_native' => 'English',
        'is_active' => true,
    ]);

    for ($i = 1; $i <= 5; $i++) {
        createActiveHostWithSchedule("disc{$i}", $language);
    }

    $viewer = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
    UserProfile::create([
        'user_id' => $viewer->id,
        'username' => 'viewerdisc',
        'display_name' => 'Viewer',
    ]);

    $paginator = app(IScheduleRepository::class)->searchOpenSchedules([
        'page' => 1,
    ], $viewer->id);

    expect($paginator)->toBeInstanceOf(Paginator::class)
        ->and($paginator)->not->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->items())->not->toBeEmpty()
        ->and($paginator->hasMorePages())->toBeBool();
});

test('claims list loads partner conversations in a bounded number of queries', function () {
    $language = Language::create([
        'code' => 'en',
        'name_en' => 'English',
        'name_native' => 'English',
        'is_active' => true,
    ]);

    $receiver = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
    UserProfile::create([
        'user_id' => $receiver->id,
        'username' => 'receiverclaims',
        'display_name' => 'Receiver',
    ]);

    for ($i = 1; $i <= 8; $i++) {
        [$host, $schedule] = createActiveHostWithSchedule("claim{$i}", $language);

        Claim::create([
            'sender_id' => $host->id,
            'receiver_id' => $receiver->id,
            'schedule_id' => $schedule->id,
            'type' => 'schedule',
            'status' => 'pending',
        ]);

        Conversation::create([
            'type' => 'direct',
            'user_a_id' => min($host->id, $receiver->id),
            'user_b_id' => max($host->id, $receiver->id),
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $claims = app(IClaimRepository::class)->incomingForUser($receiver->id);

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($claims)->toHaveCount(8)
        ->and($claims->every(fn (Claim $claim) => $claim->relationLoaded('conversation')))->toBeTrue()
        ->and($queryCount)->toBeLessThan(20);
});

test('conversation messages are capped and eager load sender profiles', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    UserProfile::create([
        'user_id' => $alice->id,
        'username' => 'aliceperf',
        'display_name' => 'Alice',
    ]);
    UserProfile::create([
        'user_id' => $bob->id,
        'username' => 'bobperf',
        'display_name' => 'Bob',
    ]);

    $conversation = Conversation::create([
        'type' => 'direct',
        'user_a_id' => min($alice->id, $bob->id),
        'user_b_id' => max($alice->id, $bob->id),
    ]);

    for ($i = 1; $i <= 60; $i++) {
        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $i % 2 === 0 ? $alice->id : $bob->id,
            'body' => "Message {$i}",
            'type' => 'text',
        ]);
    }

    $messages = app(IMessageRepository::class)->forConversation($conversation->id);

    expect($messages)->toHaveCount(50)
        ->and($messages->first()->body)->toBe('Message 11')
        ->and($messages->last()->body)->toBe('Message 60')
        ->and($messages->every(fn (Message $message) => $message->relationLoaded('sender')))->toBeTrue()
        ->and($messages->every(fn (Message $message) => $message->sender->relationLoaded('profile')))->toBeTrue();
});

test('home open slots cache stores ids and returns schedule models', function () {
    $language = Language::create([
        'code' => 'en',
        'name_en' => 'English',
        'name_native' => 'English',
        'is_active' => true,
    ]);

    [, $schedule] = createActiveHostWithSchedule('home1', $language);
    LookupCache::flushHomeOpenSlots();

    $first = LookupCache::homeOpenSlots(4);
    $second = LookupCache::homeOpenSlots(4);

    expect($first)->not->toBeEmpty()
        ->and($first->first())->toBeInstanceOf(Schedule::class)
        ->and($first->pluck('id')->all())->toContain($schedule->id)
        ->and($second->pluck('id')->all())->toBe($first->pluck('id')->all())
        ->and($second->every(fn ($slot) => $slot instanceof Schedule))->toBeTrue();
});
