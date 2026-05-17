<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\IMessageRepository;
use Illuminate\Support\Collection;

class MessageRepository implements IMessageRepository
{
    public function forConversation(int $conversationId): Collection
    {
        return Message::where('conversation_id', $conversationId)
            ->with('sender')
            ->orderBy('created_at')
            ->get();
    }

    public function create(array $data): Message
    {
        return Message::create($data);
    }

    public function markRead(int $conversationId, int $userId): void
    {
        Message::where('conversation_id', $conversationId)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
