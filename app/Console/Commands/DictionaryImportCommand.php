<?php

namespace App\Console\Commands;

use App\Domain\Dictionary\Services\VocabularyImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('dictionary:import
    {path? : Vocabulary JSON file or directory (default: database/data/vocabulary)}
    {--all : Import every *_words.json file in a directory}
    {--level= : Only import files whose meta.level matches (A1-C2)}
    {--dry-run : Validate without writing}
    {--chunk=100 : Lexemes per database transaction}
    {--prune : Remove imported children missing from the file}')]
#[Description('Import CEFR vocabulary JSON files into the dictionary (idempotent, A1-C2)')]
class DictionaryImportCommand extends Command
{
    public function handle(VocabularyImportService $importer): int
    {
        $path = $this->argument('path') ?: database_path('data/vocabulary');
        $level = $this->option('level');
        $dryRun = (bool) $this->option('dry-run');
        $prune = (bool) $this->option('prune');
        $chunk = max(1, (int) $this->option('chunk'));
        $all = (bool) $this->option('all');

        if (is_dir($path) && ! $all && $level === null && ! str_ends_with(rtrim($path, DIRECTORY_SEPARATOR), '.json')) {
            $this->error('Directory imports require --all or --level=A1 (etc). Pass a JSON file path, or use --all.');

            return self::FAILURE;
        }

        try {
            if (is_file($path)) {
                $files = [$path];
            } else {
                $files = $importer->resolveFiles($path, is_string($level) ? $level : null);
            }

            $this->info(($dryRun ? 'Dry-run validating' : 'Importing').' '.count($files).' file(s)...');

            $summary = [
                'files' => $files,
                'created' => 0,
                'updated' => 0,
                'senses' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            foreach ($files as $file) {
                $this->line(' - '.$file);
                $result = $importer->importFile($file, $dryRun, $chunk, $prune);
                $summary['created'] += $result['created'];
                $summary['updated'] += $result['updated'];
                $summary['senses'] += $result['senses'];
                $summary['skipped'] += $result['skipped'];
                $summary['errors'] = array_merge($summary['errors'], $result['errors']);
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files', (string) count($summary['files'])],
                ['Lexemes created', (string) $summary['created']],
                ['Lexemes updated', (string) $summary['updated']],
                ['Senses processed', (string) $summary['senses']],
                ['Skipped', (string) $summary['skipped']],
                ['Errors', (string) count($summary['errors'])],
            ],
        );

        foreach (array_slice($summary['errors'], 0, 20) as $error) {
            $this->warn($error);
        }

        if ($summary['errors'] !== [] && count($summary['errors']) > 20) {
            $this->warn('... and '.(count($summary['errors']) - 20).' more errors');
        }

        return $summary['errors'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
