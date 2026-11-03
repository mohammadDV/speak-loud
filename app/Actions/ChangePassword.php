<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ChangePassword
{
    public function execute(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
        ])->save();

        if (Auth::id() === $user->id) {
            Auth::logoutOtherDevices($newPassword);

            // Keep the current session valid under AuthenticateSession after rehash.
            session()->put([
                'password_hash_'.Auth::getDefaultDriver() => Auth::user()->getAuthPassword(),
            ]);
        }
    }
}
