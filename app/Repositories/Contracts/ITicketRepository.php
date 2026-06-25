<?php

namespace App\Repositories\Contracts;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Collection;

interface ITicketRepository
{
    public function forUser(int $userId): Collection;

    public function findForUser(int $ticketId, int $userId): ?Ticket;

    public function pendingStaffReplyForUser(int $userId): ?Ticket;

    public function messagesForTicket(int $ticketId, bool $includeInternal = false): Collection;

    public function create(array $data, string $initialMessage): Ticket;

    public function addMessage(Ticket $ticket, int $senderId, string $body, bool $isInternal = false): TicketMessage;
}
