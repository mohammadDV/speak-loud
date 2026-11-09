<?php

namespace App\Actions;

use App\Models\SocialAccount;
use App\Models\User;
use App\Models\UserProfile;
use App\Repositories\Contracts\IUserRepository;
use App\Support\AuthSession;
use App\Support\Legal;
use App\Support\NormalizeEmail;
use App\Support\ProfileSlug;
use App\Support\TelegramNotifier;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteGoogleRegistration
{
    public function __construct(private readonly IUserRepository $users) {}

    /**
     * @param  array{provider_id: string, email: string, name: ?string}  $pending
     */
    public function execute(array $pending, string $username): User
    {
        if (ProfileSlug::isReserved($username)) {
            throw ValidationException::withMessages([
                'username' => 'This profile link is reserved. Please choose another.',
            ]);
        }

        $providerId = $pending['provider_id'];
        $email = NormalizeEmail::normalize($pending['email']);

        if ($providerId === '' || $email === '') {
            throw ValidationException::withMessages([
                'username' => 'Your Google sign-in session expired. Please try again.',
            ]);
        }

        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'username' => 'An account with this email already exists. Please sign in instead.',
            ]);
        }

        if (SocialAccount::query()
            ->where('provider', SocialAccount::PROVIDER_GOOGLE)
            ->where('provider_id', $providerId)
            ->exists()) {
            throw ValidationException::withMessages([
                'username' => 'This Google account is already linked. Please sign in instead.',
            ]);
        }

        $displayName = $pending['name'] ?? null;
        if (! is_string($displayName) || trim($displayName) === '') {
            $displayName = $username;
        } else {
            $displayName = mb_substr(trim($displayName), 0, 100);
        }

        try {
            $user = DB::transaction(function () use ($username, $email, $providerId, $displayName): User {
                $user = $this->users->create([
                    'uuid' => Str::uuid(),
                    'email' => $email,
                    'password' => null,
                    'terms_accepted_at' => now(),
                    'terms_version' => Legal::termsVersion(),
                ]);

                $user->forceFill([
                    'email_verified_at' => now(),
                ])->save();

                UserProfile::create([
                    'user_id' => $user->id,
                    'username' => $username,
                    'profile_slug' => $username,
                    'display_name' => $displayName,
                ]);

                $user->socialAccounts()->create([
                    'provider' => SocialAccount::PROVIDER_GOOGLE,
                    'provider_id' => $providerId,
                    'email' => $email,
                ]);

                TelegramNotifier::notify("New Google registration: #{$user->id} @{$username} ({$user->email})");

                return $user;
            });
        } catch (UniqueConstraintViolationException $e) {
            $message = 'Unable to finish registration. Please try again.';

            if (str_contains(strtolower($e->getMessage()), 'username')
                || str_contains(strtolower($e->getMessage()), 'profile_slug')) {
                $message = 'That username is already taken. Please choose another.';
            } elseif (str_contains(strtolower($e->getMessage()), 'email')) {
                $message = 'An account with this email already exists. Please sign in instead.';
            }

            throw ValidationException::withMessages([
                'username' => $message,
            ]);
        }

        Auth::login($user, remember: false);
        AuthSession::syncVersion($user);
        session()->forget(HandleGoogleCallback::PENDING_SESSION_KEY);

        return $user;
    }
}
