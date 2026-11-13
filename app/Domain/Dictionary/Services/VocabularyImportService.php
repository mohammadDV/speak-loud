<?php

namespace App\Domain\Dictionary\Services;

use App\Domain\Dictionary\Enums\ContentStatus;
use App\Domain\Dictionary\Enums\FormType;
use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Dictionary\Enums\PhraseType;
use App\Domain\Dictionary\Enums\TranslationType;
use App\Domain\Dictionary\Models\Category;
use App\Domain\Dictionary\Models\Collocation;
use App\Domain\Dictionary\Models\CollocationTranslation;
use App\Domain\Dictionary\Models\DifficultyLevel;
use App\Domain\Dictionary\Models\Example;
use App\Domain\Dictionary\Models\ExampleTranslation;
use App\Domain\Dictionary\Models\GermanNounDetail;
use App\Domain\Dictionary\Models\Lexeme;
use App\Domain\Dictionary\Models\LexemeForm;
use App\Domain\Dictionary\Models\PartOfSpeech;
use App\Domain\Dictionary\Models\Sense;
use App\Domain\Dictionary\Models\SenseLevel;
use App\Domain\Dictionary\Models\SenseTranslation;
use App\Domain\Dictionary\Support\TextNormalizer;
use App\Models\Language;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class VocabularyImportService
{
    /**
     * @return array{
     *     files: list<string>,
     *     created: int,
     *     updated: int,
     *     senses: int,
     *     skipped: int,
     *     errors: list<string>
     * }
     */
    public function importPath(string $path, bool $dryRun = false, int $chunk = 100, bool $prune = false, ?string $level = null): array
    {
        $files = $this->resolveFiles($path, $level);

        $summary = [
            'files' => $files,
            'created' => 0,
            'updated' => 0,
            'senses' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($files as $file) {
            $result = $this->importFile($file, $dryRun, $chunk, $prune);
            $summary['created'] += $result['created'];
            $summary['updated'] += $result['updated'];
            $summary['senses'] += $result['senses'];
            $summary['skipped'] += $result['skipped'];
            $summary['errors'] = array_merge($summary['errors'], $result['errors']);
        }

        return $summary;
    }

    /**
     * @return array{created: int, updated: int, senses: int, skipped: int, errors: list<string>}
     */
    public function importFile(string $file, bool $dryRun = false, int $chunk = 100, bool $prune = false): array
    {
        $payload = $this->readPayload($file);
        $lexemes = $payload['lexemes'] ?? null;

        if (! is_array($lexemes)) {
            throw new InvalidArgumentException("File [{$file}] is missing a lexemes array.");
        }

        $defaultLevel = is_string($payload['meta']['level'] ?? null)
            ? strtoupper((string) $payload['meta']['level'])
            : null;

        $created = 0;
        $updated = 0;
        $senses = 0;
        $skipped = 0;
        $errors = [];

        if ($dryRun) {
            foreach ($lexemes as $index => $lexemeData) {
                try {
                    $this->validateLexemePayload($lexemeData, $index);
                } catch (InvalidArgumentException $e) {
                    $errors[] = $e->getMessage();
                    $skipped++;
                }
            }

            return [
                'created' => count($lexemes) - $skipped,
                'updated' => 0,
                'senses' => collect($lexemes)->sum(fn ($lexeme) => count($lexeme['senses'] ?? [])),
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        }

        $chunkSize = max(1, $chunk);

        foreach (array_chunk($lexemes, $chunkSize) as $batch) {
            DB::transaction(function () use ($batch, $defaultLevel, $prune, &$created, &$updated, &$senses, &$skipped, &$errors): void {
                foreach ($batch as $index => $lexemeData) {
                    try {
                        $this->validateLexemePayload($lexemeData, $index);
                        $result = $this->upsertLexeme($lexemeData, $defaultLevel, $prune);
                        if ($result['created']) {
                            $created++;
                        } else {
                            $updated++;
                        }
                        $senses += $result['senses'];
                    } catch (InvalidArgumentException|RuntimeException $e) {
                        $errors[] = $e->getMessage();
                        $skipped++;
                    }
                }
            });
        }

        return compact('created', 'updated', 'senses', 'skipped', 'errors');
    }

    /**
     * @return list<string>
     */
    public function resolveFiles(string $path, ?string $level = null): array
    {
        if (is_file($path)) {
            return [$path];
        }

        if (! is_dir($path)) {
            throw new InvalidArgumentException("Path [{$path}] is not a file or directory.");
        }

        $files = collect(scandir($path) ?: [])
            ->filter(fn (string $name): bool => str_ends_with($name, '_words.json'))
            ->map(fn (string $name): string => rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$name)
            ->sort()
            ->values();

        if ($level !== null) {
            $level = strtoupper($level);
            $files = $files->filter(function (string $file) use ($level): bool {
                $payload = $this->readPayload($file);
                $fileLevel = strtoupper((string) ($payload['meta']['level'] ?? ''));

                return $fileLevel === $level;
            })->values();
        }

        if ($files->isEmpty()) {
            throw new InvalidArgumentException("No vocabulary JSON files found in [{$path}].");
        }

        return $files->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function readPayload(string $file): array
    {
        if (! is_readable($file)) {
            throw new InvalidArgumentException("Cannot read vocabulary file [{$file}].");
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid JSON in [{$file}].");
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $lexemeData
     */
    private function validateLexemePayload(array $lexemeData, int $index): void
    {
        foreach (['import_key', 'language', 'lemma', 'senses'] as $field) {
            if (blank($lexemeData[$field] ?? null) && $field !== 'senses') {
                throw new InvalidArgumentException("Lexeme #{$index} is missing [{$field}].");
            }
        }

        if (! is_array($lexemeData['senses'] ?? null) || $lexemeData['senses'] === []) {
            throw new InvalidArgumentException("Lexeme #{$index} must contain at least one sense.");
        }

        foreach ($lexemeData['senses'] as $senseIndex => $sense) {
            if (! is_array($sense) || blank($sense['import_key'] ?? null)) {
                throw new InvalidArgumentException("Lexeme #{$index} sense #{$senseIndex} is missing import_key.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $lexemeData
     * @return array{created: bool, senses: int}
     */
    private function upsertLexeme(array $lexemeData, ?string $defaultLevel, bool $prune): array
    {
        $language = $this->language((string) $lexemeData['language']);
        $importKey = (string) $lexemeData['import_key'];
        $lemma = trim((string) $lexemeData['lemma']);

        $lexeme = Lexeme::withTrashed()->firstOrNew([
            'language_id' => $language->id,
            'import_key' => $importKey,
        ]);

        $created = ! $lexeme->exists;

        $lexeme->fill([
            'lemma' => $lemma,
            'normalized_lemma' => TextNormalizer::normalize($lemma, $language->code),
            'slug' => $lexemeData['slug'] ?? Str::slug(str_replace(':', '-', $importKey)),
            'disambiguation' => $lexemeData['disambiguation'] ?? null,
            'status' => $this->status($lexemeData['status'] ?? ContentStatus::Published->value),
        ]);
        $lexeme->deleted_at = null;
        $lexeme->save();

        $categoryIds = Category::query()
            ->whereIn('code', $lexemeData['categories'] ?? [])
            ->where('is_active', true)
            ->pluck('id');
        $lexeme->categories()->sync($categoryIds);

        $this->syncForms($lexeme, $lexemeData['forms'] ?? [], $language->code);

        $senseCount = 0;
        $seenSenseKeys = [];

        foreach ($lexemeData['senses'] as $senseData) {
            $sense = $this->upsertSense($lexeme, $senseData, $language, $defaultLevel, $prune);
            $seenSenseKeys[] = $sense->import_key;
            $senseCount++;
        }

        if ($prune) {
            $lexeme->senses()
                ->whereNotNull('import_key')
                ->whereNotIn('import_key', $seenSenseKeys)
                ->each(fn (Sense $sense) => $sense->delete());
        }

        return ['created' => $created, 'senses' => $senseCount];
    }

    /**
     * @param  list<array<string, mixed>>  $forms
     */
    private function syncForms(Lexeme $lexeme, array $forms, string $languageCode): void
    {
        $seen = [];

        foreach ($forms as $formData) {
            $form = trim((string) ($formData['form'] ?? ''));
            if ($form === '') {
                continue;
            }

            $seen[] = $form;

            LexemeForm::query()->updateOrCreate(
                [
                    'lexeme_id' => $lexeme->id,
                    'form' => $form,
                ],
                [
                    'normalized_form' => TextNormalizer::normalize($form, $languageCode),
                    'form_type' => $this->formType($formData['form_type'] ?? FormType::Inflected->value),
                    'grammatical_features' => $formData['grammatical_features'] ?? null,
                    'is_primary' => (bool) ($formData['is_primary'] ?? false),
                ],
            );
        }

        if ($seen !== []) {
            $lexeme->forms()->whereNotIn('form', $seen)->delete();
        }
    }

    /**
     * @param  array<string, mixed>  $senseData
     */
    private function upsertSense(
        Lexeme $lexeme,
        array $senseData,
        Language $sourceLanguage,
        ?string $defaultLevel,
        bool $prune,
    ): Sense {
        $importKey = (string) $senseData['import_key'];
        $posCode = $senseData['part_of_speech'] ?? null;
        $partOfSpeech = is_string($posCode) && $posCode !== ''
            ? PartOfSpeech::query()->where('code', $posCode)->first()
            : null;

        $definitionLanguage = null;
        if (! blank($senseData['definition_language'] ?? null)) {
            $definitionLanguage = $this->language((string) $senseData['definition_language']);
        }

        $usageLabel = $senseData['usage_label'] ?? null;
        if (is_array($senseData['verb_details'] ?? null)) {
            $flags = [];
            if (! empty($senseData['verb_details']['is_irregular'])) {
                $flags[] = 'irregular';
            }
            if (! empty($senseData['verb_details']['is_reflexive'])) {
                $flags[] = 'reflexive';
            }
            if ($flags !== []) {
                $usageLabel = trim(implode(', ', $flags).($usageLabel ? '; '.$usageLabel : ''));
            }
        }

        $sense = Sense::withTrashed()->firstOrNew([
            'lexeme_id' => $lexeme->id,
            'import_key' => $importKey,
        ]);

        $sense->fill([
            'part_of_speech_id' => $partOfSpeech?->id,
            'definition' => $senseData['definition'] ?? null,
            'definition_language_id' => $definitionLanguage?->id,
            'usage_label' => $usageLabel,
            'domain' => $senseData['domain'] ?? null,
            'sort_order' => (int) ($senseData['sort_order'] ?? 0),
            'status' => $this->status($senseData['status'] ?? ContentStatus::Published->value),
        ]);
        $sense->deleted_at = null;
        $sense->save();

        $levelCode = strtoupper((string) ($senseData['level'] ?? $defaultLevel ?? ''));
        if ($levelCode !== '') {
            $level = DifficultyLevel::query()->where('code', $levelCode)->first();
            if ($level !== null) {
                SenseLevel::query()->updateOrCreate(
                    [
                        'sense_id' => $sense->id,
                        'is_primary' => true,
                    ],
                    [
                        'difficulty_level_id' => $level->id,
                        'source' => 'vocabulary-import',
                        'assessed_at' => now(),
                    ],
                );
            }
        }

        foreach ($senseData['translations'] ?? [] as $i => $translation) {
            if (! is_array($translation) || blank($translation['language'] ?? null) || blank($translation['text'] ?? null)) {
                continue;
            }

            $target = $this->language((string) $translation['language']);

            SenseTranslation::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'target_language_id' => $target->id,
                ],
                [
                    'translation_text' => (string) $translation['text'],
                    'translation_type' => $this->translationType($translation['type'] ?? TranslationType::Equivalent->value),
                    'sort_order' => (int) ($translation['sort_order'] ?? $i),
                    'status' => $this->status($translation['status'] ?? ContentStatus::Published->value),
                ],
            );
        }

        if (is_array($senseData['german_noun'] ?? null)) {
            $this->upsertGermanNoun($sense, $senseData['german_noun'], $senseData['grammar_notes'] ?? null);
        } elseif (! blank($senseData['grammar_notes'] ?? null) && $partOfSpeech?->code === 'noun') {
            GermanNounDetail::query()->updateOrCreate(
                ['sense_id' => $sense->id],
                ['grammar_notes' => (string) $senseData['grammar_notes']],
            );
        }

        $seenExampleKeys = $this->syncExamples($sense, $senseData['examples'] ?? [], $sourceLanguage);
        $seenCollocationKeys = $this->syncCollocations($sense, $senseData['collocations'] ?? []);

        if ($prune) {
            $sense->examples()
                ->whereNotNull('import_key')
                ->whereNotIn('import_key', $seenExampleKeys)
                ->delete();
            $sense->collocations()
                ->whereNotNull('import_key')
                ->whereNotIn('import_key', $seenCollocationKeys)
                ->delete();
        }

        return $sense;
    }

    /**
     * @param  array{article?: string|null, grammatical_gender?: string|null, plural_form?: string|null}  $data
     */
    private function upsertGermanNoun(Sense $sense, array $data, mixed $grammarNotes): void
    {
        $article = isset($data['article']) ? mb_strtolower((string) $data['article']) : null;
        if ($article === '') {
            $article = null;
        }

        if ($article !== null && ! GermanArticle::isValid($article)) {
            throw new InvalidArgumentException("Invalid German article [{$article}] for sense [{$sense->import_key}].");
        }

        $gender = isset($data['grammatical_gender']) ? mb_strtolower((string) $data['grammatical_gender']) : null;
        if ($gender === '') {
            $gender = null;
        }

        GermanNounDetail::query()->updateOrCreate(
            ['sense_id' => $sense->id],
            [
                'article' => $article,
                'grammatical_gender' => $gender,
                'plural_form' => $data['plural_form'] ?? null,
                'grammar_notes' => is_string($grammarNotes) ? $grammarNotes : null,
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $examples
     * @return list<string>
     */
    private function syncExamples(Sense $sense, array $examples, Language $sourceLanguage): array
    {
        $seen = [];

        foreach ($examples as $i => $exampleData) {
            if (blank($exampleData['sentence'] ?? null)) {
                continue;
            }

            $importKey = (string) ($exampleData['import_key'] ?? $sense->import_key.':example:'.($i + 1));
            $seen[] = $importKey;

            $exampleLanguage = ! blank($exampleData['language'] ?? null)
                ? $this->language((string) $exampleData['language'])
                : $sourceLanguage;

            $difficultyLevelId = null;
            if (! blank($exampleData['difficulty_level'] ?? null)) {
                $difficultyLevelId = DifficultyLevel::query()
                    ->where('code', strtoupper((string) $exampleData['difficulty_level']))
                    ->value('id');
            }

            $example = Example::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'import_key' => $importKey,
                ],
                [
                    'language_id' => $exampleLanguage->id,
                    'sentence' => (string) $exampleData['sentence'],
                    'difficulty_level_id' => $difficultyLevelId,
                    'status' => $this->status($exampleData['status'] ?? ContentStatus::Published->value),
                    'sort_order' => (int) ($exampleData['sort_order'] ?? $i),
                ],
            );

            foreach ($exampleData['translations'] ?? [] as $translation) {
                if (! is_array($translation) || blank($translation['language'] ?? null) || blank($translation['translation'] ?? null)) {
                    continue;
                }

                $lang = $this->language((string) $translation['language']);

                ExampleTranslation::query()->updateOrCreate(
                    [
                        'example_id' => $example->id,
                        'language_id' => $lang->id,
                    ],
                    [
                        'translation' => (string) $translation['translation'],
                        'status' => $this->status($translation['status'] ?? ContentStatus::Published->value),
                    ],
                );
            }
        }

        return $seen;
    }

    /**
     * @param  list<array<string, mixed>>  $collocations
     * @return list<string>
     */
    private function syncCollocations(Sense $sense, array $collocations): array
    {
        $seen = [];

        foreach ($collocations as $i => $collocationData) {
            if (blank($collocationData['phrase'] ?? null)) {
                continue;
            }

            $importKey = (string) ($collocationData['import_key'] ?? $sense->import_key.':collocation:'.($i + 1));
            $seen[] = $importKey;

            $collocation = Collocation::query()->updateOrCreate(
                [
                    'sense_id' => $sense->id,
                    'import_key' => $importKey,
                ],
                [
                    'phrase' => (string) $collocationData['phrase'],
                    'pattern_type' => $collocationData['pattern_type'] ?? null,
                    'phrase_type' => $this->phraseType($collocationData['phrase_type'] ?? PhraseType::Collocation->value),
                    'status' => $this->status($collocationData['status'] ?? ContentStatus::Published->value),
                    'sort_order' => (int) ($collocationData['sort_order'] ?? $i),
                ],
            );

            foreach ($collocationData['translations'] ?? [] as $translation) {
                if (! is_array($translation) || blank($translation['language'] ?? null) || blank($translation['translation'] ?? null)) {
                    continue;
                }

                $lang = $this->language((string) $translation['language']);

                CollocationTranslation::query()->updateOrCreate(
                    [
                        'collocation_id' => $collocation->id,
                        'language_id' => $lang->id,
                    ],
                    [
                        'translation' => (string) $translation['translation'],
                        'status' => $this->status($translation['status'] ?? ContentStatus::Published->value),
                    ],
                );
            }
        }

        return $seen;
    }

    private function language(string $code): Language
    {
        $language = Language::query()->where('code', $code)->first();

        if ($language === null) {
            throw new RuntimeException("Language [{$code}] is not seeded.");
        }

        return $language;
    }

    private function status(string $status): ContentStatus
    {
        return ContentStatus::tryFrom($status) ?? ContentStatus::Published;
    }

    private function formType(string $value): FormType
    {
        return FormType::tryFrom($value) ?? FormType::Inflected;
    }

    private function phraseType(string $value): PhraseType
    {
        return PhraseType::tryFrom($value) ?? PhraseType::Collocation;
    }

    private function translationType(string $value): TranslationType
    {
        return TranslationType::tryFrom($value) ?? TranslationType::Equivalent;
    }
}
