<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ChangeDailyGoal
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly EvaluateDailyGoal $evaluateDailyGoal,
    ) {}

    public function execute(User $user, int $dailyGoal): void
    {
        $min = (int) config('gamification.daily_goal.minimum', 5);
        $max = (int) config('gamification.daily_goal.maximum', 100);

        if ($dailyGoal < $min || $dailyGoal > $max) {
            throw ValidationException::withMessages([
                'daily_goal' => "Choose a daily goal between {$min} and {$max} XP.",
            ]);
        }

        DB::transaction(function () use ($user, $dailyGoal): void {
            $profile = $this->profiles->lockForUser($user->id);
            $profile->daily_goal = $dailyGoal;
            $profile->daily_goal_chosen_at ??= now();
            $profile->save();

            $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
            $today = CarbonImmutable::now($timezone)->toDateString();

            $daily = UserDailyActivity::query()
                ->where('user_id', $user->id)
                ->whereDate('activity_date', $today)
                ->first();

            if ($daily !== null && $daily->goal_completed_at === null) {
                $daily->daily_goal = $dailyGoal;
                $daily->save();
                $result = $this->evaluateDailyGoal->execute($profile, $daily->fresh());
                if ($result['bonus'] > 0) {
                    $profile->total_xp += $result['bonus'];
                    $profile->save();
                }
            }
        });
    }
}
