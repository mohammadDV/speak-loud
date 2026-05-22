<?php

namespace App\Actions;

use App\Models\Conversation;
use App\Models\Schedule;
use App\Repositories\Contracts\IConversationRepository;

class SyncScheduleGroupChat
{
    public function __construct(private readonly IConversationRepository $conversations) {}

    public function execute(Schedule $schedule): Conversation
    {
        return $this->conversations->syncScheduleGroupMembers($schedule);
    }
}
