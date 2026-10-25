<?php

namespace App\Support;

use App\Enums\NotificationType;
use App\Models\User;
use App\Models\UserNotificationPreference;

class UserWantsNotification
{
    public static function email(User $user, NotificationType $type): bool
    {
        $preference = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $type->value)
            ->where('channel', 'email')
            ->first();

        if ($preference === null) {
            return true;
        }

        return $preference->is_enabled;
    }
}
