<?php

namespace App\Domain\Gamification\DTOs;

final readonly class DailyGoalProgress
{
    public function __construct(
        public int $target,
        public int $earned,
        public int $remaining,
        public int $percent,
        public bool $completed,
    ) {}
}
