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

    public function findBetweenUsers(int $userId1, int $userId2): ?Conversation
    {
        ['user_a_id' => $userAId, 'user_b_id' => $userBId] = $this->normalizePair($userId1, $userId2);

        return Conversation::query()
            ->where('user_a_id', $userAId)
            ->where('user_b_id', $userBId)
            ->first();
    }

    public function findOrCreateBetweenUsers(int $userId1, int $userId2): Conversation
    {
        return $this->findBetweenUsers($userId1, $userId2)
            ?? $this->create($this->normalizePair($userId1, $userId2));
    }

    public function forUser(int $userId): Collection
    {
        return Conversation::query()
            ->where(fn ($q) => $q->where('user_a_id', $userId)->orWhere('user_b_id', $userId))
            ->with(['userA.profile', 'userB.profile'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(array $data): Conversation
    {
        if (isset($data['user_a_id'], $data['user_b_id'])) {
            $data = $this->normalizePair($data['user_a_id'], $data['user_b_id']);
        }

        return Conversation::create($data);
    }

    /**
     * @return array{user_a_id: int, user_b_id: int}
     */
    private function normalizePair(int $userId1, int $userId2): array
    {
        return $userId1 < $userId2
            ? ['user_a_id' => $userId1, 'user_b_id' => $userId2]
            : ['user_a_id' => $userId2, 'user_b_id' => $userId1];
    }
}
