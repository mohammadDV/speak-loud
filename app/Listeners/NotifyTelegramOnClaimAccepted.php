<?php

namespace App\Listeners;

use App\Events\ClaimAccepted;
use App\Support\FormatsClaimTelegramMessage;
use App\Support\TelegramNotifier;

class NotifyTelegramOnClaimAccepted
{
    public function handle(ClaimAccepted $event): void
    {
        TelegramNotifier::notify(FormatsClaimTelegramMessage::for('accepted', $event->claim));
    }
}
