<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use RuntimeException;

class WithdrawClaim
{
    public function __construct(private readonly IClaimRepository $claims) {}

    public function execute(int $claimId, int $senderId): Claim
    {
        $claim = $this->claims->findById($claimId);

        if (! $claim || $claim->sender_id !== $senderId) {
            throw new RuntimeException('Claim not found.');
        }

        if ($claim->status !== 'pending') {
            throw new RuntimeException('This claim can no longer be withdrawn.');
        }

        return $this->claims->updateStatus($claimId, 'withdrawn');
    }
}
