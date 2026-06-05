<?php

use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use Illuminate\Support\Facades\Password;

mount(function () {
    Seo::share([
        'seoTitle'       => 'Forgot password',
        'seoDescription' => 'Reset your SpeakLoud password via email.',
        'seoUrl'         => route('password.request'),
        'seoRobots'      => 'noindex, nofollow',
    ]);
});

title(fn () => Seo::pageTitle('Forgot password'));

state([
    'email'  => '',
    'status' => '',
]);

rules([
    'email' => 'required|email',
]);

$sendResetLink = function () {
    $this->validate();

    $status = Password::sendResetLink(['email' => $this->email]);

    if ($status === Password::RESET_LINK_SENT) {
        $this->status = 'reset-link-sent';
        $this->reset('email');

        return;
    }

    $this->addError('email', __($status));
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoud</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Reset your password</p>
        </div>

        <flux:card class="bg-[#FFF0E0] p-8">
            @if ($status === 'reset-link-sent')
                <div class="p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                    If an account exists for that email, we sent a password reset link. Check your inbox.
                </div>
            @else
                <p class="text-sm text-[#3D2B1F]/75 mb-5">
                    Enter your email and we will send you a link to choose a new password.
                </p>

                <form wire:submit="sendResetLink" class="space-y-5">
                    <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />

                    <flux:button type="submit" variant="primary" class="w-full">
                        Send reset link
                    </flux:button>
                </form>
            @endif

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                Remember your password?
                <a href="{{ route('login') }}" class="text-[#FF8C42] font-medium">Sign in</a>
            </p>
        </flux:card>
    </div>
</div>
