<?php

namespace App\Actions;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use App\Repositories\Contracts\IMessageRepository;
use RuntimeException;

class RejectClaim
{
    public function __construct(
        private readonly IClaimRepository $claims,
        private readonly IConversationRepository $conversations,
        private readonly IMessageRepository $messages,
    ) {}

    public function execute(int $claimId, int $receiverId, ?string $message = null): Claim
    {
        $claim = $this->claims->findById($claimId);

        if (! $claim || $claim->receiver_id !== $receiverId) {
            throw new RuntimeException('Claim not found.');
        }

        if ($claim->status !== 'pending') {
            throw new RuntimeException('This claim can no longer be declined.');
        }

        $claim = $this->claims->updateStatus($claimId, 'rejected');

        $conversation = $this->conversations->create([
            'claim_id'  => $claim->id,
            'user_a_id' => $claim->receiver_id,
            'user_b_id' => $claim->sender_id,
        ]);

        $this->seedClaimThread($conversation->id, $claim, $message);

        return $claim->fresh(['conversation']);
    }

    private function seedClaimThread(int $conversationId, Claim $claim, ?string $rejectionMessage): void
    {
        $lastAt = null;

        if ($claim->message) {
            $this->messages->create([
                'conversation_id' => $conversationId,
                'sender_id'       => $claim->sender_id,
                'body'            => $claim->message,
            ]);
            $lastAt = now();
        }

        if ($rejectionMessage && trim($rejectionMessage) !== '') {
            $this->messages->create([
                'conversation_id' => $conversationId,
                'sender_id'       => $claim->receiver_id,
                'body'            => trim($rejectionMessage),
            ]);
            $lastAt = now();
        }

        if ($lastAt) {
            $conversation = $this->conversations->findById($conversationId);
            $conversation?->update(['last_message_at' => $lastAt]);
        }
    }
}
