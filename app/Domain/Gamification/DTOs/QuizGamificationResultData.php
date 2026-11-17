<?php

namespace App\Domain\Gamification\DTOs;

final readonly class QuizGamificationResultData
{
    /**
     * @param  list<array{reason: string, amount: int, capped?: bool}>  $breakdown
     * @param  array{current: int, longest: int, completed_today: bool, at_risk: bool}|null  $streak
     * @param  array{target: int, earned: int, completed: bool, remaining: int}|null  $dailyGoal
     * @param  array{activity_type: string, title: string, reason: string, url: string, estimated_minutes: int}|null  $nextStep
     */
    public function __construct(
        public string $state,
        public int $xpTotal = 0,
        public array $breakdown = [],
        public ?array $streak = null,
        public ?array $dailyGoal = null,
        public ?array $nextStep = null,
        public bool $capped = false,
        public bool $belowMinimum = false,
    ) {}
}
