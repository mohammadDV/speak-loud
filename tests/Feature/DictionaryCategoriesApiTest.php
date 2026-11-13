<?php

use App\Domain\Dictionary\Actions\CreateLexeme;
use App\Domain\Dictionary\Actions\UpdateLexeme;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Services\DictionaryCache;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\DictionaryFixtureSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryFixtureSeeder::class);
});

test('lists active categories', function () {
    $this->getJson('/api/v1/categories')
        ->assertSuccessful()
        ->assertJsonFragment(['code' => 'family'])
        ->assertJsonFragment(['code' => 'colors'])
        ->assertJsonFragment(['display_name' => 'Time and numbers']);
});

test('bank lexeme has multiple categories', function () {
    $bank = Lexeme::query()
        ->where('lemma', 'Bank')
        ->with('categories')
        ->firstOrFail();

    expect($bank->categories->pluck('code')->sort()->values()->all())
        ->toBe(['home', 'work']);
});

test('lexeme show includes categories', function () {
    $bank = Lexeme::query()->where('lemma', 'Bank')->firstOrFail();

    $this->getJson('/api/v1/lexemes/'.$bank->id)
        ->assertSuccessful()
        ->assertJsonFragment(['code' => 'work'])
        ->assertJsonFragment(['code' => 'home']);
});

test('search can filter by category', function () {
    $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de&category=work')
        ->assertSuccessful()
        ->assertJsonFragment(['lemma' => 'Bank']);

    $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de&category=family')
        ->assertSuccessful()
        ->assertJsonMissing(['lemma' => 'Bank']);
});

test('rejects inactive or unknown category filter', function () {
    Category::query()->where('code', 'colors')->update(['is_active' => false]);

    $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de&category=colors')
        ->assertUnprocessable();

    $this->getJson('/api/v1/dictionary/search?q=Bank&source_language=de&category=not-a-category')
        ->assertUnprocessable();
});

test('deactivating a category clears the categories cache', function () {
    app(DictionaryCache::class)->activeCategories();

    Category::query()->where('code', 'colors')->firstOrFail()->update(['is_active' => false]);

    $codes = app(DictionaryCache::class)->activeCategories()->pluck('code');

    expect($codes)->not->toContain('colors')
        ->and($codes)->toContain('family');
});

test('create and update reject inactive or unknown category ids', function () {
    $editor = User::factory()->editor()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $family = Category::query()->where('code', 'family')->firstOrFail();
    $colors = Category::query()->where('code', 'colors')->firstOrFail();
    $colors->update(['is_active' => false]);

    expect(fn () => app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Testwort',
        'category_ids' => [$colors->id],
    ], $editor))->toThrow(ValidationException::class);

    expect(fn () => app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Testwort',
        'category_ids' => [999_999],
    ], $editor))->toThrow(ValidationException::class);

    expect(fn () => app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Testwort',
        'category_ids' => ['5abc'],
    ], $editor))->toThrow(ValidationException::class);

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Familie',
        'category_ids' => [$family->id],
    ], $editor);

    expect($lexeme->categories()->pluck('categories.id')->all())->toBe([$family->id]);

    expect(fn () => app(UpdateLexeme::class)->execute($lexeme, [
        'category_ids' => [$colors->id],
    ], $editor))->toThrow(ValidationException::class);
});

test('deactivating a category clears cached lexeme payloads', function () {
    $bank = Lexeme::query()->where('lemma', 'Bank')->firstOrFail();
    $home = Category::query()->where('code', 'home')->firstOrFail();

    app(DictionaryCache::class)->rememberPublishedLexeme($bank->id, null, fn () => $bank->fresh(['categories']));

    $home->update(['is_active' => false]);

    $cached = Cache::get(sprintf('dictionary:lexeme:%d:all', $bank->id));

    expect($cached)->toBeNull();
});
