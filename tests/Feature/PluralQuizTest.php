<?php

use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\PluralQuizSession;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Domain\Learning\Services\ArticleQuizService;
use App\Domain\Learning\Services\PluralQuizCache;
use App\Domain\Learning\Services\PluralQuizService;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Livewire\Volt\Volt;

/**
 * @param  list<Sense>  $senses
 * @return array<int, string>
 */
function pluralsBySenseId(array $senses): array
{
    $map = [];
    foreach ($senses as $sense) {
        $map[$sense->id] = (string) $sense->germanNounDetail->plural_form;
    }

    return $map;
}

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('guests can view the noun plurals quiz page', function () {
    $this->get(route('learn.plurals'))
        ->assertOk()
        ->assertSee('Noun Plurals')
        ->assertSee('Practice German noun plurals with filters')
        ->assertSee('Show more')
        ->assertDontSee('Set up a quiz')
        ->assertSee('Start quiz');
});

test('feature guide expands when show more is clicked', function () {
    Volt::test('learn.plural')
        ->assertSet('show_feature_guide', false)
        ->assertDontSee('Mark words as Important or Learned')
        ->call('toggleFeatureGuide')
        ->assertSet('show_feature_guide', true)
        ->assertSee('Look up a word')
        ->assertSee('Set up a quiz')
        ->assertSee('Show plurals')
        ->assertSee('Start quiz')
        ->assertSee('Without an account')
        ->assertSee('With an account')
        ->assertSee('Your progress')
        ->assertSee('Show less');
});

test('guest can complete a quiz and see results without persisting a session', function () {
    $senses = createPublishedGermanNouns(5);
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('Show plurals')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->not->toHaveKey('plural_form');
                $answers[(string) $card['sense_id']] = $pluralsBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100)
        ->assertSee('Your score')
        ->assertSee('Sign up to save this result');

    expect(PluralQuizSession::query()->count())->toBe(0);
    expect($senses)->toHaveCount(5);
});

test('authenticated user quiz results are persisted with answers', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $index => $card) {
                $correct = $pluralsBySenseId[(int) $card['sense_id']];
                $answers[(string) $card['sense_id']] = $index === 0
                    ? $correct.'x'
                    : $correct;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 4)
        ->assertSet('incorrect_count', 1);

    $session = PluralQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->word_count)->toBe(5)
        ->and($session->correct_count)->toBe(4)
        ->and($session->answers)->toHaveCount(5);
});

test('persisted quiz scores ignore client claimed correctness and use database plurals', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $correct = $pluralsBySenseId[(int) $card['sense_id']];
                $answers[(string) $card['sense_id']] = $correct.'wrong';
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 0)
        ->assertSet('percentage', 0);

    $session = PluralQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->correct_count)->toBe(0)
        ->and($session->answers->every(fn ($answer) => $answer->is_correct === false))->toBeTrue();
});

test('preview shows plurals and quiz phase uses write-in inputs', function () {
    createPublishedGermanNouns(5);
    $sense = Sense::query()->with('germanNounDetail', 'lexeme')->firstOrFail();
    $lemma = $sense->lexeme->lemma;
    $plural = $sense->germanNounDetail->plural_form;
    $article = strtolower((string) $sense->germanNounDetail->article->value);

    Volt::test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('previewPlurals')
        ->assertSet('phase', 'preview')
        ->assertSee('Preview with plurals')
        ->assertSee($lemma)
        ->assertSee($plural)
        ->assertSeeHtml('data-speak')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSee('Type the plural')
        ->assertDontSee('Preview with plurals')
        ->assertSeeHtml('id="plural-answer-')
        ->assertSeeHtml('data-speak-text="'.e($lemma).'"')
        ->assertDontSeeHtml('data-speak-text="'.e($plural).'"');

    expect($article)->toBeIn(['der', 'die', 'das']);
});

test('answers accept leading articles and german umlaut ascii equivalents', function () {
    $senses = createPublishedGermanNouns(5);
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $correct = $pluralsBySenseId[(int) $card['sense_id']];
                $answers[(string) $card['sense_id']] = 'die '.$correct;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100);
});

test('plural grading requires exact normalized match and rejects missing umlauts', function () {
    $service = app(PluralQuizService::class);

    expect($service->stripLeadingArticle('die Häuser'))->toBe('Häuser')
        ->and($service->stripLeadingArticle('Der Vater'))->toBe('Vater');

    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Haus']);
    $sense = $senses[0];
    $sense->germanNounDetail->update(['plural_form' => 'Häuser']);

    $wrong = $service->gradeAnswers([$sense->id], [$sense->id => 'Hauser'], 'en');
    $ascii = $service->gradeAnswers([$sense->id], [$sense->id => 'Haeuser'], 'en');
    $right = $service->gradeAnswers([$sense->id], [$sense->id => 'die Häuser'], 'en');

    expect($wrong['correct_count'])->toBe(0)
        ->and($ascii['correct_count'])->toBe(1)
        ->and($right['correct_count'])->toBe(1);
});

test('users cannot open another users plural quiz session', function () {
    $owner = User::factory()->create(['email_verified_at' => now()]);
    $intruder = User::factory()->create(['email_verified_at' => now()]);

    $session = PluralQuizSession::factory()->create([
        'user_id' => $owner->id,
        'percentage' => 90,
    ]);

    Volt::actingAs($intruder)
        ->test('learn.plural-history')
        ->call('openSession', $session->id)
        ->assertSet('selected_session_id', null)
        ->assertSet('selected_answers', []);
});

test('submit is rejected until the quiz phase has started', function () {
    $senses = createPublishedGermanNouns(5);
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('previewPlurals')
        ->assertSet('phase', 'preview')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $pluralsBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'preview')
        ->assertSet('show_error_modal', true)
        ->assertSee('Start the quiz before submitting answers');

    expect(PluralQuizSession::query()->count())->toBe(0);
});

test('word lookup is unavailable during the quiz phase', function () {
    createPublishedGermanNouns(5, ['lemma_prefix' => 'QuizWort']);

    Volt::test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('Look up a word')
        ->set('search_query', 'Quiz')
        ->assertSet('show_search_suggestions', false)
        ->assertCount('search_suggestions', 0);
});

test('word count is validated between 5 and 50', function () {
    createPublishedGermanNouns(5);

    Volt::test('learn.plural')
        ->set('word_count', 3)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('show_error_modal', true)
        ->assertSee('Could not continue')
        ->assertSee('Choose between 5 and 50 words', false);
});

test('not enough matching words shows an error popup', function () {
    createPublishedGermanNouns(3, ['level' => 'A1', 'category' => 'family']);

    Volt::test('learn.plural')
        ->set('level_codes', ['A1'])
        ->set('category_codes', ['family'])
        ->set('word_count', 10)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('show_error_modal', true)
        ->assertSee('Could not continue')
        ->assertSee('Not enough German nouns with plurals match these filters');
});

test('level and category filters limit the pool', function () {
    createPublishedGermanNouns(5, ['level' => 'A1', 'category' => 'family']);
    createPublishedGermanNouns(5, ['level' => 'B1', 'category' => 'food', 'lemma_prefix' => 'Essen']);

    $service = app(PluralQuizService::class);

    $familyA1 = $service->queryPool(['A1'], ['family']);
    $foodB1 = $service->queryPool(['B1'], ['food']);

    expect($familyA1)->toHaveCount(5)
        ->and($foodB1)->toHaveCount(5)
        ->and($familyA1)
        ->not->toEqualCanonicalizing($foodB1)
        ->and($familyA1[0])->toBeInt();
});

test('important words are prioritized for authenticated users', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(10);

    $importantIds = collect($senses)->take(3)->pluck('id')->all();

    foreach ($importantIds as $senseId) {
        UserSenseFlag::factory()->important()->forPlural()->create([
            'user_id' => $user->id,
            'sense_id' => $senseId,
        ]);
    }

    $pool = app(PluralQuizService::class)->queryPool();
    $selectedIds = app(ArticleQuizService::class)
        ->selectFromPool($pool, 5, $user, SenseFlagContext::Plural);

    $importantSelected = array_values(array_intersect($importantIds, $selectedIds));
    $expectedQuota = (int) max(1, (int) floor(5 * 0.4));

    expect(count($importantSelected))->toBeGreaterThanOrEqual($expectedQuota)
        ->and(count($importantSelected))->toBeLessThanOrEqual(count($importantIds));
});

test('authenticated user can toggle important and learned flags in plural context', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $senseId = $senses[0]->id;
    $pluralsBySenseId = pluralsBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.plural')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($pluralsBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $pluralsBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->call('toggleFlag', $senseId, 'important')
        ->call('toggleFlag', $senseId, 'learned');

    $flag = UserSenseFlag::query()
        ->where('user_id', $user->id)
        ->where('sense_id', $senseId)
        ->where('context', SenseFlagContext::Plural)
        ->first();

    expect($flag)->not->toBeNull()
        ->and($flag->is_important)->toBeTrue()
        ->and($flag->is_learned)->toBeTrue();
});

test('toggle flag rejects senses that are not published german nouns with plurals', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $sense = Sense::factory()->create();

    expect(fn () => app(ToggleUserSenseFlag::class)->execute($user, $sense->id, 'important', SenseFlagContext::Plural))
        ->toThrow(InvalidArgumentException::class, 'Sense not found.');
});

test('hydrate cards keeps up to five examples per sense', function () {
    $senses = createPublishedGermanNouns(2, ['lemma_prefix' => 'Beispiel']);
    $de = Language::query()->where('code', 'de')->firstOrFail();

    foreach ($senses as $senseIndex => $sense) {
        for ($i = 0; $i < 6; $i++) {
            Example::factory()->published()->create([
                'sense_id' => $sense->id,
                'language_id' => $de->id,
                'sentence' => "Example {$senseIndex}-{$i}",
                'sort_order' => $i,
            ]);
        }
    }

    $cards = app(PluralQuizService::class)->hydrateCards(
        array_map(fn (Sense $sense): int => $sense->id, $senses),
        'en',
    );

    expect($cards)->toHaveCount(2)
        ->and($cards[0]['examples'])->toHaveCount(5)
        ->and($cards[1]['examples'])->toHaveCount(5)
        ->and($cards[0]['examples'][0]['sentence'])->toBe('Example 0-0')
        ->and($cards[1]['examples'][0]['sentence'])->toBe('Example 1-0');
});

test('history page requires authentication and lists saved sessions', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    PluralQuizSession::factory()->create([
        'user_id' => $user->id,
        'word_count' => 10,
        'correct_count' => 8,
        'incorrect_count' => 2,
        'percentage' => 80,
    ]);

    $this->get(route('learn.plurals.history'))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('learn.plurals.history'))
        ->assertOk()
        ->assertSee('Your progress')
        ->assertSee('80%');
});

test('plural quiz pool is cached', function () {
    createPublishedGermanNouns(5);
    Cache::flush();

    $cache = app(PluralQuizCache::class);
    $calls = 0;

    $first = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(PluralQuizService::class)->queryPool(['A1'], ['family']);
    });

    $second = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(PluralQuizService::class)->queryPool(['A1'], ['family']);
    });

    expect($calls)->toBe(1)
        ->and($first)->toEqual($second)
        ->and($first)->toHaveCount(5);

    $cache->forgetPools();

    $third = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(PluralQuizService::class)->queryPool(['A1'], ['family']);
    });

    expect($calls)->toBe(2)
        ->and($third)->toHaveCount(5);
});

test('translation language options only include languages with published translations', function () {
    createPublishedGermanNouns(5);

    $codes = app(PluralQuizService::class)
        ->translationLanguages()
        ->pluck('code')
        ->all();

    expect($codes)->toContain('en')
        ->and($codes)->not->toContain('de')
        ->and($codes)->not->toContain('ja');
});

test('translation language options include spanish persian arabic and turkish when published', function () {
    $senses = createPublishedGermanNouns(5);
    $langs = Language::query()->whereIn('code', ['es', 'fa', 'ar', 'tr'])->get()->keyBy('code');

    foreach ($senses as $sense) {
        foreach (['es' => 'padre', 'fa' => 'پدر', 'ar' => 'أب', 'tr' => 'baba'] as $code => $text) {
            SenseTranslation::factory()->published()->create([
                'sense_id' => $sense->id,
                'target_language_id' => $langs[$code]->id,
                'translation_text' => $text,
            ]);
        }
    }

    app(PluralQuizCache::class)->forgetPools();

    $codes = app(PluralQuizService::class)
        ->translationLanguages()
        ->pluck('code')
        ->all();

    expect($codes)->toContain('en', 'es', 'fa', 'ar', 'tr');
});

test('learn hub and homepage link to noun plurals', function () {
    $this->get(route('learn.index'))
        ->assertOk()
        ->assertSee('Noun Plurals')
        ->assertSee(route('learn.plurals'), false);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Noun Plurals')
        ->assertSee(route('learn.plurals'), false);
});
