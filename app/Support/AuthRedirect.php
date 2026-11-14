<?php

namespace App\Support;

class AuthRedirect
{
    /**
     * Destination after a successful login (password or OAuth).
     */
    public static function afterLogin(): string
    {
        if ($pendingDirect = session('pending_direct_claim')) {
            $profileSlug = $pendingDirect['profile_slug'] ?? null;

            if (is_string($profileSlug) && $profileSlug !== '') {
                return route('users.show', $profileSlug);
            }
        }

        $pendingReturn = session()->pull('pending_claim_return')
            ?? session()->pull('pending_learn_return');

        return SafeInternalRedirect::url(
            $pendingReturn,
            route('discover'),
        );
    }
}
