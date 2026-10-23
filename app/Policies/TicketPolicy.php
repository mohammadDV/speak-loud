<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function view(User $user, Ticket $ticket): bool
    {
        return $ticket->user_id === $user->id || $user->isStaff();
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->isStaff()) {
            return true;
        }

        return $ticket->user_id === $user->id && $ticket->userCanReply();
    }
}
