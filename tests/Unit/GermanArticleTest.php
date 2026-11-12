<?php

use App\Domain\Dictionary\Enums\GermanArticle;

test('accepts der die and das', function (string $article) {
    expect(GermanArticle::isValid($article))->toBeTrue();
})->with(['der', 'die', 'das', 'DER', 'Die']);

test('rejects invalid german articles', function (?string $article) {
    expect(GermanArticle::isValid($article))->toBeFalse();
})->with([null, '', 'den', 'ein', 'le']);
