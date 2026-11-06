<?php

namespace App\Support;

use App\Models\Claim;
use App\Models\User;

class FormatsClaimTelegramMessage
{
    public static function for(string $action, Claim $claim): string
    {
        $claim->loadMissing(['sender.profile', 'receiver.profile']);

        $sender = self::labelFor($claim->sender);
        $receiver = self::labelFor($claim->receiver);
        $schedule = $claim->schedule_id !== null ? " (schedule #{$claim->schedule_id})" : '';

        return "Claim {$action}: #{$claim->id} from @{$sender} → @{$receiver}{$schedule}";
    }

    private static function labelFor(User $user): string
    {
        $profile = $user->profile;

        if ($profile !== null && $profile->username !== '') {
            return $profile->username;
        }

        if ($user->email !== '') {
            return $user->email;
        }

        return 'unknown';
    }
}
