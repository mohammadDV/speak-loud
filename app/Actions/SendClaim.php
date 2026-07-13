<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Support\ClaimLimits;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SendClaim
{
    public function __construct(private readonly IClaimRepository $claims) {}

    public function execute(array $data): Claim
    {
        $senderId   = $data['sender_id'];
        $limiterKey = ClaimLimits::rateLimiterKey($senderId);

        if (RateLimiter::tooManyAttempts($limiterKey, ClaimLimits::maxPerHour())) {
            throw ValidationException::withMessages([
                'claimMessage' => ClaimLimits::hourlyLimitReachedMessage($senderId),
            ]);
        }

        if (isset($data['schedule_id'])) {
            $existing = $this->claims->findBySenderAndSchedule($data['sender_id'], $data['schedule_id']);

            if ($existing) {
                if (in_array($existing->status, ['pending', 'accepted'], true)) {
                    throw new \RuntimeException('You have already sent a claim for this schedule.');
                }

                if (in_array($existing->status, ['rejected', 'withdrawn', 'expired'], true)) {
                    $claim = $this->claims->reopen($existing->id, $data['message'] ?? null);
                    RateLimiter::hit($limiterKey, ClaimLimits::windowSeconds());

                    return $claim;
                }
            }
        } else {
            $existing = $this->claims->findDirectClaimBetweenUsers($data['sender_id'], $data['receiver_id']);

            if ($existing) {
                if (in_array($existing->status, ['pending', 'accepted'], true)) {
                    throw new \RuntimeException('You have already sent a claim to this user.');
                }

                if (in_array($existing->status, ['rejected', 'withdrawn', 'expired'], true)) {
                    $claim = $this->claims->reopen($existing->id, $data['message'] ?? null);
                    RateLimiter::hit($limiterKey, ClaimLimits::windowSeconds());

                    return $claim;
                }
            }
        }

        $claim = $this->claims->create([
            'sender_id'   => $data['sender_id'],
            'receiver_id' => $data['receiver_id'],
            'schedule_id' => $data['schedule_id'] ?? null,
            'type'        => isset($data['schedule_id']) ? 'schedule' : 'direct',
            'message'     => $data['message'] ?? null,
        ]);

        RateLimiter::hit($limiterKey, ClaimLimits::windowSeconds());

        return $claim;
    }
}
