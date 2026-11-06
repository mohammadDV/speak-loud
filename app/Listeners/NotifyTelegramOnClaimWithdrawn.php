<?php

namespace App\Listeners;

use App\Events\ClaimWithdrawn;
use App\Support\FormatsClaimTelegramMessage;
use App\Support\TelegramNotifier;

class NotifyTelegramOnClaimWithdrawn
{
    public function handle(ClaimWithdrawn $event): void
    {
        TelegramNotifier::notify(FormatsClaimTelegramMessage::for('withdrawn', $event->claim));
    }
}
