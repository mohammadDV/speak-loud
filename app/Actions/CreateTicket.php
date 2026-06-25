<?php

namespace App\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Repositories\Contracts\ITicketRepository;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateTicket
{
    public function __construct(
        private readonly ITicketRepository $tickets,
    ) {}

    public function execute(User $user, ?int $categoryId, string $subject, string $body): Ticket
    {
        $subject = trim($subject);
        $body = trim($body);

        if ($subject === '' || $body === '') {
            throw new RuntimeException('Subject and message are required.');
        }

        if ($this->tickets->pendingStaffReplyForUser($user->id)) {
            throw ValidationException::withMessages([
                'subject' => 'You already have a ticket waiting for a response from our team.',
            ]);
        }

        return $this->tickets->create([
            'user_id'     => $user->id,
            'category_id' => $categoryId,
            'subject'     => $subject,
            'status'      => 'open',
            'priority'    => 'normal',
        ], $body);
    }
}
