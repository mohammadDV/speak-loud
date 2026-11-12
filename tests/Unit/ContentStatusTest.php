<?php

use App\Domain\Dictionary\Enums\ContentStatus;

test('allows the editorial workflow transitions', function () {
    expect(ContentStatus::Draft->canTransitionTo(ContentStatus::PendingReview))->toBeTrue()
        ->and(ContentStatus::PendingReview->canTransitionTo(ContentStatus::Approved))->toBeTrue()
        ->and(ContentStatus::Approved->canTransitionTo(ContentStatus::Published))->toBeTrue()
        ->and(ContentStatus::Published->canTransitionTo(ContentStatus::Archived))->toBeTrue();
});

test('rejects illegal transitions', function () {
    expect(ContentStatus::Draft->canTransitionTo(ContentStatus::Published))->toBeFalse()
        ->and(ContentStatus::Published->canTransitionTo(ContentStatus::Approved))->toBeFalse();
});

test('only published content is publicly visible', function () {
    expect(ContentStatus::Published->isPubliclyVisible())->toBeTrue()
        ->and(ContentStatus::Draft->isPubliclyVisible())->toBeFalse();
});
