<?php

namespace App\Listeners;

use App\Enums\NotificationType;
use App\Events\ClaimSent;
use App\Notifications\ClaimReceivedNotification;
use App\Support\UserWantsNotification;

class SendClaimSentNotification
{
    public function handle(ClaimSent $event): void
    {
        $claim = $event->claim->loadMissing(['sender.profile', 'receiver', 'schedule']);
        $receiver = $claim->receiver;

        if ($receiver === null) {
            return;
        }

        if (! UserWantsNotification::email($receiver, NotificationType::ClaimReceived)) {
            return;
        }

        $receiver->notify(new ClaimReceivedNotification($claim));
    }
}
