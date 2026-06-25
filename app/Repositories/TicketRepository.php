<?php

namespace App\Repositories;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Repositories\Contracts\ITicketRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TicketRepository implements ITicketRepository
{
    public function forUser(int $userId): Collection
    {
        return Ticket::query()
            ->with(['category', 'messages' => fn ($query) => $query->latest()->limit(1)])
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->get();
    }

    public function findForUser(int $ticketId, int $userId): ?Ticket
    {
        return Ticket::query()
            ->with(['category', 'assignee.profile'])
            ->where('id', $ticketId)
            ->where('user_id', $userId)
            ->first();
    }

    public function pendingStaffReplyForUser(int $userId): ?Ticket
    {
        return Ticket::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->latest('updated_at')
            ->first();
    }

    public function messagesForTicket(int $ticketId, bool $includeInternal = false): Collection
    {
        return TicketMessage::query()
            ->with(['sender.profile'])
            ->where('ticket_id', $ticketId)
            ->when(! $includeInternal, fn ($query) => $query->where('is_internal', false))
            ->orderBy('created_at')
            ->get();
    }

    public function create(array $data, string $initialMessage): Ticket
    {
        return DB::transaction(function () use ($data, $initialMessage) {
            $ticket = Ticket::create($data);

            TicketMessage::create([
                'ticket_id'   => $ticket->id,
                'sender_id'   => $ticket->user_id,
                'body'        => trim($initialMessage),
                'is_internal' => false,
            ]);

            return $ticket->fresh(['category']);
        });
    }

    public function addMessage(Ticket $ticket, int $senderId, string $body, bool $isInternal = false): TicketMessage
    {
        return TicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => $senderId,
            'body'        => trim($body),
            'is_internal' => $isInternal,
        ]);
    }
}
