<?php

use App\Support\PasswordRules;
use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

mount(function (string $token) {
    Seo::share([
        'seoTitle'       => 'Reset password',
        'seoDescription' => 'Choose a new password for your SpeakLoud account.',
        'seoUrl'         => route('password.reset', ['token' => $token]),
        'seoRobots'      => 'noindex, nofollow',
    ]);

    $this->token = $token;
    $this->email = request()->query('email', '');
});

title(fn () => Seo::pageTitle('Reset password'));

state([
    'token'                 => '',
    'email'                 => '',
    'password'              => '',
    'password_confirmation' => '',
]);

rules([
    'token'                 => 'required',
    'email'                 => 'required|email',
    'password'              => PasswordRules::validationRules(),
    'password_confirmation' => 'required',
]);

$resetPassword = function () {
    $this->validate();

    $status = Password::reset(
        [
            'email'                 => $this->email,
            'password'              => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'token'                 => $this->token,
        ],
        function ($user, $password) {
            $user->forceFill([
                'password'       => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        session()->flash('status', 'password-reset');
        $this->redirect(route('login'), navigate: true);

        return;
    }

    $this->addError('email', __($status));
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoud</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Choose a new password</p>
        </div>

        <flux:card class="bg-[#FFF0E0] p-8">
            <form wire:submit="resetPassword" class="space-y-5">
                <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />
                <div class="space-y-2">
                    <flux:input wire:model="password" label="New password" type="password" placeholder="Create a strong password" />
                    <x-password-requirements />
                </div>
                <flux:input wire:model="password_confirmation" label="Confirm new password" type="password" placeholder="Repeat password" />

                <flux:button type="submit" variant="primary" class="w-full">
                    Reset password
                </flux:button>
            </form>

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                <a href="{{ route('login') }}" class="text-[#FF8C42] font-medium">Back to sign in</a>
            </p>
        </flux:card>
    </div>
</div>
