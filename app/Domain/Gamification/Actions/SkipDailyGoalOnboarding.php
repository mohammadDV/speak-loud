<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Services\GamificationProfileService;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SkipDailyGoalOnboarding
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
    ) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $profile = $this->profiles->lockForUser($user->id);

            if ($profile->daily_goal_chosen_at === null) {
                $profile->daily_goal_chosen_at = now();
                $profile->save();
            }
        });
    }
}
