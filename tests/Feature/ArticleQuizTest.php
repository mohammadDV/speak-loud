<?php

use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Learning\Actions\ToggleUserSenseFlag;
use App\Domain\Learning\Models\ArticleQuizSession;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Domain\Learning\Services\ArticleQuizCache;
use App\Domain\Learning\Services\ArticleQuizService;
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
function articlesBySenseId(array $senses): array
{
    $map = [];
    foreach ($senses as $sense) {
        $map[$sense->id] = strtolower((string) $sense->germanNounDetail->article->value);
    }

    return $map;
}

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('guests can view the der die das quiz page', function () {
    $this->get(route('learn.der-die-das'))
        ->assertOk()
        ->assertSee('Der / Die / Das')
        ->assertSee('Practice German noun articles with filters')
        ->assertSee('Show more')
        ->assertDontSee('Set up a quiz')
        ->assertSee('Start quiz');
});

test('feature guide expands when show more is clicked', function () {
    Volt::test('learn.der-die-das')
        ->assertSet('show_feature_guide', false)
        ->assertDontSee('Mark words as Important or Learned')
        ->call('toggleFeatureGuide')
        ->assertSet('show_feature_guide', true)
        ->assertSee('Look up a word')
        ->assertSee('Set up a quiz')
        ->assertSee('Show articles')
        ->assertSee('Start quiz')
        ->assertSee('Without an account')
        ->assertSee('With an account')
        ->assertSee('Your progress')
        ->assertSee('Show less');
});

test('guest can complete a quiz and see results without persisting a session', function () {
    $senses = createPublishedGermanNouns(5);
    $articlesBySenseId = articlesBySenseId($senses);

    Volt::test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('Show articles')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                expect($card)->not->toHaveKey('article');
                $answers[(string) $card['sense_id']] = $articlesBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('percentage', 100)
        ->assertSee('Your score')
        ->assertSee('Sign up to save this result');

    expect(ArticleQuizSession::query()->count())->toBe(0);
    expect($senses)->toHaveCount(5);
});

test('authenticated user quiz results are persisted with answers', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $articlesBySenseId = articlesBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $index => $card) {
                $correct = $articlesBySenseId[(int) $card['sense_id']];
                $answers[(string) $card['sense_id']] = $index === 0
                    ? ($correct === 'der' ? 'die' : 'der')
                    : $correct;
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 4)
        ->assertSet('incorrect_count', 1);

    $session = ArticleQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->word_count)->toBe(5)
        ->and($session->correct_count)->toBe(4)
        ->and($session->answers)->toHaveCount(5);
});

test('persisted quiz scores ignore client claimed correctness and use database articles', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $articlesBySenseId = articlesBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $correct = $articlesBySenseId[(int) $card['sense_id']];
                $answers[(string) $card['sense_id']] = $correct === 'der' ? 'die' : 'der';
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSet('correct_count', 0)
        ->assertSet('percentage', 0);

    $session = ArticleQuizSession::query()->where('user_id', $user->id)->first();

    expect($session)->not->toBeNull()
        ->and($session->correct_count)->toBe(0)
        ->and($session->answers->every(fn ($answer) => $answer->is_correct === false))->toBeTrue();
});

test('preview shows articles and quiz phase only shows radio choices in html', function () {
    createPublishedGermanNouns(5);
    $sense = Sense::query()->with('germanNounDetail', 'lexeme')->firstOrFail();
    $lemma = $sense->lexeme->lemma;
    $article = $sense->germanNounDetail->article->value;

    Volt::test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('previewArticles')
        ->assertSet('phase', 'preview')
        ->assertSee('Preview with articles')
        ->assertSee($lemma)
        ->assertSeeHtml('<span class="text-[#FF8C42]">'.$article.'</span>')
        ->assertSeeHtml('data-speak')
        ->assertSeeHtml('data-speak-text="'.e(trim($article.' '.$lemma)).'"')
        ->assertSeeHtml('aria-label="Hear pronunciation"')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSee('Choose the article')
        ->assertDontSee('Preview with articles')
        ->assertSee('der')
        ->assertSee('die')
        ->assertSee('das')
        ->assertDontSeeHtml('<span class="text-[#FF8C42]">'.$article.'</span>')
        ->assertSeeHtml('data-speak-text="'.e($lemma).'"')
        ->assertDontSeeHtml('data-speak-text="'.e(trim($article.' '.$lemma)).'"');
});

test('results show speak controls for german words and visible translations', function () {
    $senses = createPublishedGermanNouns(5);
    $articlesBySenseId = articlesBySenseId($senses);
    $lemma = $senses[0]->lexeme->lemma;
    $article = $articlesBySenseId[$senses[0]->id];

    Volt::test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $articlesBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'results')
        ->assertSeeHtml('data-speak-text="'.e(trim($article.' '.$lemma)).'"')
        ->call('toggleTranslation', $senses[0]->id)
        ->assertSeeHtml('aria-label="Hear translation"')
        ->assertSeeHtml('data-speak-text="word 0"');
});

test('submit is rejected until the quiz phase has started', function () {
    $senses = createPublishedGermanNouns(5);
    $articlesBySenseId = articlesBySenseId($senses);

    Volt::test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('previewArticles')
        ->assertSet('phase', 'preview')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $articlesBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->assertSet('phase', 'preview')
        ->assertSet('show_error_modal', true)
        ->assertSee('Start the quiz before submitting answers');

    expect(ArticleQuizSession::query()->count())->toBe(0);
});

test('word lookup is unavailable during the quiz phase', function () {
    createPublishedGermanNouns(5, ['lemma_prefix' => 'QuizWort']);

    Volt::test('learn.der-die-das')
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

    Volt::test('learn.der-die-das')
        ->set('word_count', 3)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('show_error_modal', true)
        ->assertSee('Could not continue')
        ->assertSee('Choose between 5 and 50 words', false);
});

test('not enough matching words shows an error popup', function () {
    createPublishedGermanNouns(3, ['level' => 'A1', 'category' => 'family']);

    Volt::test('learn.der-die-das')
        ->set('level_codes', ['A1'])
        ->set('category_codes', ['family'])
        ->set('word_count', 10)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('show_error_modal', true)
        ->assertSee('Could not continue')
        ->assertSee('Not enough German nouns match these filters');
});

test('level and category filters limit the pool', function () {
    createPublishedGermanNouns(5, ['level' => 'A1', 'category' => 'family']);
    createPublishedGermanNouns(5, ['level' => 'B1', 'category' => 'food', 'lemma_prefix' => 'Essen']);

    $service = app(ArticleQuizService::class);

    $familyA1 = $service->queryPool(['A1'], ['family']);
    $foodB1 = $service->queryPool(['B1'], ['food']);

    expect($familyA1)->toHaveCount(5)
        ->and($foodB1)->toHaveCount(5)
        ->and($familyA1)
        ->not->toEqualCanonicalizing($foodB1)
        ->and($familyA1[0])->toBeInt();
});

test('multiple levels and categories can be selected together', function () {
    createPublishedGermanNouns(3, ['level' => 'A1', 'category' => 'family']);
    createPublishedGermanNouns(3, ['level' => 'A2', 'category' => 'food', 'lemma_prefix' => 'Essen']);
    createPublishedGermanNouns(3, ['level' => 'B1', 'category' => 'travel', 'lemma_prefix' => 'Reise']);

    $service = app(ArticleQuizService::class);
    $pool = $service->queryPool(['A1', 'A2'], ['family', 'food']);

    expect($pool)->toHaveCount(6);

    Volt::test('learn.der-die-das')
        ->set('level_codes', ['A1', 'A2'])
        ->set('category_codes', ['family', 'food'])
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertCount('cards', 5);
});

test('important words are prioritized for authenticated users', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(10);

    $importantIds = collect($senses)->take(3)->pluck('id')->all();

    foreach ($importantIds as $senseId) {
        UserSenseFlag::factory()->important()->create([
            'user_id' => $user->id,
            'sense_id' => $senseId,
        ]);
    }

    $pool = app(ArticleQuizService::class)->queryPool();
    $selectedIds = app(ArticleQuizService::class)->selectFromPool($pool, 5, $user);

    $importantSelected = array_values(array_intersect($importantIds, $selectedIds));
    $expectedQuota = (int) max(1, (int) floor(5 * 0.4));

    expect(count($importantSelected))->toBeGreaterThanOrEqual($expectedQuota)
        ->and(count($importantSelected))->toBeLessThanOrEqual(count($importantIds));
});

test('authenticated user can toggle important and learned flags', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $senseId = $senses[0]->id;
    $articlesBySenseId = articlesBySenseId($senses);

    Volt::actingAs($user)
        ->test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) use ($articlesBySenseId) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $articlesBySenseId[(int) $card['sense_id']];
            }
            $component->set('answers', $answers);
        })
        ->call('submitQuiz')
        ->call('toggleFlag', $senseId, 'important')
        ->call('toggleFlag', $senseId, 'learned');

    $flag = UserSenseFlag::query()->where('user_id', $user->id)->where('sense_id', $senseId)->first();

    expect($flag)->not->toBeNull()
        ->and($flag->is_important)->toBeTrue()
        ->and($flag->is_learned)->toBeTrue();
});

test('toggle flag rejects senses that are not published german nouns', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $sense = Sense::factory()->create();

    expect(fn () => app(ToggleUserSenseFlag::class)->execute($user, $sense->id, 'important'))
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

    $cards = app(ArticleQuizService::class)->hydrateCards(
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

    ArticleQuizSession::factory()->create([
        'user_id' => $user->id,
        'word_count' => 10,
        'correct_count' => 8,
        'incorrect_count' => 2,
        'percentage' => 80,
    ]);

    $this->get(route('learn.der-die-das.history'))
        ->assertRedirect();

    $this->actingAs($user)
        ->get(route('learn.der-die-das.history'))
        ->assertOk()
        ->assertSee('Your progress')
        ->assertSee('80%');
});

test('article quiz pool is cached in redis-compatible cache store', function () {
    createPublishedGermanNouns(5);
    Cache::flush();

    $cache = app(ArticleQuizCache::class);
    $calls = 0;

    $first = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(ArticleQuizService::class)->queryPool(['A1'], ['family']);
    });

    $second = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(ArticleQuizService::class)->queryPool(['A1'], ['family']);
    });

    expect($calls)->toBe(1)
        ->and($first)->toEqual($second)
        ->and($first)->toHaveCount(5);

    $cache->forgetPools();

    $third = $cache->rememberPool(['A1'], ['family'], function () use (&$calls) {
        $calls++;

        return app(ArticleQuizService::class)->queryPool(['A1'], ['family']);
    });

    expect($calls)->toBe(2)
        ->and($third)->toHaveCount(5);
});

test('translation language options only include languages with published translations', function () {
    createPublishedGermanNouns(5);

    $codes = app(ArticleQuizService::class)
        ->translationLanguages()
        ->pluck('code')
        ->all();

    expect($codes)->toContain('en')
        ->and($codes)->not->toContain('de')
        ->and($codes)->not->toContain('ja');
});

test('learn link is present on public pages', function () {
    $this->get(route('discover'))
        ->assertOk()
        ->assertSee(route('learn.der-die-das'), false);
});
