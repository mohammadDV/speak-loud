<?php

namespace App\Actions;

use App\Models\SocialAccount;
use App\Models\User;
use App\Support\AuthRedirect;
use App\Support\AuthSession;
use App\Support\InvalidateUserSessions;
use App\Support\NormalizeEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Two\User as SocialiteUser;

class HandleGoogleCallback
{
    public const PENDING_SESSION_KEY = 'google_oauth_pending';

    public const PENDING_TTL_SECONDS = 900;

    /**
     * @return RedirectResponse|array{status: 'pending'}
     */
    public function execute(SocialiteUser $googleUser): RedirectResponse|array
    {
        $email = NormalizeEmail::normalize($googleUser->getEmail());
        $providerId = (string) $googleUser->getId();

        if ($email === '' || $providerId === '') {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Google did not return a valid email address. Please try again or register with email.');
        }

        if (! $this->isGoogleEmailVerified($googleUser)) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Your Google email address is not verified. Please verify it with Google or register with email.');
        }

        $account = SocialAccount::query()
            ->with('user')
            ->where('provider', SocialAccount::PROVIDER_GOOGLE)
            ->where('provider_id', $providerId)
            ->first();

        if ($account !== null) {
            $linkedUser = $account->user;

            if ($linkedUser === null) {
                $trashedLinkedUser = User::onlyTrashed()->find($account->user_id);

                if ($trashedLinkedUser !== null) {
                    return redirect()
                        ->route('login')
                        ->with('oauth_error', 'This account is unavailable. Please contact support if you need help.');
                }
            }

            return $this->loginExistingUser($linkedUser);
        }

        $trashedUser = User::onlyTrashed()->where('email', $email)->first();

        if ($trashedUser !== null) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'This account is unavailable. Please contact support if you need help.');
        }

        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser !== null) {
            if ($existingUser->status !== 'active') {
                return redirect()
                    ->route('login')
                    ->with('oauth_error', 'These credentials do not match our records.');
            }

            try {
                return DB::transaction(function () use ($existingUser, $providerId, $email, $googleUser) {
                    $existingGoogle = $existingUser->socialAccounts()
                        ->where('provider', SocialAccount::PROVIDER_GOOGLE)
                        ->lockForUpdate()
                        ->first();

                    if ($existingGoogle !== null && $existingGoogle->provider_id !== $providerId) {
                        return redirect()
                            ->route('login')
                            ->with('oauth_error', 'This email is already linked to a different Google account. Sign in with that account or use email and password.');
                    }

                    if ($existingGoogle === null) {
                        $existingUser->socialAccounts()->create([
                            'provider' => SocialAccount::PROVIDER_GOOGLE,
                            'provider_id' => $providerId,
                            'email' => $email,
                        ]);
                    } else {
                        $existingGoogle->forceFill([
                            'email' => $email,
                        ])->save();
                    }

                    $reclaimed = false;

                    if ($existingUser->email_verified_at === null) {
                        // Real Google owner reclaiming an unverified squatted account.
                        InvalidateUserSessions::all($existingUser);

                        $existingUser->forceFill([
                            'password' => null,
                            'email_verified_at' => now(),
                        ])->save();

                        $this->resetSquattedProfile($existingUser, $googleUser->getName());
                        $reclaimed = true;
                    }

                    $redirect = $this->loginExistingUser($existingUser->fresh());

                    if ($reclaimed) {
                        return $redirect->with(
                            'oauth_status',
                            'We verified this Google email and removed the previous password for security. You can set a new password in Security settings. Please review your profile details.',
                        );
                    }

                    return $redirect;
                });
            } catch (UniqueConstraintViolationException) {
                return redirect()
                    ->route('login')
                    ->with('oauth_error', 'Google sign-in could not be completed. Please try again.');
            }
        }

        self::storePending([
            'provider_id' => $providerId,
            'email' => $email,
            'name' => $googleUser->getName(),
        ]);

        return ['status' => 'pending'];
    }

    /**
     * @param  array{provider_id: string, email: string, name: ?string}  $payload
     */
    public static function storePending(array $payload): void
    {
        session()->regenerate();
        session([
            self::PENDING_SESSION_KEY => [
                ...$payload,
                'expires_at' => now()->addSeconds(self::PENDING_TTL_SECONDS)->timestamp,
            ],
        ]);
    }

    /**
     * @return array{provider_id: string, email: string, name: ?string}|null
     */
    public static function pendingPayload(): ?array
    {
        $pending = session(self::PENDING_SESSION_KEY);

        if (! is_array($pending)) {
            return null;
        }

        $expiresAt = $pending['expires_at'] ?? null;

        if (! is_numeric($expiresAt) || now()->timestamp > (int) $expiresAt) {
            session()->forget(self::PENDING_SESSION_KEY);

            return null;
        }

        $providerId = $pending['provider_id'] ?? null;
        $email = $pending['email'] ?? null;

        if (! is_string($providerId) || $providerId === '' || ! is_string($email) || $email === '') {
            session()->forget(self::PENDING_SESSION_KEY);

            return null;
        }

        return [
            'provider_id' => $providerId,
            'email' => $email,
            'name' => is_string($pending['name'] ?? null) ? $pending['name'] : null,
        ];
    }

    private function resetSquattedProfile(User $user, mixed $googleName): void
    {
        $profile = $user->profile;

        if ($profile === null) {
            return;
        }

        $displayName = is_string($googleName) ? trim($googleName) : '';
        if ($displayName === '') {
            $displayName = $profile->username;
        } else {
            $displayName = mb_substr($displayName, 0, 100);
        }

        $profile->forceFill([
            'display_name' => $displayName,
            'bio' => null,
            'gender' => null,
            'birthdate' => null,
            'nationality' => null,
            'country_code' => null,
            'profile_image_path' => null,
            'background_image_path' => null,
        ])->save();
    }

    private function isGoogleEmailVerified(SocialiteUser $googleUser): bool
    {
        $value = data_get($googleUser->user, 'email_verified', false);

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes'], true);
        }

        return false;
    }

    private function loginExistingUser(?User $user): RedirectResponse
    {
        if ($user === null || $user->status !== 'active') {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with('oauth_error', 'These credentials do not match our records.');
        }

        Auth::login($user, remember: $user->hasPassword());
        AuthSession::syncVersion($user);
        session()->forget(self::PENDING_SESSION_KEY);

        return redirect()->to(AuthRedirect::afterLogin());
    }
}
