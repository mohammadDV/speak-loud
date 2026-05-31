<?php

namespace App\Support;

use App\Models\User;

class ScheduleLimits
{
    public static function maxSlotsForUser(?User $user = null): int
    {
        // Future: return tier-specific limits from the user's subscription.
        return max(1, (int) config('schedules.max_slots_per_user', 3));
    }

    public static function limitReachedMessage(?User $user = null): string
    {
        $max = self::maxSlotsForUser($user);

        return "You can have up to {$max} slots. Delete an existing slot to create a new one.";
    }
}
