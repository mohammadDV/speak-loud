<?php

namespace App\Repositories\Contracts;

use App\Models\Conversation;
use App\Models\Schedule;
use Illuminate\Support\Collection;

interface IConversationRepository
{
    public function findById(int $id): ?Conversation;

    public function findBetweenUsers(int $userId1, int $userId2): ?Conversation;

    public function findOrCreateBetweenUsers(int $userId1, int $userId2): Conversation;

    public function findOrCreateForSchedule(Schedule $schedule): Conversation;

    public function syncScheduleGroupMembers(Schedule $schedule): Conversation;

    public function userCanAccess(int $conversationId, int $userId): bool;

    public function forUser(int $userId): Collection;

    public function create(array $data): Conversation;
}
