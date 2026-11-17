<?php

namespace App\Domain\Gamification\DTOs;

final readonly class NextStepRecommendation
{
    /**
     * @param  array<string, scalar|null>  $parameters
     */
    public function __construct(
        public string $activityType,
        public string $title,
        public string $description,
        public string $reason,
        public string $url,
        public int $estimatedMinutes,
        public array $parameters = [],
    ) {}
}
