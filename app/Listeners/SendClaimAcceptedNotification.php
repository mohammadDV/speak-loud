<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\ClaimAccepted;
use App\Notifications\ClaimAcceptedNotification;
use App\Support\UserWantsNotification;

class SendClaimAcceptedNotification
{
    public function handle(ClaimAccepted $event): void
    {
        $claim = $event->claim->loadMissing(['receiver.profile', 'sender', 'schedule']);
        $sender = $claim->sender;

        if ($sender === null) {
            return;
        }

        if (! UserWantsNotification::email($sender, NotificationType::ClaimAccepted)) {
            return;
        }

        $sender->notify(new ClaimAcceptedNotification($claim));
    }
}
