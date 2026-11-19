<?php

use App\Domain\Learning\Actions\ClearUserSenseFlags;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\PluralQuizSession;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Domain\Learning\Services\UserSenseFlagListService;
use App\Models\User;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('history page shows progress tabs for authenticated users', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    PluralQuizSession::factory()->create([
        'user_id' => $user->id,
        'word_count' => 10,
        'correct_count' => 8,
        'incorrect_count' => 2,
        'percentage' => 80,
    ]);

    $this->actingAs($user)
        ->get(route('learn.plurals.history'))
        ->assertOk()
        ->assertSee('Your progress')
        ->assertSee('History')
        ->assertSee('Important')
        ->assertSee('Learned')
        ->assertSee('80%');
});

test('important and learned tabs list the users flagged nouns with plurals', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(2, ['lemma_prefix' => 'Flag']);

    UserSenseFlag::factory()->important()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->learned()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[1]->id,
    ]);

    $importantLemma = $senses[0]->lexeme->lemma;
    $importantPlural = (string) $senses[0]->germanNounDetail->plural_form;

    Volt::actingAs($user)
        ->test('learn.plural-history')
        ->call('setTab', 'important')
        ->assertSee($importantLemma)
        ->assertSee($importantPlural)
        ->assertDontSee($senses[1]->lexeme->lemma)
        ->assertSeeHtml('data-speak')
        ->assertSeeHtml('data-speak-text="'.e($importantPlural).'"')
        ->assertSeeHtml('aria-label="Hear translation"')
        ->call('setTab', 'learned')
        ->assertSee($senses[1]->lexeme->lemma)
        ->assertDontSee($senses[0]->lexeme->lemma);
});

test('asking to remove does not change flags until confirmed', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Confirm']);
    $senseId = $senses[0]->id;

    UserSenseFlag::factory()->important()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senseId,
    ]);

    Volt::actingAs($user)
        ->test('learn.plural-history')
        ->call('setTab', 'important')
        ->call('askRemoveOne', $senseId)
        ->assertSet('confirm_action', 'remove_one')
        ->assertSet('confirm_sense_id', $senseId)
        ->call('dismissConfirm')
        ->assertSet('confirm_action', null);

    expect(UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senseId)
        ->where('context', SenseFlagContext::Plural)
        ->exists())->toBeTrue()
        ->and(UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('sense_id', $senseId)
            ->where('context', SenseFlagContext::Plural)
            ->value('is_important'))->toBeTrue();
});

test('confirming remove one clears that flag and keeps the other', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Both']);
    $senseId = $senses[0]->id;

    UserSenseFlag::factory()->important()->learned()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senseId,
    ]);

    Volt::actingAs($user)
        ->test('learn.plural-history')
        ->call('setTab', 'important')
        ->call('askRemoveOne', $senseId)
        ->call('confirmAction')
        ->assertDontSee($senses[0]->lexeme->lemma)
        ->call('setTab', 'learned')
        ->assertSee($senses[0]->lexeme->lemma);

    $flag = UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senseId)
        ->where('context', SenseFlagContext::Plural)
        ->first();

    expect($flag)->not->toBeNull()
        ->and($flag->is_important)->toBeFalse()
        ->and($flag->is_learned)->toBeTrue();
});

test('confirming clear all removes every matching plural flag for the user', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(3, ['lemma_prefix' => 'Bulk']);

    foreach ([$senses[0]->id, $senses[1]->id] as $senseId) {
        UserSenseFlag::factory()->important()->forPlural()->create([
            'user_id' => $user->id,
            'sense_id' => $senseId,
        ]);
    }

    UserSenseFlag::factory()->important()->forPlural()->create([
        'user_id' => $other->id,
        'sense_id' => $senses[2]->id,
    ]);

    Volt::actingAs($user)
        ->test('learn.plural-history')
        ->call('setTab', 'important')
        ->assertSee($senses[0]->lexeme->lemma)
        ->call('askClearAll')
        ->assertSet('confirm_action', 'clear_all')
        ->call('confirmAction')
        ->assertSee('No important words yet');

    expect(UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('context', SenseFlagContext::Plural)
        ->where('is_important', true)
        ->count())->toBe(0)
        ->and(UserSenseFlag::query()
            ->where('user_id', $other->id)
            ->where('context', SenseFlagContext::Plural)
            ->where('is_important', true)
            ->count())->toBe(1);
});

test('flag list service returns plural forms for plural context', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(2, ['lemma_prefix' => 'Scope']);

    UserSenseFlag::factory()->important()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->important()->forPlural()->create([
        'user_id' => $other->id,
        'sense_id' => $senses[1]->id,
    ]);

    $words = app(UserSenseFlagListService::class)->important($user, 'en', SenseFlagContext::Plural);

    expect($words)->toHaveCount(1)
        ->and($words[0]['sense_id'])->toBe($senses[0]->id)
        ->and($words[0]['lemma'])->toBe($senses[0]->lexeme->lemma)
        ->and($words[0]['plural_form'])->toBe((string) $senses[0]->germanNounDetail->plural_form)
        ->and($words[0]['article'])->toBeIn(['der', 'die', 'das']);
});

test('clear all action is scoped to the authenticated user and plural context', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $other = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(2, ['lemma_prefix' => 'Action']);

    UserSenseFlag::factory()->learned()->forPlural()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->learned()->forPlural()->create([
        'user_id' => $other->id,
        'sense_id' => $senses[1]->id,
    ]);

    $cleared = app(ClearUserSenseFlags::class)->clearAll($user, 'learned', SenseFlagContext::Plural);

    expect($cleared)->toBe(1)
        ->and(UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('context', SenseFlagContext::Plural)
            ->exists())->toBeFalse()
        ->and(UserSenseFlag::query()
            ->where('user_id', $other->id)
            ->where('context', SenseFlagContext::Plural)
            ->where('is_learned', true)
            ->count())->toBe(1);
});
