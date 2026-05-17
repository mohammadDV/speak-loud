<?php

use function Livewire\Volt\{state, rules};
use App\Actions\RegisterUser;
use Illuminate\Support\Facades\Auth;

state([
    'username'     => '',
    'email'        => '',
    'password'     => '',
    'password_confirmation' => '',
]);

rules([
    'username'              => 'required|string|min:3|max:50|unique:user_profiles,username',
    'email'                 => 'required|email|unique:users,email',
    'password'              => 'required|min:8|confirmed',
    'password_confirmation' => 'required',
]);

$register = function (RegisterUser $action) {
    $this->validate();

    $user = $action->execute([
        'username' => $this->username,
        'email'    => $this->email,
        'password' => $this->password,
    ]);

    Auth::login($user);
    session()->regenerate();
    $this->redirect(route('profile.edit'), navigate: true);
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-[#3D2B1F]">SpeakLoud</h1>
            <p class="text-[#3D2B1F]/60 mt-1">Create your account</p>
        </div>

        <flux:card class="bg-[#FFF0E0] p-8">
            <form wire:submit="register" class="space-y-5">
                <flux:input wire:model="username" label="Username" placeholder="speakloud.app/ alex" />
                <flux:input wire:model="email" label="Email" type="email" placeholder="you@example.com" />
                <flux:input wire:model="password" label="Password" type="password" placeholder="Min. 8 characters" />
                <flux:input wire:model="password_confirmation" label="Confirm Password" type="password" placeholder="Repeat password" />

                <flux:button type="submit" variant="primary" class="w-full">
                    Create account
                </flux:button>
            </form>

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                Already have an account?
                <a href="{{ route('login') }}" class="text-[#FF8C42] font-medium">Sign in</a>
            </p>
        </flux:card>
    </div>
</div>
