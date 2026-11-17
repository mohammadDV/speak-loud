<?php

namespace App\Domain\Gamification\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class StreakUpdated
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public int $oldStreak,
        public int $newStreak,
        public int $longestStreak,
    ) {}
}
