<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use Illuminate\Support\Collection;

interface IConversationRepository
{
    public function findById(int $id): ?Conversation;

    public function findByClaim(int $claimId): ?Conversation;

    public function forUser(int $userId): Collection;

    public function create(array $data): Conversation;
}
