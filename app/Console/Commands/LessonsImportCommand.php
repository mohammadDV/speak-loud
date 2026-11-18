<?php

namespace App\Console\Commands;

use App\Domain\Learning\Services\LessonsImportService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('lessons:import
    {path? : Course/questions JSON file or lessons data directory}
    {--dry-run : Validate without writing}
    {--chunk=100 : Questions per database transaction}
    {--prune : Remove lesson questions missing from the questions JSON}')]
#[Description('Import grammar course/lesson content and question banks (idempotent upsert)')]
class LessonsImportCommand extends Command
{
    public function handle(LessonsImportService $importer): int
    {
        $path = $this->argument('path') ?: database_path('data/lessons');
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));
        $prune = (bool) $this->option('prune');

        try {
            if (is_dir($path)) {
                $files = $importer->resolveLessonDataFiles($path);
                if ($files === []) {
                    $this->error('No course/questions JSON files found in directory.');

                    return self::FAILURE;
                }
            } elseif (is_file($path)) {
                $files = [$path];
            } else {
                $this->error("Path not found: {$path}");

                return self::FAILURE;
            }

            $this->info(($dryRun ? 'Dry-run validating' : 'Importing').' '.count($files).' file(s)...');

            $totals = [
                'courses_upserted' => 0,
                'lessons_upserted' => 0,
                'questions_created' => 0,
                'questions_updated' => 0,
                'questions_pruned' => 0,
            ];

            foreach ($files as $file) {
                $this->line(' - '.$file);
                $basename = basename($file);

                if (str_contains($basename, 'questions')) {
                    $result = $importer->importQuestionsFile($file, $dryRun, $chunk, $prune);
                } else {
                    $result = $importer->importCourseFile($file, $dryRun);
                }

                foreach ($totals as $key => $value) {
                    $totals[$key] = $value + $result[$key];
                }
            }
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Courses upserted', (string) $totals['courses_upserted']],
                ['Lessons upserted', (string) $totals['lessons_upserted']],
                ['Questions created', (string) $totals['questions_created']],
                ['Questions updated', (string) $totals['questions_updated']],
                ['Questions pruned', (string) $totals['questions_pruned']],
            ],
        );

        return self::SUCCESS;
    }
}
