<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\ClaimWithdrawn;
use App\Notifications\ClaimWithdrawnNotification;
use App\Support\UserWantsNotification;

class SendClaimWithdrawnNotification
{
    public function handle(ClaimWithdrawn $event): void
    {
        $claim = $event->claim->loadMissing(['sender.profile', 'receiver', 'schedule']);
        $receiver = $claim->receiver;

        if ($receiver === null) {
            return;
        }

        if (! UserWantsNotification::email($receiver, NotificationType::ClaimWithdrawn)) {
            return;
        }

        $receiver->notify(new ClaimWithdrawnNotification($claim));
    }
}
