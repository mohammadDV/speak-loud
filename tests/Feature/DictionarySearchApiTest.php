<?php

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Lexeme;
use Database\Seeders\DictionaryFixtureSeeder;
use Database\Seeders\LanguageSeeder;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryFixtureSeeder::class);
});

test('lists active languages', function () {
    $this->getJson('/api/v1/languages')
        ->assertSuccessful()
        ->assertJsonFragment(['code' => 'de'])
        ->assertJsonFragment(['code' => 'fa']);
});

test('search finds lemma and returns sense-specific persian translations', function () {
    $response = $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de&target_language=fa')
        ->assertSuccessful();

    $lexeme = collect($response->json('data'))->firstWhere('lemma', 'Bank');

    expect($lexeme)->not->toBeNull()
        ->and($lexeme['senses'])->toHaveCount(2);

    $translations = collect($lexeme['senses'])->flatMap(fn (array $sense) => $sense['translations'] ?? []);

    expect($translations->pluck('translation_text')->all())->toContain('بانک', 'نیمکت');
});

test('search finds an inflected verb form', function () {
    $this->getJson('/api/v1/dictionary/search?q=gegangen&source_language=de&target_language=en')
        ->assertSuccessful()
        ->assertJsonFragment(['lemma' => 'gehen']);
});

test('draft lexemes are hidden from public endpoints', function () {
    $bank = Lexeme::query()->where('lemma', 'Bank')->firstOrFail();
    $bank->update(['status' => ContentStatus::Draft]);

    $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de')
        ->assertSuccessful()
        ->assertJsonMissing(['lemma' => 'Bank']);

    $this->getJson('/api/v1/lexemes/'.$bank->id)
        ->assertNotFound();
});

test('sense endpoint returns published sense details', function () {
    $senseId = Lexeme::query()
        ->where('lemma', 'Bank')
        ->with('senses')
        ->firstOrFail()
        ->senses
        ->first()
        ->id;

    $this->getJson('/api/v1/senses/'.$senseId.'?target_language=en')
        ->assertSuccessful()
        ->assertJsonPath('data.grammar.article', 'die')
        ->assertJsonFragment(['translation_text' => 'bank']);
});

test('rejects invalid target_language on lexeme show', function () {
    $bank = Lexeme::query()->where('lemma', 'Bank')->firstOrFail();

    $this->getJson('/api/v1/lexemes/'.$bank->id.'?target_language=not-a-lang')
        ->assertUnprocessable();
});
