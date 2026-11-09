<?php

use App\Support\AuthRedirect;
use App\Support\AuthSession;
use App\Support\NormalizeEmail;
use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

mount(function () {
    Seo::share([
        'seoTitle'       => 'Sign in',
        'seoDescription' => 'Sign in to your SpeakLoudMate account to manage schedules, claims, and messages.',
        'seoUrl'         => route('login'),
        'seoRobots'      => 'noindex, nofollow',
    ]);
});

title(fn () => Seo::pageTitle('Sign in'));

state([
    'email'    => '',
    'password' => '',
]);

rules([
    'email'    => 'required|email',
    'password' => 'required',
]);

$login = function () {
    $this->validate();

    $email = NormalizeEmail::normalize($this->email);
    $throttleKey = Str::transliterate($email.'|'.request()->ip());

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->addError('email', 'Too many login attempts. Please try again in '.$seconds.' seconds.');

        return;
    }

    if (! Auth::attempt(['email' => $email, 'password' => $this->password])) {
        RateLimiter::hit($throttleKey, 60);
        $this->addError('email', 'These credentials do not match our records.');

        return;
    }

    $user = Auth::user();

    if ($user === null || $user->status !== 'active') {
        Auth::logout();
        RateLimiter::hit($throttleKey, 60);
        $this->addError('email', 'These credentials do not match our records.');

        return;
    }

    RateLimiter::clear($throttleKey);
    session()->regenerate();
    AuthSession::syncVersion($user);

    $this->redirect(AuthRedirect::afterLogin(), navigate: true);
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoudMate</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Sign in to your account</p>
        </div>

        @if (session('status') === 'password-reset')
            <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                Your password has been reset. You can sign in with your new password.
            </div>
        @endif

        @if (session('oauth_status'))
            <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                {{ session('oauth_status') }}
            </div>
        @endif

        @if (session('oauth_error'))
            <div class="mb-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm ring-1 ring-red-200">
                {{ session('oauth_error') }}
            </div>
        @endif

        <flux:card class="bg-[#FFF0E0] p-8">
            <form wire:submit="login" class="space-y-5">
                <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />
                <flux:input wire:model="password" label="Password" type="password" placeholder="••••••••" />

                <div class="text-right">
                    <a href="{{ route('password.request') }}" class="text-sm text-[#FF8C42] font-medium hover:underline">
                        Forgot password?
                    </a>
                </div>

                <flux:button type="submit" variant="primary" class="w-full">
                    Sign in
                </flux:button>
            </form>

            <x-google-oauth-button class="mt-5" label="Continue with Google" />

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                No account?
                <a href="{{ route('register') }}" class="text-[#FF8C42] font-medium">Create one</a>
            </p>
        </flux:card>
    </div>
</div>
