<?php

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\GermanNounDetail;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseLevel;
use App\Domain\Dictionary\Models\SenseTranslation;
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

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

/**
 * @return list<Sense>
 */
function createPublishedGermanNouns(int $count, array $options = []): array
{
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();
    $levelCode = $options['level'] ?? 'A1';
    $categoryCode = $options['category'] ?? 'family';
    $level = DifficultyLevel::query()->where('code', $levelCode)->firstOrFail();
    $category = Category::query()->where('code', $categoryCode)->firstOrFail();

    $articles = [
        GermanArticle::Der,
        GermanArticle::Die,
        GermanArticle::Das,
    ];
    $genders = [
        GrammaticalGender::Masculine,
        GrammaticalGender::Feminine,
        GrammaticalGender::Neuter,
    ];

    $senses = [];

    for ($i = 0; $i < $count; $i++) {
        $article = $articles[$i % 3];
        $gender = $genders[$i % 3];
        $lemma = ($options['lemma_prefix'] ?? 'Wort').$i.fake()->unique()->numerify('###');

        $lexeme = Lexeme::factory()->published()->forLanguage($de)->create([
            'lemma' => $lemma,
        ]);
        $lexeme->categories()->sync([$category->id]);

        $sense = Sense::factory()->published()->create([
            'lexeme_id' => $lexeme->id,
            'part_of_speech_id' => $noun->id,
            'definition' => 'Test noun '.$i,
            'definition_language_id' => $en->id,
        ]);

        GermanNounDetail::factory()->create([
            'sense_id' => $sense->id,
            'article' => $article,
            'grammatical_gender' => $gender,
            'plural_form' => $lemma.'e',
        ]);

        SenseLevel::factory()->create([
            'sense_id' => $sense->id,
            'difficulty_level_id' => $level->id,
            'is_primary' => true,
        ]);

        SenseTranslation::factory()->published()->create([
            'sense_id' => $sense->id,
            'target_language_id' => $en->id,
            'translation_text' => 'word '.$i,
        ]);

        $senses[] = $sense->fresh(['germanNounDetail', 'lexeme']);
    }

    return $senses;
}

test('guests can view the der die das quiz page', function () {
    $this->get(route('learn.der-die-das'))
        ->assertOk()
        ->assertSee('Der / Die / Das')
        ->assertSee('Start quiz');
});

test('guest can complete a quiz and see results without persisting a session', function () {
    $senses = createPublishedGermanNouns(5);

    Volt::test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('Show articles')
        ->tap(function ($component) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $card['article'];
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
    createPublishedGermanNouns(5);

    Volt::actingAs($user)
        ->test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) {
            $answers = [];
            foreach ($component->get('cards') as $index => $card) {
                $answers[(string) $card['sense_id']] = $index === 0
                    ? ($card['article'] === 'der' ? 'die' : 'der')
                    : $card['article'];
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
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertSee('Choose the article')
        ->assertDontSee('Preview with articles')
        ->assertSee('der')
        ->assertSee('die')
        ->assertSee('das')
        ->assertDontSeeHtml('<span class="text-[#FF8C42]">'.$article.'</span>');
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
        ->and(array_column($familyA1, 'sense_id'))
        ->not->toEqualCanonicalizing(array_column($foodB1, 'sense_id'));
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
    $selected = app(ArticleQuizService::class)->selectFromPool($pool, 5, $user);
    $selectedIds = array_column($selected, 'sense_id');

    $importantSelected = array_values(array_intersect($importantIds, $selectedIds));
    $expectedQuota = (int) max(1, (int) floor(5 * 0.4));

    expect(count($importantSelected))->toBeGreaterThanOrEqual($expectedQuota)
        ->and(count($importantSelected))->toBeLessThanOrEqual(count($importantIds));
});

test('authenticated user can toggle important and learned flags', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $senses = createPublishedGermanNouns(5);
    $senseId = $senses[0]->id;

    Volt::actingAs($user)
        ->test('learn.der-die-das')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->tap(function ($component) {
            $answers = [];
            foreach ($component->get('cards') as $card) {
                $answers[(string) $card['sense_id']] = $card['article'];
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
        ->assertSee('Quiz history')
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
