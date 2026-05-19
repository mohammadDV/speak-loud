<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use Illuminate\Support\Collection;

interface IConversationRepository
{
    public function findById(int $id): ?Conversation;

    public function findBetweenUsers(int $userId1, int $userId2): ?Conversation;

    public function findOrCreateBetweenUsers(int $userId1, int $userId2): Conversation;

    public function forUser(int $userId): Collection;

    public function create(array $data): Conversation;
}
