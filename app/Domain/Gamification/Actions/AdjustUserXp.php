<?php

namespace App\Domain\Gamification\Actions;

use App\Domain\Gamification\Enums\XpReason;
use App\Domain\Gamification\Exceptions\InsufficientXpException;
use App\Domain\Gamification\Models\XpTransaction;
use App\Domain\Gamification\Services\GamificationProfileService;
use App\Domain\Gamification\Services\XpTransactionService;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class AdjustUserXp
{
    public function __construct(
        private readonly GamificationProfileService $profiles,
        private readonly AwardXp $awardXp,
        private readonly XpTransactionService $transactions,
    ) {}

    public function execute(User $target, int $amount, User $actor, string $note = ''): XpTransaction
    {
        if (! $actor->isAdmin()) {
            throw new AuthorizationException('Only active admins can adjust XP.');
        }

        return DB::transaction(function () use ($target, $amount, $actor, $note): XpTransaction {
            $profile = $this->profiles->lockForUser($target->id);

            if ($profile->total_xp + $amount < 0) {
                throw new InsufficientXpException('Adjustment would make total XP negative.');
            }

            $uuid = (string) Str::uuid();
            $key = $this->transactions->idempotencyKey(
                $target->id,
                'admin',
                $uuid,
                XpReason::AdministrativeAdjustment,
            );

            $transaction = $this->awardXp->execute(
                profile: $profile,
                amount: $amount,
                reason: XpReason::AdministrativeAdjustment,
                sourceType: 'admin',
                sourceId: $uuid,
                idempotencyKey: $key,
                localDate: null,
                metadata: [
                    'actor_id' => $actor->id,
                    'note' => $note,
                ],
            );

            if ($transaction === null) {
                throw new InsufficientXpException('Failed to record XP adjustment.');
            }

            $profile->total_xp += $amount;
            $profile->save();

            return $transaction;
        });
    }
}
