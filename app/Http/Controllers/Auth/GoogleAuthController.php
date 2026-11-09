<?php

namespace App\Http\Controllers\Auth;

use App\Actions\HandleGoogleCallback;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        $driver = Socialite::driver('google');

        if (! $driver instanceof AbstractProvider) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Google sign-in is not available right now.');
        }

        return $driver
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(HandleGoogleCallback $action): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Google sign-in expired. Please try again.');
        } catch (Throwable) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Google sign-in failed. Please try again or use email and password.');
        }

        if (! $googleUser instanceof SocialiteUser) {
            return redirect()
                ->route('login')
                ->with('oauth_error', 'Google sign-in failed. Please try again or use email and password.');
        }

        $result = $action->execute($googleUser);

        if (is_array($result)) {
            return redirect()->route('auth.google.complete');
        }

        return $result;
    }
}
