<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\DTOs\StreakStatus;
use App\Domain\Gamification\Models\UserDailyActivity;
use App\Domain\Gamification\Models\UserGamificationProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

final class StreakService
{
    public function status(UserGamificationProfile $profile, ?CarbonImmutable $now = null): StreakStatus
    {
        $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
        $today = ($now ?? CarbonImmutable::now($timezone))->timezone($timezone)->startOfDay();
        $last = $profile->last_activity_date !== null
            ? CarbonImmutable::parse($profile->last_activity_date->toDateString(), $timezone)->startOfDay()
            : null;

        $completedToday = $last !== null && $last->equalTo($today);
        $atRisk = $last !== null && $last->equalTo($today->subDay());
        $effective = ($completedToday || $atRisk) ? $profile->current_streak : 0;

        return new StreakStatus(
            current: $effective,
            longest: $profile->longest_streak,
            completedToday: $completedToday,
            atRisk: $atRisk && ! $completedToday,
            nextTarget: $effective + 1,
        );
    }

    /**
     * @return array{old: int, new: int, longest: int, changed: bool}
     */
    public function applyQualifyingActivity(UserGamificationProfile $profile, string $activityDate): array
    {
        $old = $profile->current_streak;
        $last = $profile->last_activity_date?->toDateString();

        if ($last === $activityDate) {
            return [
                'old' => $old,
                'new' => $old,
                'longest' => $profile->longest_streak,
                'changed' => false,
            ];
        }

        if ($last !== null && $activityDate < $last) {
            $recalculated = $this->recalculateFromDailyRows($profile->user_id, $last);
            $profile->current_streak = $recalculated;
            $profile->longest_streak = max($profile->longest_streak, $recalculated);
            $profile->save();

            return [
                'old' => $old,
                'new' => $profile->current_streak,
                'longest' => $profile->longest_streak,
                'changed' => $old !== $profile->current_streak,
            ];
        }

        $timezone = $profile->timezone ?: (string) config('gamification.default_user_timezone', 'Europe/Berlin');
        $activity = CarbonImmutable::parse($activityDate, $timezone)->startOfDay();

        if ($last === null) {
            $profile->current_streak = 1;
        } else {
            $previous = CarbonImmutable::parse($last, $timezone)->startOfDay();
            if ($activity->equalTo($previous->addDay())) {
                $profile->current_streak = $old + 1;
            } else {
                $profile->current_streak = 1;
            }
        }

        $profile->last_activity_date = Carbon::parse($activityDate, $timezone)->startOfDay();
        $profile->longest_streak = max($profile->longest_streak, $profile->current_streak);
        $profile->save();

        return [
            'old' => $old,
            'new' => $profile->current_streak,
            'longest' => $profile->longest_streak,
            'changed' => $old !== $profile->current_streak,
        ];
    }

    public function recalculateFromDailyRows(int $userId, string $fromDate): int
    {
        $dates = UserDailyActivity::query()
            ->where('user_id', $userId)
            ->where('qualifying_activity_count', '>', 0)
            ->whereDate('activity_date', '<=', $fromDate)
            ->orderByDesc('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date): string => $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : (string) $date)
            ->values()
            ->all();

        if ($dates === []) {
            return 0;
        }

        $streak = 1;
        for ($i = 0; $i < count($dates) - 1; $i++) {
            $current = CarbonImmutable::parse($dates[$i]);
            $next = CarbonImmutable::parse($dates[$i + 1]);
            if ($current->subDay()->equalTo($next)) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }
}
