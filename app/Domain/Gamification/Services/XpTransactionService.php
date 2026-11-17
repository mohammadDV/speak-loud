<?php

namespace App\Domain\Gamification\Services;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Models\XpTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

final class XpTransactionService
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function createIdempotent(
        int $userId,
        int $amount,
        XpReason $reason,
        string $sourceType,
        ?string $sourceId,
        string $idempotencyKey,
        ?string $localDate = null,
        ?array $metadata = null,
    ): ?XpTransaction {
        try {
            return XpTransaction::query()->create([
                'user_id' => $userId,
                'amount' => $amount,
                'reason' => $reason,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'local_date' => $localDate,
                'metadata' => $metadata,
                'created_at' => Carbon::now(),
            ]);
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return null;
            }

            throw $e;
        }
    }

    public function idempotencyKey(int $userId, string $sourceType, string $sourceId, XpReason $reason): string
    {
        return "v1:{$userId}:{$sourceType}:{$sourceId}:{$reason->value}";
    }

    public function activityXpForDate(int $userId, string $localDate): int
    {
        return (int) XpTransaction::query()
            ->where('user_id', $userId)
            ->whereDate('local_date', $localDate)
            ->whereIn('reason', array_map(
                static fn (XpReason $reason): string => $reason->value,
                array_filter(XpReason::cases(), static fn (XpReason $reason): bool => $reason->isActivityXp()),
            ))
            ->sum('amount');
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;

        return $sqlState === '23000' || $sqlState === '23505';
    }
}
