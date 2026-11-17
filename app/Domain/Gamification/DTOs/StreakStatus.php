<?php

namespace App\Domain\Gamification\DTOs;

final readonly class StreakStatus
{
    public function __construct(
        public int $current,
        public int $longest,
        public bool $completedToday,
        public bool $atRisk,
        public int $nextTarget,
    ) {}
}
