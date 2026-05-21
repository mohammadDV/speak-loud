<?php

namespace App\Repositories;

use App\Models\Claim;
use App\Repositories\Contracts\IClaimRepository;
use App\Repositories\Contracts\IConversationRepository;
use Illuminate\Support\Collection;

class ClaimRepository implements IClaimRepository
{
    public function __construct(
        private readonly IConversationRepository $conversations,
    ) {}

    public function findById(int $id): ?Claim
    {
        return Claim::find($id);
    }

    public function findBySenderAndSchedule(int $senderId, int $scheduleId): ?Claim
    {
        return Claim::where('sender_id', $senderId)
            ->where('schedule_id', $scheduleId)
            ->first();
    }

    public function create(array $data): Claim
    {
        return Claim::create($data);
    }

    public function updateStatus(int $id, string $status): Claim
    {
        $claim = Claim::findOrFail($id);

        $claim->update([
            'status'       => $status,
            'responded_at' => now(),
        ]);

        return $claim->fresh();
    }

    public function reopen(int $id, ?string $message): Claim
    {
        $claim = Claim::findOrFail($id);

        $claim->update([
            'status'       => 'pending',
            'message'      => $message,
            'responded_at' => null,
        ]);

        return $claim->fresh();
    }

    public function incomingForUser(int $userId): Collection
    {
        $claims = Claim::query()
            ->where('receiver_id', $userId)
            ->with(['sender.profile', 'schedule.language'])
            ->latest()
            ->get();

        return $this->attachPartnerConversations($claims);
    }

    public function outgoingForUser(int $userId): Collection
    {
        $claims = Claim::query()
            ->where('sender_id', $userId)
            ->with(['receiver.profile', 'schedule', 'schedule.user'])
            ->latest()
            ->get();

        return $this->attachPartnerConversations($claims);
    }

    private function attachPartnerConversations(Collection $claims): Collection
    {
        $claims->each(function (Claim $claim) {
            $conversation = $this->conversations->findBetweenUsers(
                $claim->sender_id,
                $claim->receiver_id,
            );

            $claim->setRelation('conversation', $conversation);
        });

        return $claims;
    }
}
