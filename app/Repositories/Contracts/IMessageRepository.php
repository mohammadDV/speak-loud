<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Support\Collection;

interface IMessageRepository
{
    public function forConversation(int $conversationId): Collection;

    public function create(array $data): Message;

    public function markRead(int $conversationId, int $userId): void;
}
