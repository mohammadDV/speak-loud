<?php

namespace App\Support;

use App\Models\User;

class AuthSession
{
    public const VERSION_KEY = 'auth_session_version';

    public static function syncVersion(User $user): void
    {
        session()->put(self::VERSION_KEY, (int) $user->session_version);
    }

    public static function currentVersion(): ?int
    {
        $value = session(self::VERSION_KEY);

        return is_numeric($value) ? (int) $value : null;
    }
}
