<?php

use App\Actions\ChangePassword;
use App\Actions\SetPassword;
use App\Support\NormalizeEmail;
use App\Support\PasswordRules;
use App\Support\Seo;
use function Livewire\Volt\{state, mount, title, computed};
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

mount(function () {
    Seo::share([
        'seoTitle' => 'Security',
        'seoDescription' => 'Manage your SpeakLoudMate account security.',
        'seoUrl' => route('profile.security'),
        'seoRobots' => 'noindex, nofollow',
    ]);

    if (! auth()->user()->hasVerifiedEmail()) {
        $this->redirect(route('verification.notice'), navigate: true);
    }
});

title(fn () => Seo::pageTitle('Security'));

state([
    'email_confirmation'    => '',
    'current_password'      => '',
    'password'              => '',
    'password_confirmation' => '',
    'status'                => '',
]);

$hasPassword = computed(fn () => auth()->user()->hasPassword());
$hasGoogleAccount = computed(fn () => auth()->user()->hasGoogleAccount());

$updatePassword = function (ChangePassword $changePassword, SetPassword $setPassword) {
    $user = auth()->user();
    $throttleKey = 'password-change|'.$user->id;
    $errorKey = $user->hasPassword() ? 'current_password' : 'email_confirmation';

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->addError($errorKey, 'Too many password change attempts. Please try again in '.$seconds.' seconds.');

        return;
    }

    try {
        if ($user->hasPassword()) {
            $this->validate([
                'current_password' => ['required', 'current_password'],
                'password' => PasswordRules::validationRules(),
                'password_confirmation' => ['required'],
            ]);

            $changePassword->execute($user, $this->current_password, $this->password);
            $this->status = 'password-changed';
        } else {
            $this->validate([
                'email_confirmation' => ['required', 'email'],
                'password' => PasswordRules::validationRules(),
                'password_confirmation' => ['required'],
            ]);

            if (NormalizeEmail::normalize($this->email_confirmation) !== NormalizeEmail::normalize($user->email)) {
                throw ValidationException::withMessages([
                    'email_confirmation' => 'Enter the email address on this account to confirm.',
                ]);
            }

            $setPassword->execute($user, $this->password);
            $this->status = 'password-set';
        }
    } catch (ValidationException $e) {
        RateLimiter::hit($throttleKey, 60);
        $this->setErrorBag($e->validator->getMessageBag());

        return;
    }

    RateLimiter::clear($throttleKey);
    $this->reset(['email_confirmation', 'current_password', 'password', 'password_confirmation']);
};

?>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="mb-8 space-y-6">
        <div class="hidden md:block">
            <x-profile-nav />
        </div>

        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Security</h1>
            <p class="text-sm text-[#3D2B1F]/50 mt-1">Manage how you sign in to SpeakLoudMate.</p>
        </div>
    </div>

    @if ($this->hasGoogleAccount)
        <flux:card class="bg-[#FFF0E0] p-6 mb-6">
            <h2 class="text-lg font-semibold text-[#3D2B1F]">Connected accounts</h2>
            <p class="mt-2 text-sm text-[#3D2B1F]/70">
                Signed in with Google. You can keep using Google, and optionally set a password below for email sign-in.
            </p>
        </flux:card>
    @endif

    @if ($status === 'password-changed')
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
            Your password has been updated.
        </div>
    @endif

    @if ($status === 'password-set')
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
            Your password has been set. You can now sign in with email and password as well.
        </div>
    @endif

    <flux:card class="bg-[#FFF0E0] p-6">
        <h2 class="text-lg font-semibold text-[#3D2B1F] mb-4">
            {{ $this->hasPassword ? 'Update password' : 'Set a password' }}
        </h2>

        <form wire:submit="updatePassword" class="space-y-5">
            @if ($this->hasPassword)
                <flux:input wire:model="current_password" label="Current password" type="password" placeholder="••••••••" />
            @else
                <flux:input
                    wire:model="email_confirmation"
                    label="Confirm your email"
                    type="email"
                    placeholder="you@example.com"
                />
                <p class="text-xs text-[#3D2B1F]/50 -mt-3">
                    Type your account email to confirm before setting a password.
                </p>
            @endif

            <div class="space-y-2">
                <flux:input
                    wire:model="password"
                    label="{{ $this->hasPassword ? 'New password' : 'Password' }}"
                    type="password"
                    placeholder="Create a strong password"
                />
                <x-password-requirements />
            </div>
            <flux:input
                wire:model="password_confirmation"
                label="{{ $this->hasPassword ? 'Confirm new password' : 'Confirm password' }}"
                type="password"
                placeholder="Repeat password"
            />

            <flux:button type="submit" variant="primary">
                {{ $this->hasPassword ? 'Update password' : 'Set password' }}
            </flux:button>
        </form>
    </flux:card>
</div>
