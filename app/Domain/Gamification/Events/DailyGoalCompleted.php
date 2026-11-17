<?php

namespace App\Domain\Gamification\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class DailyGoalCompleted
{
    use Dispatchable;

    public function __construct(
        public int $userId,
        public string $localDate,
        public int $bonus,
    ) {}
}
