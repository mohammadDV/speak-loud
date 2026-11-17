<?php

namespace App\Domain\Gamification\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class XpAwarded
{
    use Dispatchable;

    /**
     * @param  array<string, int>  $amountsByReason
     */
    public function __construct(
        public int $userId,
        public int $totalXp,
        public array $amountsByReason,
        public string $sourceType,
        public string $sourceId,
    ) {}
}
