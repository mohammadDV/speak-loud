<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Dictionary\Enums\GermanArticle;
use App\Domain\Learning\Models\ArticleQuizSession;
use App\Domain\Learning\Services\ArticleQuizService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveArticleQuizResult
{
    public function __construct(
        private readonly ArticleQuizService $quizService,
    ) {}

    /**
     * Persist a completed quiz. Articles and is_correct are always resolved from the database.
     *
     * @param  array{
     *     level_codes: list<string>,
     *     category_codes: list<string>,
     *     word_count: int,
     *     translation_language_code: string,
     *     answers: list<array{
     *         sense_id: int,
     *         selected_article: string,
     *         position: int
     *     }>
     * }  $payload
     */
    public function execute(User $user, array $payload): ArticleQuizSession
    {
        return DB::transaction(function () use ($user, $payload): ArticleQuizSession {
            $incoming = $payload['answers'];
            $senseIds = array_map(static fn (array $answer): int => (int) $answer['sense_id'], $incoming);
            $resolved = $this->quizService->resolveArticlesBySenseId($senseIds);

            if (count($resolved) !== count(array_unique($senseIds))) {
                throw ValidationException::withMessages([
                    'answers' => 'One or more quiz words are no longer available. Please start a new quiz.',
                ]);
            }

            $answers = [];
            foreach ($incoming as $answer) {
                $senseId = (int) $answer['sense_id'];
                $selected = strtolower(trim((string) $answer['selected_article']));

                if (! GermanArticle::isValid($selected)) {
                    throw ValidationException::withMessages([
                        'answers.'.$senseId => 'Pick der, die, or das for every word.',
                    ]);
                }

                $correct = $resolved[$senseId]['article'];
                $answers[] = [
                    'sense_id' => $senseId,
                    'lemma' => $resolved[$senseId]['lemma'],
                    'correct_article' => $correct,
                    'selected_article' => $selected,
                    'is_correct' => $selected === $correct,
                    'position' => (int) $answer['position'],
                ];
            }

            $correctCount = count(array_filter($answers, fn (array $a): bool => $a['is_correct']));
            $total = count($answers);
            $incorrect = $total - $correctCount;
            $percentage = $total > 0 ? (int) round(($correctCount / $total) * 100) : 0;

            $session = ArticleQuizSession::query()->create([
                'user_id' => $user->id,
                'level_codes' => $payload['level_codes'] !== [] ? $payload['level_codes'] : null,
                'category_codes' => $payload['category_codes'] !== [] ? $payload['category_codes'] : null,
                'word_count' => $payload['word_count'],
                'translation_language_code' => $payload['translation_language_code'],
                'correct_count' => $correctCount,
                'incorrect_count' => $incorrect,
                'percentage' => $percentage,
                'sense_ids' => array_column($answers, 'sense_id'),
                'completed_at' => now(),
            ]);

            $session->answers()->createMany($answers);

            return $session->load('answers');
        });
    }
}
