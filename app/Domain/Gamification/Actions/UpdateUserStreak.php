<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Services\StreakService;

final class UpdateUserStreak
{
    public function __construct(
        private readonly StreakService $streaks,
    ) {}

    /**
     * @return array{old: int, new: int, longest: int, changed: bool}
     */
    public function execute(UserGamificationProfile $profile, string $activityDate): array
    {
        if (! config('gamification.streak.enabled', true)) {
            return [
                'old' => $profile->current_streak,
                'new' => $profile->current_streak,
                'longest' => $profile->longest_streak,
                'changed' => false,
            ];
        }

        return $this->streaks->applyQualifyingActivity($profile, $activityDate);
    }
}
