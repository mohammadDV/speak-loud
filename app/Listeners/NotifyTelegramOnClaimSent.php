<?php

namespace App\Listeners;

use App\Events\ClaimSent;
use App\Support\FormatsClaimTelegramMessage;
use App\Support\TelegramNotifier;

class NotifyTelegramOnClaimSent
{
    public function handle(ClaimSent $event): void
    {
        TelegramNotifier::notify(FormatsClaimTelegramMessage::for('sent', $event->claim));
    }
}
