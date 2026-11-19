<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Enums\GrammarQuizType;
use App\Domain\Learning\Models\GrammarQuizSession;
use App\Domain\Learning\Models\UserLessonProgress;
use App\Domain\Learning\Services\GrammarQuizService;
use App\Domain\Learning\Services\MeaningAnswerChecker;
use App\Models\User;
use App\Support\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveGrammarQuizResult
{
    public function __construct(
        private readonly GrammarQuizService $quizService,
        private readonly MeaningAnswerChecker $answerChecker,
    ) {}

    /**
     * @param  array{
     *     lesson_ids: list<int>,
     *     word_count: int,
     *     translation_language_code: string,
     *     quiz_type: string,
     *     answers: list<array{
     *         question_id: int,
     *         selected_answer: string,
     *         position: int
     *     }>,
     *     options_by_question_id?: array<int|string, list<string>>
     * }  $payload
     */
    public function execute(User $user, array $payload): GrammarQuizSession
    {
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'answers' => 'Your account must be active to save quiz results.',
            ]);
        }

        $quizType = GrammarQuizType::tryFrom((string) $payload['quiz_type']);
        if ($quizType === null) {
            throw ValidationException::withMessages([
                'quiz_type' => 'Choose a valid quiz type.',
            ]);
        }

        $translationLanguage = (string) $payload['translation_language_code'];
        if (! in_array($translationLanguage, GrammarQuizService::UI_LANGUAGES, true)) {
            throw ValidationException::withMessages([
                'translation_language_code' => 'Choose a supported translation language.',
            ]);
        }

        $session = DB::transaction(function () use ($user, $payload, $quizType, $translationLanguage): GrammarQuizSession {
            $incoming = $payload['answers'];
            $questionIds = array_map(static fn (array $answer): int => (int) $answer['question_id'], $incoming);
            $resolved = $this->quizService->resolveQuestionsById(
                $questionIds,
                $translationLanguage,
            );

            if (count($resolved) !== count(array_unique($questionIds))) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz questions are no longer available. Please start a new quiz.',
                ]);
            }

            $claimedLessonIds = array_values(array_unique(array_map('intval', $payload['lesson_ids'])));
            $questionLessonIds = array_values(array_unique(array_map(
                static fn (array $row): int => (int) $row['lesson_id'],
                $resolved,
            )));

            foreach ($questionLessonIds as $questionLessonId) {
                if (! in_array($questionLessonId, $claimedLessonIds, true)) {
                    throw ValidationException::withMessages([
                        'lesson_ids' => 'Quiz answers must belong to the selected lessons.',
                    ]);
                }
            }

            /** @var array<int|string, list<string>> $optionsByQuestionId */
            $optionsByQuestionId = $payload['options_by_question_id'] ?? [];

            $answers = [];
            foreach ($incoming as $answer) {
                $questionId = (int) $answer['question_id'];
                $selected = trim((string) $answer['selected_answer']);
                $correct = $resolved[$questionId]['answer'];
                $prompt = $resolved[$questionId]['prompt'];

                if ($selected === '') {
                    throw ValidationException::withMessages([
                        'answers.'.$questionId => 'Answer every question before submitting.',
                    ]);
                }

                if ($quizType === GrammarQuizType::MultipleChoice) {
                    $options = $optionsByQuestionId[$questionId] ?? $optionsByQuestionId[(string) $questionId] ?? [];
                    if ($options === []) {
                        throw ValidationException::withMessages([
                            'answers.'.$questionId => 'Multiple-choice options are missing. Please start a new quiz.',
                        ]);
                    }
                    if (! in_array($selected, $options, true)) {
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

                $answers[] = [
                    'lesson_question_id' => $questionId,
                    'prompt' => $prompt,
                    'correct_answer' => $correct,
                    'selected_answer' => $selected,
                    'is_correct' => $isCorrect,
                    'match_score' => $matchScore,
                    'position' => (int) $answer['position'],
                ];
            }

            $correctCount = count(array_filter($answers, fn (array $a): bool => $a['is_correct']));
            $total = count($answers);
            $incorrect = $total - $correctCount;
            $percentage = $total > 0 ? (int) round(($correctCount / $total) * 100) : 0;

            // Progress only for lessons that actually appear in this quiz attempt.
            $lessonIds = $questionLessonIds;

            $session = GrammarQuizSession::query()->create([
                'user_id' => $user->id,
                'lesson_ids' => $lessonIds,
                'word_count' => $payload['word_count'],
                'quiz_type' => $quizType,
                'translation_language_code' => $translationLanguage,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrect,
                'percentage' => $percentage,
                'question_ids' => array_column($answers, 'lesson_question_id'),
                'completed_at' => now(),
            ]);

            $session->answers()->createMany($answers);

            foreach ($lessonIds as $lessonId) {
                $progress = UserLessonProgress::query()->firstOrNew([
                    'user_id' => $user->id,
                    'lesson_id' => $lessonId,
                ]);

                $progress->last_completed_at = now();
                $progress->attempts_count = (int) $progress->attempts_count + 1;
                $progress->best_percentage = max((int) $progress->best_percentage, $percentage);
                $progress->save();
            }

            return $session->load('answers');
        });

        event($session->completionEvent());

        TelegramNotifier::notify(
            "Grammar quiz completed: #{$session->id} by user #{$user->id} ({$user->email})"
            ." — {$session->correct_count}/{$session->word_count} ({$session->percentage}%) [{$quizType->value}]"
        );

        return $session;
    }
}
