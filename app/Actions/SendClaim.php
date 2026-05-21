<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;

class SendClaim
{
    public function __construct(private readonly IClaimRepository $claims) {}

    public function execute(array $data): Claim
    {
        if (isset($data['schedule_id'])) {
            $existing = $this->claims->findBySenderAndSchedule($data['sender_id'], $data['schedule_id']);

            if ($existing) {
                if (in_array($existing->status, ['pending', 'accepted'], true)) {
                    throw new \RuntimeException('You have already sent a claim for this schedule.');
                }

                if (in_array($existing->status, ['rejected', 'withdrawn', 'expired'], true)) {
                    return $this->claims->reopen($existing->id, $data['message'] ?? null);
                }
            }
        }

        return $this->claims->create([
            'sender_id'   => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'schedule_id' => $data['schedule_id'] ?? null,
            'type'        => isset($data['schedule_id']) ? 'schedule' : 'direct',
            'message'     => $data['message'] ?? null,
        ]);
    }
}
