<?php

use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use App\Actions\RegisterUser;
use Illuminate\Support\Facades\Auth;

mount(function () {
    Seo::share([
        'seoTitle'       => 'Create account',
        'seoDescription' => 'Join SpeakLoud for free. Create your profile, publish practice slots, and find language partners.',
        'seoUrl'         => route('register'),
        'seoRobots'      => 'noindex, nofollow',
    ]);
});

title(fn () => Seo::pageTitle('Create account'));

state([
    'username'              => '',
    'email'                 => '',
    'password'              => '',
    'password_confirmation' => '',
    'accepted_terms'        => false,
]);

rules([
    'username'              => 'required|string|min:3|max:50|unique:user_profiles,username',
    'email'                 => 'required|email|unique:users,email',
    'password'              => 'required|min:8|confirmed',
    'password_confirmation' => 'required',
    'accepted_terms'        => 'accepted',
])->messages([
    'accepted_terms.accepted' => 'You must read and accept the Terms of Service to create an account.',
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

                <label class="flex items-start gap-3 text-sm text-[#3D2B1F]/75 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="accepted_terms"
                        class="mt-1 rounded border-[#3D2B1F]/25 text-[#FF8C42] focus:ring-[#FF8C42]"
                    />
                    <span>
                        I have read and agree to the
                        <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-[#FF8C42] font-semibold hover:underline">Terms of Service</a>,
                        including rules about payments, links, fraud, chat retention, and privacy.
                    </span>
                </label>
                @error('accepted_terms')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

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
