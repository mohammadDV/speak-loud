<?php

namespace App\Actions;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Repositories\Contracts\ITicketRepository;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ReplyToTicket
{
    public function __construct(
        private readonly ITicketRepository $tickets,
    ) {}

    public function execute(Ticket $ticket, User $sender, string $body, bool $isInternal = false): TicketMessage
    {
        $body = trim($body);

        if ($body === '') {
            throw new RuntimeException('Message cannot be empty.');
        }

        $isStaff = in_array($sender->role, ['admin', 'moderator'], true);

        if (! $isStaff && $ticket->user_id !== $sender->id) {
            throw new RuntimeException('You cannot reply to this ticket.');
        }

        if (! $isStaff && ! $ticket->userCanReply()) {
            throw ValidationException::withMessages([
                'replyBody' => $ticket->status === 'closed'
                    ? 'This ticket is closed.'
                    : 'Please wait for our team to respond before sending another message.',
            ]);
        }

        $message = $this->tickets->addMessage($ticket, $sender->id, $body, $isInternal);

        if (! $isInternal) {
            if ($isStaff) {
                $updates = ['status' => 'waiting_user'];

                if (! $ticket->assigned_to) {
                    $updates['assigned_to'] = $sender->id;
                }

                $ticket->update($updates);
            } elseif (in_array($ticket->status, ['waiting_user', 'resolved', 'closed'], true)) {
                $ticket->update(['status' => 'in_progress']);
            }
        }

        return $message;
    }
}
