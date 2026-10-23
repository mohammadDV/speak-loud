<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use Illuminate\Auth\Access\AuthorizationException;

class WithdrawClaim
{
    public function __construct(private readonly IClaimRepository $claims) {}

    public function execute(int $claimId, int $senderId): Claim
    {
        $claim = $this->claims->findById($claimId);

        if (! $claim || $claim->sender_id !== $senderId) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        if ($claim->status !== 'pending') {
            throw new AuthorizationException('This claim can no longer be withdrawn.');
        }

        return $this->claims->updateStatus($claimId, 'withdrawn');
    }
}
