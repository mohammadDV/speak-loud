<?php

use App\Domain\Gamification\Services\NextStepRecommendationService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('every authenticated user receives a next step recommendation', function () {
    $user = User::factory()->create();

    $recommendation = app(NextStepRecommendationService::class)->forUser($user);

    expect($recommendation->url)->toContain('/learn/')
        ->and($recommendation->parameters)->toHaveKey('count')
        ->and($recommendation->estimatedMinutes)->toBeGreaterThan(0);
});
