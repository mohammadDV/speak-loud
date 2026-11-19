<?php

namespace App\Domain\Learning\Services;

use App\Domain\Learning\Enums\SenseFlagContext;
use App\Domain\Learning\Models\ArticleQuizAnswer;
use App\Domain\Learning\Models\MeaningQuizAnswer;
use App\Domain\Learning\Models\PluralQuizAnswer;
use App\Domain\Learning\Models\UserSenseFlag;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class LearningInsightsService
{
    /**
     * @return array{article: int, meaning: int, plural: int}
     */
    public function importantUnlearnedCounts(User $user): array
    {
        return [
            'article' => $this->flagCount($user, SenseFlagContext::Article, important: true, learned: false),
            'meaning' => $this->flagCount($user, SenseFlagContext::Meaning, important: true, learned: false),
            'plural' => $this->flagCount($user, SenseFlagContext::Plural, important: true, learned: false),
        ];
    }

    public function learnedWordCount(User $user): int
    {
        return (int) UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('is_learned', true)
            ->distinct('sense_id')
            ->count('sense_id');
    }

    /**
     * @return array{article: int, meaning: int, plural: int}
     */
    public function difficultWordCounts(User $user): array
    {
        return [
            'article' => $this->countDifficultSenses($user, 'article_quiz'),
            'meaning' => $this->countDifficultSenses($user, 'meaning_quiz'),
            'plural' => $this->countDifficultSenses($user, 'plural_quiz'),
        ];
    }

    /**
     * @return list<int>
     */
    public function importantUnlearnedSenseIds(User $user, SenseFlagContext $context, int $limit = 100): array
    {
        return UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->where('is_important', true)
            ->where('is_learned', false)
            ->limit($limit)
            ->pluck('sense_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    public function difficultSenseIds(User $user, string $activityType, int $limit = 100): array
    {
        return $this->difficultSenseQuery($user, $activityType)
            ->limit($limit)
            ->pluck('sense_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function countDifficultSenses(User $user, string $activityType): int
    {
        $query = $this->difficultSenseQuery($user, $activityType);

        return (int) DB::query()
            ->fromSub($query->toBase(), 'difficult_senses')
            ->count();
    }

    /**
     * @return Builder<ArticleQuizAnswer>|Builder<MeaningQuizAnswer>|Builder<PluralQuizAnswer>
     */
    private function difficultSenseQuery(User $user, string $activityType): Builder
    {
        $minMistakes = (int) config('gamification.recommendations.difficult_min_mistakes', 2);
        $windowDays = (int) config('gamification.recommendations.difficult_window_days', 30);
        $since = now()->subDays($windowDays);

        if ($activityType === 'meaning_quiz') {
            return MeaningQuizAnswer::query()
                ->select('meaning_quiz_answers.sense_id')
                ->join('meaning_quiz_sessions', 'meaning_quiz_sessions.id', '=', 'meaning_quiz_answers.meaning_quiz_session_id')
                ->where('meaning_quiz_sessions.user_id', $user->id)
                ->where('meaning_quiz_answers.is_correct', false)
                ->where('meaning_quiz_sessions.completed_at', '>=', $since)
                ->whereNotExists(function ($query) use ($user): void {
                    $query->select(DB::raw(1))
                        ->from('user_sense_flags')
                        ->whereColumn('user_sense_flags.sense_id', 'meaning_quiz_answers.sense_id')
                        ->where('user_sense_flags.user_id', $user->id)
                        ->where('user_sense_flags.context', SenseFlagContext::Meaning->value)
                        ->where('user_sense_flags.is_learned', true);
                })
                ->groupBy('meaning_quiz_answers.sense_id')
                ->havingRaw('COUNT(*) >= ?', [$minMistakes]);
        }

        if ($activityType === 'plural_quiz') {
            return PluralQuizAnswer::query()
                ->select('plural_quiz_answers.sense_id')
                ->join('plural_quiz_sessions', 'plural_quiz_sessions.id', '=', 'plural_quiz_answers.plural_quiz_session_id')
                ->where('plural_quiz_sessions.user_id', $user->id)
                ->where('plural_quiz_answers.is_correct', false)
                ->where('plural_quiz_sessions.completed_at', '>=', $since)
                ->whereNotExists(function ($query) use ($user): void {
                    $query->select(DB::raw(1))
                        ->from('user_sense_flags')
                        ->whereColumn('user_sense_flags.sense_id', 'plural_quiz_answers.sense_id')
                        ->where('user_sense_flags.user_id', $user->id)
                        ->where('user_sense_flags.context', SenseFlagContext::Plural->value)
                        ->where('user_sense_flags.is_learned', true);
                })
                ->groupBy('plural_quiz_answers.sense_id')
                ->havingRaw('COUNT(*) >= ?', [$minMistakes]);
        }

        return ArticleQuizAnswer::query()
            ->select('article_quiz_answers.sense_id')
            ->join('article_quiz_sessions', 'article_quiz_sessions.id', '=', 'article_quiz_answers.article_quiz_session_id')
            ->where('article_quiz_sessions.user_id', $user->id)
            ->where('article_quiz_answers.is_correct', false)
            ->where('article_quiz_sessions.completed_at', '>=', $since)
            ->whereNotExists(function ($query) use ($user): void {
                $query->select(DB::raw(1))
                    ->from('user_sense_flags')
                    ->whereColumn('user_sense_flags.sense_id', 'article_quiz_answers.sense_id')
                    ->where('user_sense_flags.user_id', $user->id)
                    ->where('user_sense_flags.context', SenseFlagContext::Article->value)
                    ->where('user_sense_flags.is_learned', true);
            })
            ->groupBy('article_quiz_answers.sense_id')
            ->havingRaw('COUNT(*) >= ?', [$minMistakes]);
    }

    private function flagCount(User $user, SenseFlagContext $context, bool $important, bool $learned): int
    {
        return (int) UserSenseFlag::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->where('is_important', $important)
            ->where('is_learned', $learned)
            ->distinct('sense_id')
            ->count('sense_id');
    }
}
