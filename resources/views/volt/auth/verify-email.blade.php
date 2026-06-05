<?php

use App\Support\Seo;
use function Livewire\Volt\{state, mount, title};
use Illuminate\Support\Facades\RateLimiter;

mount(function () {
    if (auth()->user()?->hasVerifiedEmail()) {
        $this->redirect(route('profile.edit'), navigate: true);
    }

    Seo::share([
        'seoTitle'       => 'Verify email',
        'seoDescription' => 'Verify your SpeakLoud email address to unlock your account.',
        'seoUrl'         => route('verification.notice'),
        'seoRobots'      => 'noindex, nofollow',
    ]);
});

title(fn () => Seo::pageTitle('Verify email'));

state(['status' => '']);

$resend = function () {
    $user = auth()->user();

    if ($user->hasVerifiedEmail()) {
        $this->redirect(route('profile.edit'), navigate: true);

        return;
    }

    $key = 'email-verification:'.$user->id;

    if (RateLimiter::tooManyAttempts($key, 3)) {
        $this->addError('email', 'Too many resend attempts. Please wait a few minutes and try again.');

        return;
    }

    RateLimiter::hit($key, 300);

    $user->sendEmailVerificationNotification();
    $this->status = 'verification-link-sent';
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0] px-4">
    <div class="w-full max-w-md">
        <div class="flex justify-end mb-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm">
                    Log out
                </flux:button>
            </form>
        </div>

        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoud</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Verify your email address</p>
        </div>

        <flux:card class="bg-[#FFF0E0] p-8">
            <p class="text-sm text-[#3D2B1F]/75 leading-relaxed">
                We sent a verification link to
                <span class="font-semibold text-[#3D2B1F]">{{ auth()->user()->email }}</span>.
                Click the link in that email to activate your account.
            </p>

            @if ($status === 'verification-link-sent')
                <div class="mt-4 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
                    A new verification link has been sent to your email.
                </div>
            @endif

            @error('email')
                <p class="mt-4 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-6 flex flex-col gap-3">
                <flux:button type="button" variant="primary" class="w-full" wire:click="resend">
                    Resend verification email
                </flux:button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" class="w-full">
                        Log out
                    </flux:button>
                </form>
            </div>

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                Wrong email? Log out and
                <a href="{{ route('register') }}" class="text-[#FF8C42] font-medium hover:underline">create a new account</a>.
            </p>
        </flux:card>
    </div>
</div>
