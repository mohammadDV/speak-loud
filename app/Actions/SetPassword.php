<?php

namespace App\Actions;

use App\Models\User;
use App\Support\InvalidateUserSessions;
use App\Support\TelegramNotifier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SetPassword
{
    public function execute(User $user, string $newPassword): void
    {
        if ($user->hasPassword()) {
            throw ValidationException::withMessages([
                'password' => 'Your account already has a password. Use change password instead.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        if (Auth::user()?->is($user)) {
            InvalidateUserSessions::others($user);

            session()->put([
                'password_hash_'.Auth::getDefaultDriver() => Auth::user()->getAuthPassword(),
            ]);
        }

        TelegramNotifier::notify("Password set: user #{$user->id} ({$user->email})");
    }
}
