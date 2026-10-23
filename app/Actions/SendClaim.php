<?php

namespace App\Actions;

use App\Models\Claim;
use App\Models\Schedule;
use App\Models\User;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IUserRepository;
use App\Support\ClaimLimits;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SendClaim
{
    public function __construct(
        private readonly IClaimRepository $claims,
        private readonly IUserRepository $users,
    ) {}

    public function execute(array $data): Claim
    {
        $senderId = (int) $data['sender_id'];
        $receiverId = (int) $data['receiver_id'];
        $scheduleId = isset($data['schedule_id']) ? (int) $data['schedule_id'] : null;

        $this->assertCanSend($senderId, $receiverId, $scheduleId);

        $limiterKey = ClaimLimits::rateLimiterKey($senderId);

        if (RateLimiter::tooManyAttempts($limiterKey, ClaimLimits::maxPerHour())) {
            throw ValidationException::withMessages([
                'claimMessage' => ClaimLimits::hourlyLimitReachedMessage($senderId),
            ]);
        }

        if ($scheduleId !== null) {
            $existing = $this->claims->findBySenderAndSchedule($senderId, $scheduleId);

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
            $existing = $this->claims->findDirectClaimBetweenUsers($senderId, $receiverId);

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
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'schedule_id' => $scheduleId,
            'type' => $scheduleId !== null ? 'schedule' : 'direct',
            'message' => $data['message'] ?? null,
        ]);

        RateLimiter::hit($limiterKey, ClaimLimits::windowSeconds());

        return $claim;
    }

    private function assertCanSend(int $senderId, int $receiverId, ?int $scheduleId): void
    {
        if ($senderId === $receiverId) {
            throw new AuthorizationException('You cannot send a claim to yourself.');
        }

        if ($this->users->areBlocked($senderId, $receiverId)) {
            throw new AuthorizationException('You cannot send a claim to this user.');
        }

        $receiver = User::query()->with('profile')->find($receiverId);

        if ($receiver === null || $receiver->status !== 'active') {
            throw new AuthorizationException('You cannot send a claim to this user.');
        }

        // Private profiles block cold direct messages only; schedule claims stay allowed.
        if ($scheduleId === null && $receiver->profile?->is_private) {
            throw new AuthorizationException('You cannot message this user.');
        }

        if ($scheduleId === null) {
            return;
        }

        $schedule = Schedule::query()->find($scheduleId);

        if ($schedule === null
            || (int) $schedule->user_id !== $receiverId
            || $schedule->status !== 'active'
        ) {
            throw new AuthorizationException('You cannot send a claim for this schedule.');
        }
    }
}
