<?php

namespace App\Repositories;

use App\Models\Message;
use App\Repositories\Contracts\IMessageRepository;
use Illuminate\Support\Collection;

class MessageRepository implements IMessageRepository
{
    public const DEFAULT_LIMIT = 50;

    public function forConversation(int $conversationId, int $limit = self::DEFAULT_LIMIT): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversationId)
            ->with('sender.profile')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy([
                ['created_at', 'asc'],
                ['id', 'asc'],
            ])
            ->values();
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
