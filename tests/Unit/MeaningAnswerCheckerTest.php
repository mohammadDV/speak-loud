<?php

use App\Domain\Learning\Services\MeaningAnswerChecker;

test('exact normalized match is correct', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('  Apple ', ['apple', 'fruit'], 'en');

    expect($result['is_correct'])->toBeTrue()
        ->and($result['match_score'])->toBe(100)
        ->and($result['matched_meaning'])->toBe('apple');
});

test('fuzzy similar answers are accepted for longer glosses', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('aple', ['apple'], 'en');

    expect($result['is_correct'])->toBeTrue()
        ->and($result['match_score'])->toBeGreaterThanOrEqual(MeaningAnswerChecker::MIN_SIMILARITY_PERCENT);
});

test('short glosses require an exact match', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('car', ['cat'], 'en');

    expect($result['is_correct'])->toBeFalse();
});

test('unrelated answers are rejected', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('banana', ['apple'], 'en');

    expect($result['is_correct'])->toBeFalse();
});

test('parenthetical gloss variants are accepted', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('house', ['house (building)'], 'en');

    expect($result['is_correct'])->toBeTrue()
        ->and($result['match_score'])->toBe(100);
});

test('slash separated gloss parts are accepted', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('home', ['house / home'], 'en');

    expect($result['is_correct'])->toBeTrue();
});

test('empty answer is incorrect', function () {
    $checker = new MeaningAnswerChecker;

    $result = $checker->check('   ', ['apple'], 'en');

    expect($result['is_correct'])->toBeFalse()
        ->and($result['match_score'])->toBe(0);
});
