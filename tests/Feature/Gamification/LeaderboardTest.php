<?php

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Services\LeaderboardService;
use App\Domain\Gamification\Support\CompetitionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('leaderboard ranks users and excludes soft deleted and hidden users', function () {
    $visible = User::factory()->create();
    $hidden = User::factory()->create();
    $deleted = User::factory()->create();

    foreach ([$visible, $hidden, $deleted] as $user) {
        UserGamificationProfile::factory()->create([
            'user_id' => $user->id,
            'show_on_leaderboard' => $user->is($hidden) ? false : true,
        ]);
        XpTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => $user->is($visible) ? 100 : 50,
            'reason' => XpReason::CorrectAnswers,
            'created_at' => now(),
            'idempotency_key' => 'v1:'.$user->id.':article_quiz:'.$user->id.':correct_answers',
        ]);
    }

    $deleted->delete();

    $board = app(LeaderboardService::class)->forPeriod(CompetitionPeriod::forDate(), $visible, 10);

    expect($board['participants'])->toBe(1)
        ->and($board['entries'][0])->not->toHaveKey('user_id')
        ->and($board['entries'][0]['display_name'])->not->toBeEmpty()
        ->and($board['viewer']['rank'])->toBe(1)
        ->and($board['period'])->toHaveKeys(['start_utc', 'end_utc', 'start_local', 'timezone'])
        ->and($board['period']['start_utc'])->toBeString();
});

test('learn index dehydrates dashboard leaderboard without CompetitionPeriod objects', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->create([
        'user_id' => $user->id,
        'show_on_leaderboard' => true,
    ]);

    $this->actingAs($user);

    Volt::test('learn.index')
        ->assertOk()
        ->assertSet('dashboard.leaderboard.period.timezone', (string) config('gamification.competition_timezone', 'Europe/Berlin'));
});

test('leaderboard page renders podium hero and viewer standing', function () {
    $leader = User::factory()->create();
    $second = User::factory()->create();

    UserGamificationProfile::factory()->create([
        'user_id' => $leader->id,
        'show_on_leaderboard' => true,
        'leaderboard_display_name' => 'Apex',
    ]);
    UserGamificationProfile::factory()->create([
        'user_id' => $second->id,
        'show_on_leaderboard' => true,
        'leaderboard_display_name' => 'Runner',
    ]);

    XpTransaction::factory()->create([
        'user_id' => $leader->id,
        'amount' => 120,
        'reason' => XpReason::CorrectAnswers,
        'created_at' => now(),
        'idempotency_key' => 'v1:'.$leader->id.':article_quiz:ui:correct_answers',
    ]);
    XpTransaction::factory()->create([
        'user_id' => $second->id,
        'amount' => 80,
        'reason' => XpReason::CorrectAnswers,
        'created_at' => now(),
        'idempotency_key' => 'v1:'.$second->id.':article_quiz:ui:correct_answers',
    ]);

    $this->actingAs($leader);

    Volt::test('learn.leaderboard')
        ->assertOk()
        ->assertSee('This week')
        ->assertSee('Your standing')
        ->assertSee('Apex')
        ->assertSee('Runner')
        ->assertSee('120');
});
