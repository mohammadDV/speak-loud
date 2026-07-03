<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;
use App\Repositories\Contracts\IScheduleRepository;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AcceptClaim
{
    public function __construct(
        private readonly IClaimRepository $claims,
        private readonly IConversationRepository $conversations,
        private readonly IScheduleRepository $schedules,
        private readonly IMessageRepository $messages,
        private readonly SyncScheduleGroupChat $syncGroupChat,
    ) {}

    public function execute(int $claimId, int $receiverId): Claim
    {
        $claim = $this->claims->findById($claimId);

        if (! $claim || $claim->receiver_id !== $receiverId) {
            throw new RuntimeException('Claim not found.');
        }

        if ($claim->status !== 'pending') {
            throw new RuntimeException('This claim can no longer be accepted.');
        }

        if ($claim->schedule_id) {
            $accepted = $this->schedules->countAcceptedClaims($claim->schedule_id);
            $schedule = $this->schedules->findById($claim->schedule_id);

            if ($accepted >= $schedule->max_participants) {
                throw ValidationException::withMessages([
                    'claim' => sprintf(
                        'You have already accepted %d of %d %s for this slot. Increase max claims in My Schedule, or decline this claim.',
                        $accepted,
                        $schedule->max_participants,
                        $schedule->max_participants === 1 ? 'claim' : 'claims',
                    ),
                ]);
            }
        }

        $claim = $this->claims->updateStatus($claimId, 'accepted');

        if ($claim->schedule_id) {
            $schedule = $this->schedules->findById($claim->schedule_id);
            if ($schedule) {
                $this->syncGroupChat->execute($schedule);
            }
        }

        $conversation = $this->conversations->findOrCreateBetweenUsers(
            $claim->sender_id,
            $claim->receiver_id,
        );

        if ($claim->message) {
            $this->messages->create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $claim->sender_id,
                'body'            => $claim->message,
            ]);
            $conversation->update(['last_message_at' => now()]);
        }

        $claim->setRelation('conversation', $conversation->fresh());

        return $claim;
    }
}
