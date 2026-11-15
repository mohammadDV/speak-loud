<?php

use App\Domain\Learning\Services\ArticleQuizCache;
use App\Domain\Learning\Services\ArticleQuizWordSearchService;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
    RateLimiter::clear('article-quiz-suggest|'.(request()->ip() ?? '127.0.0.1'));
});

test('word search suggestions require at least two characters', function () {
    createPublishedGermanNouns(3, ['lemma_prefix' => 'Haus']);

    $suggestions = app(ArticleQuizWordSearchService::class)->suggest('H', 'en');

    expect($suggestions)->toBe([]);
});

test('word search suggests published german nouns with article and translation', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Fenster']);
    $lemma = $senses[0]->lexeme->lemma;

    $suggestions = app(ArticleQuizWordSearchService::class)->suggest(mb_substr($lemma, 0, 4), 'en');

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0])->toHaveKeys(['sense_id', 'lemma', 'article', 'translation'])
        ->and($suggestions[0]['lemma'])->toBe($lemma)
        ->and($suggestions[0]['article'])->toBeIn(['der', 'die', 'das'])
        ->and($suggestions[0]['translation'])->toBe('word 0');
});

test('word search details return article meaning examples shape', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Tisch']);
    $senseId = $senses[0]->id;

    $detail = app(ArticleQuizWordSearchService::class)->details($senseId, 'en');

    expect($detail)->not->toBeNull()
        ->and($detail['sense_id'])->toBe($senseId)
        ->and($detail['article'])->toBeIn(['der', 'die', 'das'])
        ->and($detail['lemma'])->toBe($senses[0]->lexeme->lemma)
        ->and($detail['translation'])->toBe('word 0')
        ->and($detail)->toHaveKeys(['examples', 'collocations', 'translations', 'plural_form', 'gender']);
});

test('word search suggestions are cached across repeated queries', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Buch']);
    Cache::flush();

    $service = app(ArticleQuizWordSearchService::class);

    $first = $service->suggest('Buch', 'en');
    $second = $service->suggest('Buch', 'en');

    expect($first)->not->toBeEmpty()
        ->and($second)->toEqual($first);

    app(ArticleQuizCache::class)->forgetPools();

    expect($service->suggest('Buch', 'en'))->toEqual($first);
});

test('quiz page search shows suggestions and opens details', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Apfel']);
    $lemma = $senses[0]->lexeme->lemma;
    $prefix = mb_substr($lemma, 0, 3);

    Volt::test('learn.der-die-das')
        ->set('translation_language', 'en')
        ->set('search_query', $prefix)
        ->assertSet('show_search_suggestions', true)
        ->assertCount('search_suggestions', 1)
        ->call('selectSearchSuggestion', $senses[0]->id)
        ->assertSet('details_sense_id', $senses[0]->id)
        ->assertSet('search_detail.lemma', $lemma)
        ->assertSet('show_search_suggestions', false)
        ->assertSee('Translations');
});

test('word search escapes like wildcards in the query', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Haus']);

    $suggestions = app(ArticleQuizWordSearchService::class)->suggest('%%', 'en');

    expect($suggestions)->toBe([]);
});

test('word search suggestions are rate limited', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Limit']);
    RateLimiter::clear('article-quiz-suggest|'.request()->ip());

    $service = app(ArticleQuizWordSearchService::class);

    for ($i = 0; $i < ArticleQuizWordSearchService::SUGGEST_RATE_LIMIT; $i++) {
        $service->suggest('Li', 'en');
    }

    expect(fn () => $service->suggest('Li', 'en'))
        ->toThrow(ValidationException::class);
});
