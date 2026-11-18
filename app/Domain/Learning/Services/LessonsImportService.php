<?php

namespace App\Domain\Learning\Services;

use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\CourseTranslation;
use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonQuestion;
use App\Domain\Learning\Models\LessonQuestionTranslation;
use App\Domain\Learning\Models\LessonTranslation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class LessonsImportService
{
    /**
     * @return array{courses_upserted: int, lessons_upserted: int, questions_created: int, questions_updated: int, questions_pruned: int}
     */
    public function importCourseFile(string $path, bool $dryRun = false): array
    {
        $payload = $this->decodeJsonFile($path);

        if (! isset($payload['course']) || ! is_array($payload['course'])) {
            throw new InvalidArgumentException("Course file missing course object: {$path}");
        }

        /** @var array<string, mixed> $courseData */
        $courseData = $payload['course'];
        /** @var list<array<string, mixed>> $lessonsData */
        $lessonsData = is_array($payload['lessons'] ?? null) ? $payload['lessons'] : [];

        $summary = [
            'courses_upserted' => 0,
            'lessons_upserted' => 0,
            'questions_created' => 0,
            'questions_updated' => 0,
            'questions_pruned' => 0,
        ];

        if ($dryRun) {
            $summary['courses_upserted'] = 1;
            $summary['lessons_upserted'] = count($lessonsData);

            return $summary;
        }

        DB::transaction(function () use ($courseData, $lessonsData, &$summary): void {
            $course = $this->upsertCourse($courseData);
            $summary['courses_upserted'] = 1;

            foreach ($lessonsData as $lessonData) {
                $this->upsertLesson($course, $lessonData);
                $summary['lessons_upserted']++;
            }
        });

        return $summary;
    }

    /**
     * @return array{courses_upserted: int, lessons_upserted: int, questions_created: int, questions_updated: int, questions_pruned: int}
     */
    public function importQuestionsFile(string $path, bool $dryRun = false, int $chunk = 100, bool $prune = false): array
    {
        $payload = $this->decodeJsonFile($path);
        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $courseCode = (string) ($meta['course_code'] ?? '');
        $lessonCode = (string) ($meta['lesson_code'] ?? '');

        if ($courseCode === '' || $lessonCode === '') {
            throw new InvalidArgumentException("Questions file meta must include course_code and lesson_code: {$path}");
        }

        /** @var list<array<string, mixed>> $questions */
        $questions = is_array($payload['questions'] ?? null) ? $payload['questions'] : [];

        $summary = [
            'courses_upserted' => 0,
            'lessons_upserted' => 0,
            'questions_created' => 0,
            'questions_updated' => 0,
            'questions_pruned' => 0,
        ];

        if ($dryRun) {
            $summary['questions_created'] = count($questions);

            return $summary;
        }

        $course = Course::query()->where('code', $courseCode)->first();
        if ($course === null) {
            throw new InvalidArgumentException("Course [{$courseCode}] not found. Import the course file first.");
        }

        $lesson = Lesson::query()
            ->where('course_id', $course->id)
            ->where('code', $lessonCode)
            ->first();

        if ($lesson === null) {
            throw new InvalidArgumentException("Lesson [{$lessonCode}] not found on course [{$courseCode}].");
        }

        $keptImportKeys = [];

        foreach (array_chunk($questions, max(1, $chunk)) as $batch) {
            DB::transaction(function () use ($batch, $lesson, &$summary, &$keptImportKeys): void {
                foreach ($batch as $questionData) {
                    $result = $this->upsertQuestion($lesson, $questionData);
                    $importKey = (string) ($questionData['import_key'] ?? '');
                    if ($importKey !== '') {
                        $keptImportKeys[] = $importKey;
                    }
                    if ($result === 'created') {
                        $summary['questions_created']++;
                    } else {
                        $summary['questions_updated']++;
                    }
                }
            });
        }

        if ($prune && $keptImportKeys !== []) {
            $summary['questions_pruned'] = LessonQuestion::query()
                ->where('lesson_id', $lesson->id)
                ->whereNotIn('import_key', $keptImportKeys)
                ->delete();
        }

        return $summary;
    }

    /**
     * @return list<string>
     */
    public function resolveLessonDataFiles(string $directory): array
    {
        $directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $files = [];

        $course = $directory.DIRECTORY_SEPARATOR.'german_a1_grammar_course.json';
        if (is_file($course)) {
            $files[] = $course;
        }

        $questionFiles = glob($directory.DIRECTORY_SEPARATOR.'*_questions.json') ?: [];
        sort($questionFiles);

        foreach ($questionFiles as $questionFile) {
            $files[] = $questionFile;
        }

        return $files;
    }

    /**
     * @param  array<string, mixed>  $courseData
     */
    private function upsertCourse(array $courseData): Course
    {
        $code = (string) ($courseData['code'] ?? '');
        if ($code === '') {
            throw new InvalidArgumentException('Course code is required.');
        }

        /** @var Course $course */
        $course = Course::query()->updateOrCreate(
            ['code' => $code],
            [
                'cefr_level' => $courseData['cefr_level'] ?? null,
                'status' => LessonContentStatus::tryFrom((string) ($courseData['status'] ?? 'published'))
                    ?? LessonContentStatus::Published,
                'position' => (int) ($courseData['position'] ?? 0),
            ],
        );

        /** @var list<array<string, mixed>> $translations */
        $translations = is_array($courseData['translations'] ?? null) ? $courseData['translations'] : [];
        foreach ($translations as $translation) {
            $languageCode = (string) ($translation['language_code'] ?? '');
            if ($languageCode === '') {
                continue;
            }

            CourseTranslation::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'language_code' => $languageCode,
                ],
                [
                    'title' => (string) ($translation['title'] ?? $code),
                    'description' => isset($translation['description']) ? (string) $translation['description'] : null,
                ],
            );
        }

        return $course;
    }

    /**
     * @param  array<string, mixed>  $lessonData
     */
    private function upsertLesson(Course $course, array $lessonData): Lesson
    {
        $code = (string) ($lessonData['code'] ?? '');
        if ($code === '') {
            throw new InvalidArgumentException('Lesson code is required.');
        }

        /** @var Lesson $lesson */
        $lesson = Lesson::query()->updateOrCreate(
            [
                'course_id' => $course->id,
                'code' => $code,
            ],
            [
                'cefr_level' => $lessonData['cefr_level'] ?? $course->cefr_level,
                'status' => LessonContentStatus::tryFrom((string) ($lessonData['status'] ?? 'published'))
                    ?? LessonContentStatus::Published,
                'position' => (int) ($lessonData['position'] ?? 0),
            ],
        );

        /** @var list<array<string, mixed>> $translations */
        $translations = is_array($lessonData['translations'] ?? null) ? $lessonData['translations'] : [];
        foreach ($translations as $translation) {
            $languageCode = (string) ($translation['language_code'] ?? '');
            if ($languageCode === '') {
                continue;
            }

            LessonTranslation::query()->updateOrCreate(
                [
                    'lesson_id' => $lesson->id,
                    'language_code' => $languageCode,
                ],
                [
                    'title' => (string) ($translation['title'] ?? $code),
                    'body_markdown' => (string) ($translation['body_markdown'] ?? ''),
                ],
            );
        }

        return $lesson;
    }

    /**
     * @param  array<string, mixed>  $questionData
     * @return 'created'|'updated'
     */
    private function upsertQuestion(Lesson $lesson, array $questionData): string
    {
        $importKey = (string) ($questionData['import_key'] ?? '');
        if ($importKey === '') {
            throw new InvalidArgumentException('Question import_key is required.');
        }

        $existing = LessonQuestion::query()->where('import_key', $importKey)->first();

        /** @var list<string> $distractors */
        $distractors = is_array($questionData['distractors'] ?? null)
            ? array_values(array_map(static fn (mixed $value): string => (string) $value, $questionData['distractors']))
            : [];

        /** @var LessonQuestion $question */
        $question = LessonQuestion::query()->updateOrCreate(
            ['import_key' => $importKey],
            [
                'lesson_id' => $lesson->id,
                'topic' => (string) ($questionData['topic'] ?? 'general'),
                'answer' => (string) ($questionData['answer'] ?? ''),
                'distractors' => $distractors,
                'blank_token' => isset($questionData['blank_token']) ? (string) $questionData['blank_token'] : '___',
                'source_lemma' => isset($questionData['source_lemma']) ? (string) $questionData['source_lemma'] : null,
                'sense_import_key' => isset($questionData['sense_import_key']) ? (string) $questionData['sense_import_key'] : null,
                'meta' => is_array($questionData['meta'] ?? null) ? $questionData['meta'] : null,
                'status' => LessonContentStatus::Published,
            ],
        );

        $rawTranslations = $questionData['translations'] ?? [];
        if (! is_array($rawTranslations)) {
            $rawTranslations = [];
        }

        foreach ($rawTranslations as $languageCode => $translation) {
            if (! is_array($translation)) {
                continue;
            }

            $code = is_string($languageCode)
                ? $languageCode
                : (string) ($translation['language_code'] ?? '');

            if ($code === '') {
                continue;
            }

            LessonQuestionTranslation::query()->updateOrCreate(
                [
                    'lesson_question_id' => $question->id,
                    'language_code' => $code,
                ],
                [
                    'prompt' => (string) ($translation['prompt'] ?? ''),
                    'hint' => isset($translation['hint']) ? (string) $translation['hint'] : null,
                    'explanation' => isset($translation['explanation']) ? (string) $translation['explanation'] : null,
                ],
            );
        }

        return $existing === null ? 'created' : 'updated';
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException("File not found: {$path}");
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new InvalidArgumentException("Unable to read file: {$path}");
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            throw new InvalidArgumentException("Invalid JSON: {$path}");
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }
}
