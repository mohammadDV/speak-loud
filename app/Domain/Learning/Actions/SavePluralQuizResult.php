<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\PluralQuizSession;
use App\Domain\Learning\Services\PluralQuizService;
use App\Models\User;
use App\Support\TelegramNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SavePluralQuizResult
{
    public function __construct(
        private readonly PluralQuizService $quizService,
    ) {}

    /**
     * Persist a completed quiz. Plurals and is_correct are always resolved from the database.
     *
     * @param  array{
     *     level_codes: list<string>,
     *     category_codes: list<string>,
     *     word_count: int,
     *     translation_language_code: string,
     *     answers: list<array{
     *         sense_id: int,
     *         selected_plural: string,
     *         position: int
     *     }>
     * }  $payload
     */
    public function execute(User $user, array $payload): PluralQuizSession
    {
        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'answers' => 'Your account must be active to save quiz results.',
            ]);
        }

        $session = DB::transaction(function () use ($user, $payload): PluralQuizSession {
            $incoming = $payload['answers'];
            $answerCount = count($incoming);
            if ($answerCount < 5 || $answerCount > 50) {
                throw ValidationException::withMessages([
                    'answers' => 'Choose between 5 and 50 words.',
                ]);
            }

            $senseIds = array_map(static fn (array $answer): int => (int) $answer['sense_id'], $incoming);
            $resolved = $this->quizService->resolvePluralsBySenseId($senseIds);

            if (count($resolved) !== count(array_unique($senseIds))) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz words are no longer available. Please start a new quiz.',
                ]);
            }

            $graded = $this->quizService->gradeAnswers(
                $senseIds,
                collect($incoming)->mapWithKeys(
                    static fn (array $answer): array => [
                        (int) $answer['sense_id'] => (string) $answer['selected_plural'],
                    ],
                )->all(),
                $payload['translation_language_code'],
                $user,
            );

            $answers = [];
            foreach ($graded['results'] as $result) {
                $answers[] = [
                    'sense_id' => (int) $result['sense_id'],
                    'lemma' => (string) $result['lemma'],
                    'correct_plural' => (string) $result['correct_plural'],
                    'selected_plural' => (string) $result['selected_plural'],
                    'is_correct' => (bool) $result['is_correct'],
                    'match_score' => (int) $result['match_score'],
                    'position' => (int) $result['position'],
                ];
            }

            $session = PluralQuizSession::query()->create([
                'user_id' => $user->id,
                'level_codes' => $payload['level_codes'] !== [] ? $payload['level_codes'] : null,
                'category_codes' => $payload['category_codes'] !== [] ? $payload['category_codes'] : null,
                'word_count' => $payload['word_count'],
                'translation_language_code' => $payload['translation_language_code'],
                'correct_count' => $graded['correct_count'],
                'incorrect_count' => $graded['incorrect_count'],
                'percentage' => $graded['percentage'],
                'sense_ids' => array_column($answers, 'sense_id'),
                'completed_at' => now(),
            ]);

            $session->answers()->createMany($answers);

            return $session->load('answers');
        });

        event($session->completionEvent());

        TelegramNotifier::notify(
            "Plural quiz completed: #{$session->id} by user #{$user->id} ({$user->email})"
            ." — {$session->correct_count}/{$session->word_count} ({$session->percentage}%)"
        );

        return $session;
    }
}
