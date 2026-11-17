<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;

final class UpdateDailyActivity
{
    public function execute(
        UserGamificationProfile $profile,
        string $activityDate,
        int $activityXp,
        int $bonusXp,
        int $questionsAnswered,
        bool $countsAsCompletedQuiz,
        bool $qualifiesForStreak,
    ): UserDailyActivity {
        $daily = UserDailyActivity::query()->firstOrCreate(
            [
                'user_id' => $profile->user_id,
                'activity_date' => $activityDate,
            ],
            [
                'xp_earned' => 0,
                'bonus_xp_earned' => 0,
                'quizzes_completed' => 0,
                'questions_answered' => 0,
                'qualifying_activity_count' => 0,
                'daily_goal' => $profile->daily_goal,
                'goal_completed_at' => null,
            ],
        );

        $daily->xp_earned += $activityXp;
        $daily->bonus_xp_earned += $bonusXp;
        $daily->questions_answered += $questionsAnswered;

        if ($countsAsCompletedQuiz) {
            $daily->quizzes_completed += 1;
        }

        if ($qualifiesForStreak) {
            $daily->qualifying_activity_count += 1;
        }

        $daily->save();

        return $daily;
    }
}
