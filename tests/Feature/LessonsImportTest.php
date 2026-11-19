<?php

use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonQuestion;
use App\Domain\Learning\Services\LessonsImportService;
use Illuminate\Support\Facades\File;

test('lessons import upserts course lesson and questions idempotently', function () {
    $dir = storage_path('framework/testing/lessons-import');
    File::ensureDirectoryExists($dir);

    $coursePath = $dir.'/course.json';
    $questionsPath = $dir.'/questions.json';

    File::put($coursePath, json_encode([
        'course' => [
            'code' => 'de-grammar-a1',
            'cefr_level' => 'A1',
            'status' => 'published',
            'position' => 1,
            'translations' => [
                ['language_code' => 'en', 'title' => 'German Grammar A1', 'description' => 'Test course'],
            ],
        ],
        'lessons' => [
            [
                'code' => 'pronouns-and-present-tense',
                'cefr_level' => 'A1',
                'status' => 'published',
                'position' => 1,
                'translations' => [
                    [
                        'language_code' => 'en',
                        'title' => 'Pronouns & Present Tense',
                        'body_markdown' => "# Hello\n\nBody",
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    $questions = [];
    for ($i = 1; $i <= 12; $i++) {
        $questions[] = [
            'import_key' => "de-grammar-a1:l01:conj:kommen:{$i}",
            'topic' => 'regular_conjugation',
            'source_lemma' => 'kommen',
            'answer' => "komme{$i}",
            'distractors' => ['a', 'b', 'c'],
            'translations' => [
                'en' => ['prompt' => "Prompt {$i}"],
                'es' => ['prompt' => "Pregunta {$i}"],
            ],
        ];
    }

    File::put($questionsPath, json_encode([
        'meta' => [
            'course_code' => 'de-grammar-a1',
            'lesson_code' => 'pronouns-and-present-tense',
        ],
        'questions' => $questions,
    ], JSON_THROW_ON_ERROR));

    $importer = app(LessonsImportService::class);

    $firstCourse = $importer->importCourseFile($coursePath);
    $firstQuestions = $importer->importQuestionsFile($questionsPath);

    expect($firstCourse['courses_upserted'])->toBe(1)
        ->and($firstCourse['lessons_upserted'])->toBe(1)
        ->and($firstQuestions['questions_created'])->toBe(12)
        ->and(Course::query()->where('code', 'de-grammar-a1')->count())->toBe(1)
        ->and(Lesson::query()->count())->toBe(1)
        ->and(LessonQuestion::query()->count())->toBe(12);

    $secondQuestions = $importer->importQuestionsFile($questionsPath);

    expect($secondQuestions['questions_created'])->toBe(0)
        ->and($secondQuestions['questions_updated'])->toBe(12)
        ->and(LessonQuestion::query()->count())->toBe(12);

    $lesson = Lesson::query()->first();
    expect($lesson->translations()->where('language_code', 'en')->value('title'))
        ->toBe('Pronouns & Present Tense');
});

test('lessons import resolves course and all question bank files', function () {
    $files = app(LessonsImportService::class)->resolveLessonDataFiles(database_path('data/lessons'));

    expect($files[0] ?? null)->toEndWith('german_a1_grammar_course.json')
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_01_questions.json'))
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_02_questions.json'))
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_03_questions.json'))
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_04_questions.json'))
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_05_questions.json'))
        ->and($files)->toContain(database_path('data/lessons/german_grammar_lesson_06_questions.json'));
});

test('lessons import dry run does not write', function () {
    $path = storage_path('framework/testing/lessons-import/course-dry.json');
    File::ensureDirectoryExists(dirname($path));
    File::put($path, json_encode([
        'course' => [
            'code' => 'dry-course',
            'status' => 'published',
            'position' => 1,
            'translations' => [
                ['language_code' => 'en', 'title' => 'Dry', 'description' => null],
            ],
        ],
        'lessons' => [],
    ], JSON_THROW_ON_ERROR));

    $result = app(LessonsImportService::class)->importCourseFile($path, dryRun: true);

    expect($result['courses_upserted'])->toBe(1)
        ->and(Course::query()->where('code', 'dry-course')->exists())->toBeFalse();
});
