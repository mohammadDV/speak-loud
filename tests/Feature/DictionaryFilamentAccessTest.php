<?php

use App\Domain\Dictionary\Models\Lexeme;
use App\Filament\Admin\Resources\Lexemes\LexemeResource;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

test('editors and reviewers can access the admin panel and dictionary resource', function (string $role) {
    $user = User::factory()->{$role}()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();

    $this->actingAs($user);

    expect(LexemeResource::canViewAny())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(TicketResource::canViewAny())->toBeFalse();
})->with(['editor', 'reviewer']);

test('admins can access dictionary and user resources', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and(LexemeResource::canViewAny())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeTrue();
});

test('moderators can access the admin panel and staff resources but not users', function () {
    $user = User::factory()->create(['role' => 'moderator']);
    $this->actingAs($user);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue()
        ->and(TicketResource::canViewAny())->toBeTrue()
        ->and(UserResource::canViewAny())->toBeFalse()
        ->and(LexemeResource::canViewAny())->toBeFalse();
});

test('regular users cannot access the admin panel', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

test('editors cannot delete lexemes but reviewers can', function () {
    $editor = User::factory()->editor()->create();
    $reviewer = User::factory()->reviewer()->create();

    $this->actingAs($editor);
    expect(LexemeResource::canDelete(new Lexeme))->toBeFalse();

    $this->actingAs($reviewer);
    expect(LexemeResource::canDelete(new Lexeme))->toBeTrue();
});
