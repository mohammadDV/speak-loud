<?php

use App\Domain\Learning\Services\MeaningQuizCache;
use App\Domain\Learning\Services\MeaningQuizWordSearchService;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
    $ip = request()->ip() ?? '127.0.0.1';
    RateLimiter::clear('meaning-quiz-suggest|'.$ip);
    RateLimiter::clear('meaning-quiz-details|'.$ip);
});

test('meaning word search suggestions require at least two characters', function () {
    createPublishedGermanNouns(3, ['lemma_prefix' => 'Haus']);

    $suggestions = app(MeaningQuizWordSearchService::class)->suggest('H', 'en');

    expect($suggestions)->toBe([]);
});

test('meaning word search suggests published german words with translation', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Fenster']);
    $lemma = $senses[0]->lexeme->lemma;

    $suggestions = app(MeaningQuizWordSearchService::class)->suggest(mb_substr($lemma, 0, 4), 'en');

    expect($suggestions)->not->toBeEmpty()
        ->and($suggestions[0])->toHaveKeys(['sense_id', 'lemma', 'article', 'translation'])
        ->and($suggestions[0]['lemma'])->toBe($lemma)
        ->and($suggestions[0]['translation'])->toBe('word 0');
});

test('meaning word search details return meaning examples shape', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Tisch']);
    $senseId = $senses[0]->id;

    $detail = app(MeaningQuizWordSearchService::class)->details($senseId, 'en');

    expect($detail)->not->toBeNull()
        ->and($detail['sense_id'])->toBe($senseId)
        ->and($detail['lemma'])->toBe($senses[0]->lexeme->lemma)
        ->and($detail['translation'])->toBe('word 0')
        ->and($detail)->toHaveKeys(['examples', 'collocations', 'translations', 'article']);
});

test('meaning word search suggestions are cached across repeated queries', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Buch']);
    Cache::flush();

    $service = app(MeaningQuizWordSearchService::class);

    $first = $service->suggest('Buch', 'en');
    $second = $service->suggest('Buch', 'en');

    expect($first)->not->toBeEmpty()
        ->and($second)->toEqual($first);

    app(MeaningQuizCache::class)->forgetPools();

    expect($service->suggest('Buch', 'en'))->toEqual($first);
});

test('meaning quiz page search shows suggestions and opens details', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Apfel']);
    $lemma = $senses[0]->lexeme->lemma;
    $prefix = mb_substr($lemma, 0, 3);

    Volt::test('learn.vocabulary-meaning')
        ->set('translation_language', 'en')
        ->set('search_query', $prefix)
        ->assertSet('show_search_suggestions', true)
        ->assertCount('search_suggestions', 1)
        ->call('selectSearchSuggestion', $senses[0]->id)
        ->assertSet('details_sense_id', $senses[0]->id)
        ->assertSet('search_detail.lemma', $lemma)
        ->assertSet('show_search_suggestions', false)
        ->assertSee('Meanings');
});

test('meaning word search escapes like wildcards in the query', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Haus']);

    $suggestions = app(MeaningQuizWordSearchService::class)->suggest('%%', 'en');

    expect($suggestions)->toBe([]);
});

test('meaning word search suggestions are rate limited', function () {
    createPublishedGermanNouns(1, ['lemma_prefix' => 'Limit']);
    RateLimiter::clear('meaning-quiz-suggest|'.request()->ip());

    $service = app(MeaningQuizWordSearchService::class);

    for ($i = 0; $i < MeaningQuizWordSearchService::SUGGEST_RATE_LIMIT; $i++) {
        $service->suggest('Li', 'en');
    }

    expect(fn () => $service->suggest('Li', 'en'))
        ->toThrow(ValidationException::class);
});

test('meaning word search details are rate limited', function () {
    $senses = createPublishedGermanNouns(1, ['lemma_prefix' => 'Detail']);
    RateLimiter::clear('meaning-quiz-details|'.request()->ip());

    $service = app(MeaningQuizWordSearchService::class);

    for ($i = 0; $i < MeaningQuizWordSearchService::DETAILS_RATE_LIMIT; $i++) {
        $service->details($senses[0]->id, 'en');
    }

    expect(fn () => $service->details($senses[0]->id, 'en'))
        ->toThrow(ValidationException::class);
});

test('meaning word search is hidden during quiz phase', function () {
    createPublishedGermanNouns(5, ['lemma_prefix' => 'Quiz']);

    Volt::test('learn.vocabulary-meaning')
        ->set('word_count', 5)
        ->set('translation_language', 'en')
        ->call('startQuiz')
        ->assertSet('phase', 'quiz')
        ->assertDontSee('Look up a word')
        ->set('search_query', 'Qu')
        ->assertSet('show_search_suggestions', false)
        ->assertSet('search_suggestions', []);
});
