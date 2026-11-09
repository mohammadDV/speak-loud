<?php

namespace App\Actions;

use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\IUserRepository;
use App\Support\Legal;
use App\Support\NormalizeEmail;
use App\Support\ProfileSlug;
use App\Support\TelegramNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegisterUser
{
    public function __construct(private readonly IUserRepository $users) {}

    public function execute(array $data): User
    {
        $username = $data['username'];
        $email = NormalizeEmail::normalize($data['email'] ?? null);

        if ($email === '') {
            throw ValidationException::withMessages([
                'email' => 'A valid email address is required.',
            ]);
        }

        if (ProfileSlug::isReserved($username)) {
            throw ValidationException::withMessages([
                'username' => 'This profile link is reserved. Please choose another.',
            ]);
        }

        try {
            return DB::transaction(function () use ($data, $username, $email): User {
                $user = $this->users->create([
                    'uuid' => Str::uuid(),
                    'email' => $email,
                    'password' => Hash::make($data['password']),
                    'terms_accepted_at' => now(),
                    'terms_version' => Legal::termsVersion(),
                ]);

                UserProfile::create([
                    'user_id' => $user->id,
                    'username' => $username,
                    'profile_slug' => $username,
                    'display_name' => $data['display_name'] ?? $username,
                ]);

                TelegramNotifier::notify("New registration: #{$user->id} @{$username} ({$user->email})");

                return $user;
            });
        } catch (UniqueConstraintViolationException $e) {
            $message = 'Unable to create this account. Please try a different email or username.';
            $field = 'email';

            if (str_contains(strtolower($e->getMessage()), 'username')
                || str_contains(strtolower($e->getMessage()), 'profile_slug')) {
                $message = 'That username is already taken. Please choose another.';
                $field = 'username';
            } elseif (str_contains(strtolower($e->getMessage()), 'email')) {
                $message = 'An account with this email already exists.';
                $field = 'email';
            }

            throw ValidationException::withMessages([
                $field => $message,
            ]);
        }
    }
}
