<?php

use App\Domain\Dictionary\Actions\ApproveLexeme;
use App\Domain\Dictionary\Actions\CreateLexeme;
use App\Domain\Dictionary\Actions\CreateSense;
use App\Domain\Dictionary\Actions\PublishLexeme;
use App\Domain\Dictionary\Actions\SubmitLexemeForReview;
use App\Domain\Dictionary\Actions\UpdateLexeme;
use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\GrammaticalGender;
use App\Domain\Dictionary\Models\ContentRevision;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Services\ContentPublishingService;
use App\Domain\Dictionary\Services\DictionarySearchService;
use App\Models\Language;
use App\Models\User;
use Database\Seeders\DictionaryReferenceSeeder;
use Database\Seeders\LanguageSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->seed(LanguageSeeder::class);
    $this->seed(DictionaryReferenceSeeder::class);
});

test('editor can create a multi-sense lexeme and reviewer can publish it', function () {
    $editor = User::factory()->editor()->create();
    $reviewer = User::factory()->reviewer()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();
    $a1 = DifficultyLevel::query()->where('code', 'A1')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Fenster',
        'forms' => [
            ['form' => 'Fenster'],
        ],
    ], $editor);

    app(CreateSense::class)->execute($lexeme, [
        'part_of_speech_id' => $noun->id,
        'definition' => 'An opening in a wall with glass.',
        'definition_language_id' => $en->id,
        'difficulty_level_id' => $a1->id,
        'translations' => [
            ['target_language_id' => $en->id, 'translation_text' => 'window'],
        ],
        'examples' => [
            [
                'language_id' => $de->id,
                'sentence' => 'Das Fenster ist offen.',
                'translations' => [
                    ['language_id' => $en->id, 'translation' => 'The window is open.'],
                ],
            ],
        ],
        'german_noun' => [
            'article' => GermanArticle::Das->value,
            'grammatical_gender' => GrammaticalGender::Neuter->value,
            'plural_form' => 'Fenster',
        ],
    ], $editor);

    app(SubmitLexemeForReview::class)->execute($lexeme->fresh(), $editor);
    app(ApproveLexeme::class)->execute($lexeme->fresh(), $reviewer);
    $published = app(PublishLexeme::class)->execute($lexeme->fresh(), $reviewer);

    expect($published->status)->toBe(ContentStatus::Published)
        ->and($published->senses()->first()?->status)->toBe(ContentStatus::Published);
});

test('editor cannot publish content', function () {
    $editor = User::factory()->editor()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Testwort',
    ], $editor);

    $lexeme->update(['status' => ContentStatus::Approved]);

    expect(fn () => app(PublishLexeme::class)->execute($lexeme->fresh(), $editor))
        ->toThrow(ValidationException::class);
});

test('german noun without article cannot be published', function () {
    $reviewer = User::factory()->reviewer()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Unfertig',
    ], $reviewer);

    app(CreateSense::class)->execute($lexeme, [
        'part_of_speech_id' => $noun->id,
        'definition' => 'Incomplete noun.',
        'translations' => [
            ['target_language_id' => $en->id, 'translation_text' => 'incomplete'],
        ],
    ], $reviewer);

    $lexeme->update(['status' => ContentStatus::Approved]);

    expect(fn () => app(PublishLexeme::class)->execute($lexeme->fresh(), $reviewer))
        ->toThrow(ValidationException::class);
});

test('lexeme without senses cannot be published', function () {
    $reviewer = User::factory()->reviewer()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Leer',
    ], $reviewer);

    $lexeme->update(['status' => ContentStatus::Approved]);

    expect(fn () => app(PublishLexeme::class)->execute($lexeme->fresh(), $reviewer))
        ->toThrow(ValidationException::class);
});

test('editing a published lexeme demotes it and creates a revision', function () {
    $reviewer = User::factory()->reviewer()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Tisch',
    ], $reviewer);

    app(CreateSense::class)->execute($lexeme, [
        'part_of_speech_id' => $noun->id,
        'definition' => 'A table.',
        'translations' => [
            ['target_language_id' => $en->id, 'translation_text' => 'table'],
        ],
        'german_noun' => [
            'article' => 'der',
            'grammatical_gender' => 'masculine',
            'plural_form' => 'Tische',
        ],
    ], $reviewer);

    $lexeme->update(['status' => ContentStatus::Approved]);
    $published = app(PublishLexeme::class)->execute($lexeme->fresh(), $reviewer);

    $updated = app(UpdateLexeme::class)->execute($published, [
        'lemma' => 'Tischchen',
    ], $reviewer);

    expect($updated->status)->toBe(ContentStatus::Draft)
        ->and(ContentRevision::query()->where('entity_id', $updated->id)->count())->toBeGreaterThan(0);
});

test('archive cascades child statuses and clears target language cache', function () {
    Cache::flush();

    $reviewer = User::factory()->reviewer()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();
    $en = Language::query()->where('code', 'en')->firstOrFail();
    $fa = Language::query()->where('code', 'fa')->firstOrFail();
    $noun = PartOfSpeech::query()->where('code', 'noun')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Cachewort',
    ], $reviewer);

    app(CreateSense::class)->execute($lexeme, [
        'part_of_speech_id' => $noun->id,
        'definition' => 'A cache test word.',
        'translations' => [
            ['target_language_id' => $en->id, 'translation_text' => 'cacheword'],
            ['target_language_id' => $fa->id, 'translation_text' => 'کلمه'],
        ],
        'german_noun' => [
            'article' => 'das',
            'grammatical_gender' => 'neuter',
            'plural_form' => 'Cacheworter',
        ],
    ], $reviewer);

    $lexeme->update(['status' => ContentStatus::Approved]);
    $published = app(PublishLexeme::class)->execute($lexeme->fresh(), $reviewer);

    $search = app(DictionarySearchService::class);
    expect($search->findPublishedLexeme($published->id, 'fa'))->not->toBeNull();

    $archived = app(ContentPublishingService::class)->archive($published->fresh(), $reviewer);

    expect($archived->status)->toBe(ContentStatus::Archived)
        ->and($archived->senses()->first()?->status)->toBe(ContentStatus::Archived)
        ->and($search->findPublishedLexeme($archived->id, 'fa'))->toBeNull()
        ->and($search->findPublishedLexeme($archived->id, 'en'))->toBeNull();
});

test('soft deleting a lexeme frees its slug for reuse', function () {
    $editor = User::factory()->editor()->create();
    $de = Language::query()->where('code', 'de')->firstOrFail();

    $lexeme = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Slugwort',
        'slug' => 'slugwort-de',
    ], $editor);

    $lexeme->delete();

    $replacement = app(CreateLexeme::class)->execute([
        'language_id' => $de->id,
        'lemma' => 'Slugwort',
        'slug' => 'slugwort-de',
    ], $editor);

    expect($replacement->slug)->toBe('slugwort-de')
        ->and($lexeme->fresh()->slug)->toContain('-deleted-');
});
