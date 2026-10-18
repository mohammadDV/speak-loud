<?php

namespace App\Support;

use App\Models\Schedule;

class ScheduleAccess
{
    public static function canView(Schedule $schedule, ?int $userId): bool
    {
        if (! $userId) {
            return false;
        }

        return $schedule->userCanView($userId);
    }

    public static function canSeeParticipants(Schedule $schedule, int $userId): bool
    {
        return $schedule->userCanSeeParticipants($userId);
    }

    public static function canAccessGroupChat(Schedule $schedule, int $userId): bool
    {
        return $schedule->userCanAccessGroupChat($userId);
    }
}
