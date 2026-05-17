<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IScheduleRepository;

class AcceptClaim
{
    public function __construct(
        private readonly IClaimRepository $claims,
        private readonly IConversationRepository $conversations,
        private readonly IScheduleRepository $schedules,
    ) {}

    public function execute(int $claimId): Claim
    {
        $claim = $this->claims->findById($claimId);

        if (!$claim) {
            throw new \RuntimeException('Claim not found.');
        }

        if ($claim->schedule_id) {
            $accepted = $this->schedules->countAcceptedClaims($claim->schedule_id);
            $schedule = $this->schedules->findById($claim->schedule_id);

            if ($accepted >= $schedule->max_participants) {
                throw new \RuntimeException('This schedule is already at capacity.');
            }
        }

        $claim = $this->claims->updateStatus($claimId, 'accepted');

        $this->conversations->create([
            'claim_id'  => $claim->id,
            'user_a_id' => $claim->receiver_id,
            'user_b_id' => $claim->sender_id,
        ]);

        return $claim;
    }
}
