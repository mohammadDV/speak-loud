<?php

use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use Illuminate\Support\Facades\Auth;

mount(function () {
    Seo::share([
        'seoTitle'       => 'Sign in',
        'seoDescription' => 'Sign in to your SpeakLoud account to manage schedules, claims, and messages.',
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

    if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
        $this->addError('email', 'These credentials do not match our records.');
        return;
    }

    session()->regenerate();

    if ($pendingDirect = session('pending_direct_claim')) {
        $this->redirect(route('users.show', $pendingDirect['profile_slug']), navigate: true);

        return;
    }

    $redirect = session()->pull('pending_claim_return', route('discover'));
    $this->redirect($redirect, navigate: true);
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoud</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Sign in to your account</p>
        </div>

        @if (session('status') === 'password-reset')
            <div class="mb-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                Your password has been reset. You can sign in with your new password.
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

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                No account?
                <a href="{{ route('register') }}" class="text-[#FF8C42] font-medium">Create one</a>
            </p>
        </flux:card>
    </div>
</div>
