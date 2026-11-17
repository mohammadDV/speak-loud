<?php

use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

test('learn dashboard shows segmented nav and streak for authenticated users', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->create([
        'user_id' => $user->id,
        'total_xp' => 40,
        'current_streak' => 2,
        'daily_goal_chosen_at' => now(),
    ]);

    $this->actingAs($user);

    Volt::test('learn.index')
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Leaderboard')
        ->assertSee('Progress')
        ->assertSee('day streak')
        ->assertDontSee('Set a daily XP goal');
});

test('learn dashboard prompts for daily goal when not chosen yet', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->needsGoalSetup()->create([
        'user_id' => $user->id,
    ]);

    $this->actingAs($user);

    Volt::test('learn.index')
        ->assertOk()
        ->assertSee('Set a daily XP goal')
        ->call('chooseGoal', 20)
        ->assertSet('needs_goal_setup', false);

    expect(UserGamificationProfile::query()->where('user_id', $user->id)->value('daily_goal'))->toBe(20)
        ->and(UserGamificationProfile::query()->where('user_id', $user->id)->value('daily_goal_chosen_at'))->not->toBeNull();
});

test('progress page links to activity and settings', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('learn.progress'))
        ->assertOk()
        ->assertSee('Activity & XP', false)
        ->assertSee('Goals & settings', false)
        ->assertSee(route('learn.activity'), false)
        ->assertSee(route('learn.settings'), false);
});

test('settings flow uses timezone select and can save preferences', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->create([
        'user_id' => $user->id,
        'timezone' => 'Europe/Berlin',
    ]);

    $this->actingAs($user);

    Volt::test('learn.settings')
        ->assertOk()
        ->assertSee('Daily goal')
        ->set('daily_goal', 30)
        ->call('saveGoalAndContinue')
        ->assertSet('step', 2)
        ->assertSee('Timezone')
        ->set('timezone', 'Asia/Tokyo')
        ->set('show_on_leaderboard', true)
        ->set('leaderboard_display_name', 'Hamed')
        ->call('saveSettings');

    $profile = UserGamificationProfile::query()->where('user_id', $user->id)->first();

    expect($profile->daily_goal)->toBe(30)
        ->and($profile->timezone)->toBe('Asia/Tokyo')
        ->and($profile->show_on_leaderboard)->toBeTrue()
        ->and($profile->leaderboard_display_name)->toBe('Hamed');
});

test('activity page shows daily activity and xp history headings', function () {
    $user = User::factory()->create();
    UserGamificationProfile::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('learn.activity'))
        ->assertOk()
        ->assertSee('Recent daily activity')
        ->assertSee('XP history');
});
