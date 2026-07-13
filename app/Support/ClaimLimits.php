<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;

class ClaimLimits
{
    public static function maxPerHour(): int
    {
        return max(1, (int) config('claims.max_per_hour', 3));
    }

    public static function windowSeconds(): int
    {
        return max(60, (int) config('claims.rate_limit_window_seconds', 3600));
    }

    public static function rateLimiterKey(int $userId): string
    {
        return "send-claim:{$userId}";
    }

    public static function hourlyLimitReachedMessage(int $userId): string
    {
        $max = self::maxPerHour();
        $seconds = RateLimiter::availableIn(self::rateLimiterKey($userId));
        $minutes = max(1, (int) ceil($seconds / 60));

        return "You can send up to {$max} claims per hour. Please try again in about {$minutes} minute(s).";
    }
}
