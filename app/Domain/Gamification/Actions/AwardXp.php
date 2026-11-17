<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\UserGamificationProfile;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Services\XpTransactionService;

final class AwardXp
{
    public function __construct(
        private readonly XpTransactionService $transactions,
    ) {}

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(
        UserGamificationProfile $profile,
        int $amount,
        XpReason $reason,
        string $sourceType,
        ?string $sourceId,
        string $idempotencyKey,
        ?string $localDate = null,
        ?array $metadata = null,
    ): ?XpTransaction {
        if ($amount === 0) {
            return null;
        }

        $transaction = $this->transactions->createIdempotent(
            userId: $profile->user_id,
            amount: $amount,
            reason: $reason,
            sourceType: $sourceType,
            sourceId: $sourceId,
            idempotencyKey: $idempotencyKey,
            localDate: $localDate,
            metadata: $metadata,
        );

        if ($transaction === null) {
            return null;
        }

        return $transaction;
    }
}
