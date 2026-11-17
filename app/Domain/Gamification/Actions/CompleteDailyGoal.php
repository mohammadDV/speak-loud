<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Services\XpCalculationService;
use App\Domain\Gamification\Services\XpTransactionService;

final class CompleteDailyGoal
{
    public function __construct(
        private readonly AwardXp $awardXp,
        private readonly XpTransactionService $transactions,
        private readonly XpCalculationService $calculation,
    ) {}

    /**
     * @return array{completed: bool, bonus: int, local_date: string}
     */
    public function execute(UserGamificationProfile $profile, UserDailyActivity $daily): array
    {
        if ($daily->goal_completed_at !== null) {
            return [
                'completed' => false,
                'bonus' => 0,
                'local_date' => $daily->activity_date->toDateString(),
            ];
        }

        $bonus = $this->calculation->resolveAmount('article_quiz', XpReason::DailyGoalCompleted);
        $bonus = (int) config('gamification.xp.daily_goal_completed', $bonus);
        $localDate = $daily->activity_date->toDateString();
        $key = $this->transactions->idempotencyKey(
            $profile->user_id,
            'daily_goal',
            $localDate,
            XpReason::DailyGoalCompleted,
        );

        $tx = $this->awardXp->execute(
            profile: $profile,
            amount: $bonus,
            reason: XpReason::DailyGoalCompleted,
            sourceType: 'daily_goal',
            sourceId: $localDate,
            idempotencyKey: $key,
            localDate: $localDate,
            metadata: null,
        );

        if ($tx === null) {
            return [
                'completed' => false,
                'bonus' => 0,
                'local_date' => $localDate,
            ];
        }

        $daily->goal_completed_at = now();
        $daily->bonus_xp_earned += $bonus;
        $daily->save();

        return [
            'completed' => true,
            'bonus' => $bonus,
            'local_date' => $localDate,
        ];
    }
}
