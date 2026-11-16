<?php

use App\Domain\Learning\Actions\ClearUserSenseFlags;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Enums\SenseFlagContext;
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

test('meaning important and learned tabs list only meaning-context flags', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(2);
    foreach ($senses as $sense) {
        $sense->load('lexeme');
    }

    UserSenseFlag::factory()->important()->forMeaning()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->learned()->forMeaning()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[1]->id,
    ]);
    UserSenseFlag::factory()->important()->forArticle()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);

    $importantLemma = $senses[0]->lexeme->lemma;
    $learnedLemma = $senses[1]->lexeme->lemma;

    Volt::actingAs($user)
        ->test('learn.vocabulary-meaning-history')
        ->call('setTab', 'important')
        ->assertSee($importantLemma)
        ->assertDontSee($learnedLemma)
        ->call('setTab', 'learned')
        ->assertSee($learnedLemma)
        ->assertDontSee($importantLemma);
});

test('toggling meaning flags does not affect article flags for the same sense', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    foreach ($senses as $sense) {
        $sense->load('translations');
    }

    UserSenseFlag::factory()->important()->learned()->forArticle()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);

    $meanings = [];
    foreach ($senses as $sense) {
        $meanings[$sense->id] = (string) $sense->translations->first()->translation_text;
    }

    Volt::actingAs($user)
        ->test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->set('quiz_type', MeaningQuizType::MultipleChoice->value)
        ->call('startQuiz')
        ->tap(function ($component) use ($meanings) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $meanings[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->call('toggleFlag', $senses[0]->id, 'important')
        ->call('toggleFlag', $senses[0]->id, 'learned');

    $article = UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senses[0]->id)
        ->where('context', SenseFlagContext::Article)
        ->first();

    $meaning = UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senses[0]->id)
        ->where('context', SenseFlagContext::Meaning)
        ->first();

    expect($article)->not->toBeNull()
        ->and($article->is_important)->toBeTrue()
        ->and($article->is_learned)->toBeTrue()
        ->and($meaning)->not->toBeNull()
        ->and($meaning->is_important)->toBeTrue()
        ->and($meaning->is_learned)->toBeTrue();
});

test('clearing meaning important leaves article important intact', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(1);

    UserSenseFlag::factory()->important()->forArticle()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->important()->forMeaning()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);

    $cleared = app(ClearUserSenseFlags::class)->clearAll($user, 'important', SenseFlagContext::Meaning);

    expect($cleared)->toBe(1)
        ->and(UserSenseFlag::query()->where('user_id', $user->id)->where('context', SenseFlagContext::Meaning)->exists())->toBeFalse()
        ->and(UserSenseFlag::query()->where('user_id', $user->id)->where('context', SenseFlagContext::Article)->where('is_important', true)->count())->toBe(1);
});

test('meaning list service ignores article-context flags', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(2);
    foreach ($senses as $sense) {
        $sense->load('lexeme');
    }

    UserSenseFlag::factory()->important()->forArticle()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[0]->id,
    ]);
    UserSenseFlag::factory()->important()->forMeaning()->create([
        'user_id' => $user->id,
        'sense_id' => $senses[1]->id,
    ]);

    $words = app(UserSenseFlagListService::class)->important($user, 'en', SenseFlagContext::Meaning);

    expect($words)->toHaveCount(1)
        ->and($words[0]['sense_id'])->toBe($senses[1]->id)
        ->and($words[0]['lemma'])->toBe($senses[1]->lexeme->lemma);
});

test('toggle meaning flag requires a published translation sense', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(1);

    expect(fn () => app(ToggleUserSenseFlag::class)->execute(
        $user,
        $senses[0]->id,
        'important',
        SenseFlagContext::Meaning,
    ))->not->toThrow(InvalidArgumentException::class);

    $flag = UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senses[0]->id)
        ->where('context', SenseFlagContext::Meaning)
        ->first();

    expect($flag)->not->toBeNull()
        ->and($flag->is_important)->toBeTrue();
});
