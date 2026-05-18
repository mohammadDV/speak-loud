<?php

namespace App\Repositories;

use App\Models\Conversation;
use App\Repositories\Contracts\IConversationRepository;
use Illuminate\Support\Collection;

class ConversationRepository implements IConversationRepository
{
    public function findById(int $id): ?Conversation
    {
        return Conversation::find($id);
    }

    public function findByClaim(int $claimId): ?Conversation
    {
        return Conversation::where('claim_id', $claimId)->first();
    }

    public function forUser(int $userId): Collection
    {
        return Conversation::query()
            ->where(fn ($q) => $q->where('user_a_id', $userId)->orWhere('user_b_id', $userId))
            ->with(['userA.profile', 'userB.profile', 'claim'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Conversation
    {
        return Conversation::create($data);
    }
}
