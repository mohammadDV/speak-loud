<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InvalidateUserSessions
{
    /**
     * Rotate remember token, bump session version, and delete persisted DB sessions.
     */
    public static function all(User $user): void
    {
        self::bump($user);
        self::deleteDatabaseSessions($user);
    }

    /**
     * Invalidate every other session while keeping the current request valid.
     */
    public static function others(User $user): void
    {
        self::bump($user);
        AuthSession::syncVersion($user);
        self::deleteDatabaseSessions($user, exceptSessionId: session()->getId());
    }

    private static function bump(User $user): void
    {
        $user->forceFill([
            'remember_token' => Str::random(60),
            'session_version' => ((int) $user->session_version) + 1,
        ])->save();
    }

    private static function deleteDatabaseSessions(User $user, ?string $exceptSessionId = null): void
    {
        if (! self::sessionsTableExists()) {
            return;
        }

        $table = config('session.table', 'sessions');
        $query = DB::table($table)->where('user_id', $user->id);

        if ($exceptSessionId !== null && $exceptSessionId !== '') {
            $query->where('id', '!=', $exceptSessionId);
        }

        $query->delete();
    }

    private static function sessionsTableExists(): bool
    {
        return once(function (): bool {
            return Schema::hasTable(config('session.table', 'sessions'));
        });
    }
}
