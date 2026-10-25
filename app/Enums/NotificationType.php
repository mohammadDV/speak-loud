<?php

namespace App\Enums;

enum NotificationType: string
{
    case ClaimReceived = 'claim_received';
    case ClaimAccepted = 'claim_accepted';
    case ClaimRejected = 'claim_rejected';
    case ClaimWithdrawn = 'claim_withdrawn';
}
