<?php

namespace App\Domain\Learning\Services;

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Enums\LessonContentStatus;
use App\Domain\Learning\Models\Course;
use App\Domain\Learning\Models\Lesson;
use App\Domain\Learning\Models\LessonQuestion;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class GrammarQuizService
{
    public const CHOICE_COUNT = 4;

    public const MIN_WORD_COUNT = 5;

    public const MAX_WORD_COUNT = 50;

    /** @var list<string> */
    public const UI_LANGUAGES = ['en', 'es', 'fa', 'ar', 'tr'];

    public function __construct(
        private readonly MeaningAnswerChecker $answerChecker,
    ) {}

    /**
     * @return Collection<int, Course>
     */
    public function publishedCourses(): Collection
    {
        return Course::query()
            ->where('status', LessonContentStatus::Published)
            ->with(['translations', 'lessons' => function ($query): void {
                $query->where('status', LessonContentStatus::Published)
                    ->orderBy('position')
                    ->with('translations');
            }])
            ->orderBy('position')
            ->get();
    }

    public function findPublishedCourse(string $code): ?Course
    {
        return Course::query()
            ->where('code', $code)
            ->where('status', LessonContentStatus::Published)
            ->with(['translations', 'lessons' => function ($query): void {
                $query->where('status', LessonContentStatus::Published)
                    ->orderBy('position')
                    ->with('translations');
            }])
            ->first();
    }

    public function findPublishedLesson(Course $course, string $lessonCode): ?Lesson
    {
        return Lesson::query()
            ->where('course_id', $course->id)
            ->where('code', $lessonCode)
            ->where('status', LessonContentStatus::Published)
            ->with('translations')
            ->first();
    }

    /**
     * @param  list<int>  $lessonIds
     * @return list<array{
     *     question_id: int,
     *     topic: string,
     *     source_lemma: string|null,
     *     prompt: string,
     *     answer: string,
     *     options?: list<string>,
     *     blank_token: string|null
     * }>
     */
    public function buildQuiz(
        array $lessonIds,
        int $wordCount,
        string $translationLanguageCode,
        GrammarQuizType $quizType,
    ): array {
        $this->assertWordCount($wordCount);
        $this->assertTranslationLanguage($translationLanguageCode);

        $lessonIds = array_values(array_unique(array_map('intval', $lessonIds)));
        if ($lessonIds === []) {
            throw ValidationException::withMessages([
                'lesson_ids' => 'Select at least one lesson.',
            ]);
        }

        $publishedLessonIds = Lesson::query()
            ->whereIn('id', $lessonIds)
            ->where('status', LessonContentStatus::Published)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (count($publishedLessonIds) !== count($lessonIds)) {
            throw ValidationException::withMessages([
                'lesson_ids' => 'One or more selected lessons are unavailable.',
            ]);
        }

        $pool = LessonQuestion::query()
            ->whereIn('lesson_id', $publishedLessonIds)
            ->where('status', LessonContentStatus::Published)
            ->with('translations')
            ->get();

        if ($pool->count() < self::MIN_WORD_COUNT) {
            throw ValidationException::withMessages([
                'lesson_ids' => 'Not enough published questions for the selected lessons.',
            ]);
        }

        if ($pool->count() < $wordCount) {
            throw ValidationException::withMessages([
                'word_count' => "Only {$pool->count()} questions are available for the selected lessons.",
            ]);
        }

        $selected = $pool->shuffle()->take($wordCount)->values();

        $cards = [];
        foreach ($selected as $question) {
            $translation = $question->translationFor($translationLanguageCode);
            if ($translation === null || trim($translation->prompt) === '') {
                continue;
            }

            $card = [
                'question_id' => (int) $question->id,
                'topic' => (string) $question->topic,
                'source_lemma' => $question->source_lemma,
                'prompt' => (string) $translation->prompt,
                'answer' => (string) $question->answer,
                'blank_token' => $question->blank_token,
            ];

            if ($quizType === GrammarQuizType::MultipleChoice) {
                $card['options'] = $this->buildOptions($question);
            }

            $cards[] = $card;
        }

        if (count($cards) < $wordCount) {
            throw ValidationException::withMessages([
                'translation_language_code' => 'Not enough translated prompts for the selected language.',
            ]);
        }

        return array_slice($cards, 0, $wordCount);
    }

    /**
     * @param  list<array{
     *     question_id: int,
     *     topic: string,
     *     source_lemma: string|null,
     *     prompt: string,
     *     answer: string,
     *     options?: list<string>,
     *     blank_token: string|null
     * }>  $cards
     * @return list<array{
     *     question_id: int,
     *     topic: string,
     *     source_lemma: string|null,
     *     prompt: string,
     *     options?: list<string>,
     *     blank_token: string|null
     * }>
     */
    public function toQuizPromptCards(array $cards): array
    {
        return array_map(static function (array $card): array {
            $prompt = [
                'question_id' => $card['question_id'],
                'topic' => $card['topic'],
                'source_lemma' => $card['source_lemma'],
                'prompt' => $card['prompt'],
                'blank_token' => $card['blank_token'],
            ];

            if (isset($card['options'])) {
                $prompt['options'] = $card['options'];
            }

            return $prompt;
        }, $cards);
    }

    /**
     * @param  list<array{question_id: int, selected_answer: string, position: int}>  $answers
     * @param  list<array{
     *     question_id: int,
     *     prompt: string,
     *     answer: string,
     *     options?: list<string>
     * }>  $cards
     * @return array{
     *     results: list<array{
     *         question_id: int,
     *         prompt: string,
     *         correct_answer: string,
     *         selected_answer: string,
     *         is_correct: bool,
     *         match_score: int,
     *         position: int
     *     }>,
     *     correct_count: int,
     *     incorrect_count: int,
     *     percentage: int
     * }
     */
    public function gradeAnswers(
        array $answers,
        array $cards,
        GrammarQuizType $quizType,
    ): array {
        $cardsById = [];
        foreach ($cards as $card) {
            $cardsById[(int) $card['question_id']] = $card;
        }

        $graded = [];
        foreach ($answers as $answer) {
            $questionId = (int) $answer['question_id'];
            $card = $cardsById[$questionId] ?? null;
            if ($card === null) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz questions are no longer available. Please start a new quiz.',
                ]);
            }

            $selected = trim((string) $answer['selected_answer']);
            if ($selected === '') {
                throw ValidationException::withMessages([
                    'answers.'.$questionId => 'Answer every question before submitting.',
                ]);
            }

            $correct = (string) $card['answer'];

            if ($quizType === GrammarQuizType::MultipleChoice) {
                $options = $card['options'] ?? [];
                if ($options !== [] && ! in_array($selected, $options, true)) {
                    throw ValidationException::withMessages([
                        'answers.'.$questionId => 'Choose one of the shown answers for every question.',
                    ]);
                }

                $isCorrect = $selected === $correct;
                $matchScore = $isCorrect ? 100 : 0;
            } else {
                $check = $this->answerChecker->check($selected, [$correct], 'de');
                $isCorrect = $check['is_correct'];
                $matchScore = $check['match_score'];
            }

            $graded[] = [
                'question_id' => $questionId,
                'prompt' => (string) $card['prompt'],
                'correct_answer' => $correct,
                'selected_answer' => $selected,
                'is_correct' => $isCorrect,
                'match_score' => $matchScore,
                'position' => (int) $answer['position'],
            ];
        }

        $correctCount = count(array_filter($graded, static fn (array $row): bool => $row['is_correct']));
        $total = count($graded);

        return [
            'results' => $graded,
            'correct_count' => $correctCount,
            'incorrect_count' => $total - $correctCount,
            'percentage' => $total > 0 ? (int) round(($correctCount / $total) * 100) : 0,
        ];
    }

    /**
     * @param  list<int>  $questionIds
     * @return array<int, array{answer: string, prompt: string, distractors: list<string>}>
     */
    public function resolveQuestionsById(array $questionIds, string $translationLanguageCode): array
    {
        $questions = LessonQuestion::query()
            ->whereIn('id', $questionIds)
            ->where('status', LessonContentStatus::Published)
            ->with('translations')
            ->get()
            ->keyBy('id');

        $resolved = [];
        foreach ($questionIds as $questionId) {
            /** @var LessonQuestion|null $question */
            $question = $questions->get($questionId);
            if ($question === null) {
                continue;
            }

            $translation = $question->translationFor($translationLanguageCode);
            $distractors = $question->distractors ?? [];
            $resolved[(int) $question->id] = [
                'answer' => (string) $question->answer,
                'prompt' => $translation === null ? '' : (string) $translation->prompt,
                'distractors' => array_map(static fn (mixed $value): string => (string) $value, $distractors),
            ];
        }

        return $resolved;
    }

    public function assertWordCount(int $wordCount): void
    {
        if ($wordCount < self::MIN_WORD_COUNT || $wordCount > self::MAX_WORD_COUNT) {
            throw ValidationException::withMessages([
                'word_count' => 'Choose between 5 and 50 questions.',
            ]);
        }
    }

    public function assertTranslationLanguage(string $languageCode): void
    {
        if (! in_array($languageCode, self::UI_LANGUAGES, true)) {
            throw ValidationException::withMessages([
                'translation_language_code' => 'Choose a supported language (en, es, fa, ar, tr).',
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function buildOptions(LessonQuestion $question): array
    {
        $options = [(string) $question->answer];
        foreach ($question->distractors ?? [] as $distractor) {
            $distractor = trim((string) $distractor);
            if ($distractor !== '' && ! in_array($distractor, $options, true)) {
                $options[] = $distractor;
            }
            if (count($options) >= self::CHOICE_COUNT) {
                break;
            }
        }

        while (count($options) < self::CHOICE_COUNT) {
            $pad = $question->answer.str_repeat('e', count($options));
            if (! in_array($pad, $options, true)) {
                $options[] = $pad;
            } else {
                $options[] = $pad.'x';
            }
        }

        shuffle($options);

        return array_slice($options, 0, self::CHOICE_COUNT);
    }
}
