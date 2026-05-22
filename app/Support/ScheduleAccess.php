<?php

namespace App\Support;

use App\Models\Schedule;

class ScheduleAccess
{
    public static function canView(Schedule $schedule, ?int $userId): bool
    {
        if (! $userId) {
            return $schedule->status === 'active';
        }

        return $schedule->userCanView($userId);
    }

    public static function canAccessGroupChat(Schedule $schedule, int $userId): bool
    {
        return $schedule->userCanAccessGroupChat($userId);
    }
}
