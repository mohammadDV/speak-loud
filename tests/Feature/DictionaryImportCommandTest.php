<?php

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\SenseTranslation;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);

    $this->fixtureDir = storage_path('framework/testing/vocabulary');
    File::deleteDirectory($this->fixtureDir);
    File::ensureDirectoryExists($this->fixtureDir);

    File::copy(
        base_path('tests/Fixtures/vocabulary/a1_sample_words.json'),
        $this->fixtureDir.'/a1_sample_words.json',
    );
    File::copy(
        base_path('tests/Fixtures/vocabulary/a2_sample_words.json'),
        $this->fixtureDir.'/a2_sample_words.json',
    );
});

afterEach(function () {
    File::deleteDirectory(storage_path('framework/testing/vocabulary'));
});

test('imports published vocabulary from a json file', function () {
    $this->artisan('dictionary:import', [
        'path' => $this->fixtureDir.'/a1_sample_words.json',
    ])->assertSuccessful();

    $vater = Lexeme::query()
        ->where('import_key', 'de:vater')
        ->with(['categories', 'forms', 'senses.translations', 'senses.examples.translations', 'senses.collocations', 'senses.germanNounDetail', 'senses.levels.difficultyLevel'])
        ->firstOrFail();

    expect($vater->status)->toBe(ContentStatus::Published)
        ->and($vater->lemma)->toBe('Vater')
        ->and($vater->categories->pluck('code')->all())->toBe(['family'])
        ->and($vater->forms->pluck('form')->all())->toContain('Väter')
        ->and($vater->senses)->toHaveCount(1);

    $sense = $vater->senses->first();

    expect($sense->status)->toBe(ContentStatus::Published)
        ->and($sense->import_key)->toBe('de:vater:sense:a1:1')
        ->and($sense->germanNounDetail?->article?->value)->toBe('der')
        ->and($sense->germanNounDetail?->plural_form)->toBe('Väter')
        ->and($sense->levels->first()?->difficultyLevel?->code)->toBe('A1')
        ->and($sense->translations->first()?->translation_text)->toBe('father')
        ->and($sense->examples->first()?->sentence)->toBe('Mein Vater ist hier.')
        ->and($sense->collocations->first()?->phrase)->toBe('mein Vater');
});

test('reimport updates corrected example sentences without duplicating', function () {
    $path = $this->fixtureDir.'/a1_sample_words.json';

    $this->artisan('dictionary:import', ['path' => $path])->assertSuccessful();

    $payload = json_decode(File::get($path), true);
    $payload['lexemes'][0]['senses'][0]['examples'][0]['sentence'] = 'Mein Vater ist nett.';
    $payload['lexemes'][0]['senses'][0]['examples'][0]['translations'][0]['translation'] = 'My father is nice.';
    File::put($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $this->artisan('dictionary:import', ['path' => $path])->assertSuccessful();

    $examples = Example::query()
        ->where('import_key', 'de:vater:sense:a1:1:example:1')
        ->with('translations')
        ->get();

    expect($examples)->toHaveCount(1)
        ->and($examples->first()->sentence)->toBe('Mein Vater ist nett.')
        ->and($examples->first()->translations->first()->translation)->toBe('My father is nice.')
        ->and(Lexeme::query()->where('import_key', 'de:vater')->count())->toBe(1);
});

test('reimport can add a new translation language', function () {
    $path = $this->fixtureDir.'/a1_sample_words.json';

    $this->artisan('dictionary:import', ['path' => $path])->assertSuccessful();

    $payload = json_decode(File::get($path), true);
    $payload['lexemes'][0]['senses'][0]['translations'][] = [
        'language' => 'fa',
        'text' => 'پدر',
        'type' => 'equivalent',
        'status' => 'published',
    ];
    File::put($path, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    $this->artisan('dictionary:import', ['path' => $path])->assertSuccessful();

    $senseId = Lexeme::query()->where('import_key', 'de:vater')->firstOrFail()->senses()->firstOrFail()->id;

    $translations = SenseTranslation::query()
        ->where('sense_id', $senseId)
        ->with('targetLanguage')
        ->get();

    expect($translations)->toHaveCount(2)
        ->and($translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'en')?->translation_text)->toBe('father')
        ->and($translations->firstWhere(fn ($t) => $t->targetLanguage?->code === 'fa')?->translation_text)->toBe('پدر');
});

test('directory import can filter by cefr level', function () {
    $this->artisan('dictionary:import', [
        'path' => $this->fixtureDir,
        '--level' => 'A2',
    ])->assertSuccessful();

    expect(Lexeme::query()->where('import_key', 'de:reise')->exists())->toBeTrue()
        ->and(Lexeme::query()->where('import_key', 'de:vater')->exists())->toBeFalse();
});

test('dry run does not write lexemes', function () {
    $this->artisan('dictionary:import', [
        'path' => $this->fixtureDir.'/a1_sample_words.json',
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(Lexeme::query()->count())->toBe(0);
});
