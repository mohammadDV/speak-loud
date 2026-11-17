<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Enums\MeaningQuizType;
use App\Domain\Learning\Models\MeaningQuizSession;
use App\Domain\Learning\Services\MeaningAnswerChecker;
use App\Domain\Learning\Services\MeaningQuizService;
use App\Models\User;
use App\Support\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveMeaningQuizResult
{
    public function __construct(
        private readonly MeaningQuizService $quizService,
        private readonly MeaningAnswerChecker $answerChecker,
    ) {}

    /**
     * Persist a completed quiz. Correctness is always resolved from the database.
     *
     * @param  array{
     *     level_codes: list<string>,
     *     category_codes: list<string>,
     *     word_count: int,
     *     translation_language_code: string,
     *     quiz_type: string,
     *     answers: list<array{
     *         sense_id: int,
     *         selected_meaning: string,
     *         position: int
     *     }>,
     *     options_by_sense_id?: array<int|string, list<string>>
     * }  $payload
     */
    public function execute(User $user, array $payload): MeaningQuizSession
    {
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'answers' => 'Your account must be active to save quiz results.',
            ]);
        }

        $quizType = MeaningQuizType::tryFrom((string) $payload['quiz_type']);
        if ($quizType === null) {
            throw ValidationException::withMessages([
                'quiz_type' => 'Choose a valid quiz type.',
            ]);
        }

        $session = DB::transaction(function () use ($user, $payload, $quizType): MeaningQuizSession {
            $incoming = $payload['answers'];
            $senseIds = array_map(static fn (array $answer): int => (int) $answer['sense_id'], $incoming);
            $resolved = $this->quizService->resolveMeaningsBySenseId(
                $senseIds,
                (string) $payload['translation_language_code'],
            );

            if (count($resolved) !== count(array_unique($senseIds))) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz words are no longer available. Please start a new quiz.',
                ]);
            }

            /** @var array<int|string, list<string>> $optionsBySenseId */
            $optionsBySenseId = $payload['options_by_sense_id'] ?? [];

            $answers = [];
            foreach ($incoming as $answer) {
                $senseId = (int) $answer['sense_id'];
                $selected = trim((string) $answer['selected_meaning']);
                $accepted = $resolved[$senseId]['translations'];
                $correct = $resolved[$senseId]['translation'];

                if ($selected === '') {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Answer every word before submitting.',
                    ]);
                }

                if ($quizType === MeaningQuizType::MultipleChoice) {
                    $options = $optionsBySenseId[$senseId] ?? $optionsBySenseId[(string) $senseId] ?? [];
                    if ($options !== [] && ! in_array($selected, $options, true)) {
                        throw ValidationException::withMessages([
                            'answers.'.$senseId => 'Choose one of the shown meanings for every word.',
                        ]);
                    }

                    // Primary gloss only — never accept alternate translations as MCQ correct.
                    $isCorrect = $selected === $correct;
                    $matchScore = $isCorrect ? 100 : 0;
                } else {
                    $check = $this->answerChecker->check(
                        $selected,
                        $accepted,
                        (string) $payload['translation_language_code'],
                    );
                    $isCorrect = $check['is_correct'];
                    $matchScore = $check['match_score'];
                }

                $answers[] = [
                    'sense_id' => $senseId,
                    'lemma' => $resolved[$senseId]['lemma'],
                    'correct_meaning' => $correct,
                    'selected_meaning' => $selected,
                    'is_correct' => $isCorrect,
                    'match_score' => $matchScore,
                    'position' => (int) $answer['position'],
                ];
            }

            $correctCount = count(array_filter($answers, fn (array $a): bool => $a['is_correct']));
            $total = count($answers);
            $incorrect = $total - $correctCount;
            $percentage = $total > 0 ? (int) round(($correctCount / $total) * 100) : 0;

            $session = MeaningQuizSession::query()->create([
                'user_id' => $user->id,
                'level_codes' => $payload['level_codes'] !== [] ? $payload['level_codes'] : null,
                'category_codes' => $payload['category_codes'] !== [] ? $payload['category_codes'] : null,
                'word_count' => $payload['word_count'],
                'translation_language_code' => $payload['translation_language_code'],
                'quiz_type' => $quizType,
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrect,
                'percentage' => $percentage,
                'sense_ids' => array_column($answers, 'sense_id'),
                'completed_at' => now(),
            ]);

            $session->answers()->createMany($answers);

            return $session->load('answers');
        });

        event($session->completionEvent());

        TelegramNotifier::notify(
            "Meaning quiz completed: #{$session->id} by user #{$user->id} ({$user->email})"
            ." — {$session->correct_count}/{$session->word_count} ({$session->percentage}%) [{$quizType->value}]"
        );

        return $session;
    }
}
