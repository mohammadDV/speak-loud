<?php

use App\Domain\Gamification\Actions\AdjustUserXp;
use App\Domain\Gamification\Exceptions\InsufficientXpException;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Services\XpCalculationService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('xp calculation resolves milestone and perfect rewards', function () {
    $service = app(XpCalculationService::class);

    $rewards = $service->calculateQuizRewards('article_quiz', 20, 20, 300);

    $amounts = collect($rewards)->mapWithKeys(
        fn (array $row) => [$row['reason']->value => $row['amount']]
    );

    expect($amounts['correct_answers'])->toBe(40)
        ->and($amounts['quiz_completion'])->toBe(5)
        ->and($amounts['question_milestone'])->toBe(10)
        ->and($amounts['perfect_quiz'])->toBe(5);
});

test('administrative adjustment cannot make total xp negative', function () {
    $user = User::factory()->create();
    $actor = User::factory()->admin()->create();
    UserGamificationProfile::factory()->create([
        'user_id' => $user->id,
        'total_xp' => 5,
    ]);

    expect(fn () => app(AdjustUserXp::class)->execute($user, -10, $actor, 'oops'))
        ->toThrow(InsufficientXpException::class);
});

test('non admin cannot adjust xp', function () {
    $user = User::factory()->create();
    $actor = User::factory()->create();
    UserGamificationProfile::factory()->create([
        'user_id' => $user->id,
        'total_xp' => 5,
    ]);

    expect(fn () => app(AdjustUserXp::class)->execute($user, 10, $actor, 'nope'))
        ->toThrow(AuthorizationException::class);
});
