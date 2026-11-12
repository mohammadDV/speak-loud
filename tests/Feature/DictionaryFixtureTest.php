<?php

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use Database\Seeders\DictionaryFixtureSeeder;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryFixtureSeeder::class);
});

test('bank fixture has two published senses with distinct translations', function () {
    $bank = Lexeme::query()
        ->where('lemma', 'Bank')
        ->with(['senses.translations.targetLanguage', 'senses.germanNounDetail'])
        ->firstOrFail();

    expect($bank->status)->toBe(ContentStatus::Published)
        ->and($bank->senses)->toHaveCount(2);

    $financial = $bank->senses->firstWhere('definition', 'A financial institution that accepts deposits and makes loans.');
    $bench = $bank->senses->firstWhere('definition', 'A long seat for several people, typically outdoors.');

    expect($financial)->not->toBeNull()
        ->and($bench)->not->toBeNull()
        ->and($financial->germanNounDetail?->article?->value)->toBe('die')
        ->and($financial->germanNounDetail?->plural_form)->toBe('Banken')
        ->and($financial->translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'en')?->translation_text)->toBe('bank')
        ->and($financial->translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'fa')?->translation_text)->toBe('بانک')
        ->and($bench->translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'en')?->translation_text)->toBe('bench')
        ->and($bench->translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'fa')?->translation_text)->toBe('نیمکت');
});

test('gehen fixture resolves inflected forms', function () {
    $gehen = Lexeme::query()->where('lemma', 'gehen')->with('forms')->firstOrFail();

    expect($gehen->forms->pluck('form')->all())->toContain('geht', 'ging', 'gegangen');
});
