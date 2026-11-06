<?php

namespace App\Listeners;

use App\Events\ClaimRejected;
use App\Support\FormatsClaimTelegramMessage;
use App\Support\TelegramNotifier;

class NotifyTelegramOnClaimRejected
{
    public function handle(ClaimRejected $event): void
    {
        TelegramNotifier::notify(FormatsClaimTelegramMessage::for('rejected', $event->claim));
    }
}
