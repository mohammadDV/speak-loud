<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\ClaimRejected;
use App\Notifications\ClaimRejectedNotification;
use App\Support\UserWantsNotification;

class SendClaimRejectedNotification
{
    public function handle(ClaimRejected $event): void
    {
        $claim = $event->claim->loadMissing(['receiver.profile', 'sender', 'schedule']);
        $sender = $claim->sender;

        if ($sender === null) {
            return;
        }

        if (! UserWantsNotification::email($sender, NotificationType::ClaimRejected)) {
            return;
        }

        $sender->notify(new ClaimRejectedNotification($claim));
    }
}
