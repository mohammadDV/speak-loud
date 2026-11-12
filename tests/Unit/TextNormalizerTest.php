<?php

use App\Domain\Dictionary\Support\TextNormalizer;

test('normalizes german umlauts and eszett for search', function () {
    expect(TextNormalizer::normalize('Schön', 'de'))->toBe('schoen')
        ->and(TextNormalizer::normalize('Straße', 'de'))->toBe('strasse')
        ->and(TextNormalizer::normalize('Männer', 'de'))->toBe('maenner');
});

test('normalizes persian yeh and kaf variants', function () {
    $arabicYeh = "بانک\u{064A}";
    $persianYeh = "بانک\u{06CC}";

    expect(TextNormalizer::normalize($arabicYeh, 'fa'))
        ->toBe(TextNormalizer::normalize($persianYeh, 'fa'));
});

test('strips arabic diacritics', function () {
    $withMarks = 'كِتَاب';
    $without = 'کتاب';

    expect(TextNormalizer::normalize($withMarks, 'ar'))
        ->toBe(TextNormalizer::normalize($without, 'ar'));
});

test('preserves english text lowercased', function () {
    expect(TextNormalizer::normalize('  Bank  ', 'en'))->toBe('bank');
});

test('normalizes turkish dotted and dotless i', function () {
    expect(TextNormalizer::normalize('Istanbul', 'tr'))->toBe('ıstanbul')
        ->and(TextNormalizer::normalize('İstanbul', 'tr'))->toBe('istanbul');
});
