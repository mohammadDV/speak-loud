<?php

namespace App\Domain\Learning\Actions;

use App\Domain\Learning\Models\ArticleQuizSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaveArticleQuizResult
{
    /**
     * @param  array{
     *     level_codes: list<string>,
     *     category_codes: list<string>,
     *     word_count: int,
     *     translation_language_code: string,
     *     answers: list<array{
     *         sense_id: int,
     *         lemma: string,
     *         correct_article: string,
     *         selected_article: string,
     *         is_correct: bool,
     *         position: int
     *     }>
     * }  $payload
     */
    public function execute(User $user, array $payload): ArticleQuizSession
    {
        return DB::transaction(function () use ($user, $payload): ArticleQuizSession {
            $answers = $payload['answers'];
            $correct = count(array_filter($answers, fn (array $a): bool => $a['is_correct']));
            $total = count($answers);
            $incorrect = $total - $correct;
            $percentage = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

            $session = ArticleQuizSession::query()->create([
                'user_id' => $user->id,
                'level_codes' => $payload['level_codes'] !== [] ? $payload['level_codes'] : null,
                'category_codes' => $payload['category_codes'] !== [] ? $payload['category_codes'] : null,
                'word_count' => $payload['word_count'],
                'translation_language_code' => $payload['translation_language_code'],
                'correct_count' => $correct,
                'incorrect_count' => $incorrect,
                'percentage' => $percentage,
                'sense_ids' => array_column($answers, 'sense_id'),
                'completed_at' => now(),
            ]);

            $session->answers()->createMany(
                array_map(fn (array $answer): array => [
                    'sense_id' => $answer['sense_id'],
                    'lemma' => $answer['lemma'],
                    'correct_article' => $answer['correct_article'],
                    'selected_article' => $answer['selected_article'],
                    'is_correct' => $answer['is_correct'],
                    'position' => $answer['position'],
                ], $answers)
            );

            return $session->load('answers');
        });
    }
}
