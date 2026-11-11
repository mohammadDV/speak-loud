<?php

use App\Actions\CompleteGoogleRegistration;
use App\Actions\HandleGoogleCallback;
use App\Support\AuthRedirect;
use App\Support\ProfileSlug;
use App\Support\Seo;
use function Livewire\Volt\{state, rules, mount, title};
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

mount(function () {
    Seo::share([
        'seoTitle' => 'Complete your profile',
        'seoDescription' => 'Choose a username to finish creating your SpeakLoudMate account.',
        'seoUrl' => route('auth.google.complete'),
        'seoRobots' => 'noindex, nofollow',
    ]);

    if (HandleGoogleCallback::pendingPayload() === null) {
        $this->redirect(route('login'), navigate: true);
    }
});

title(fn () => Seo::pageTitle('Complete your profile'));

state([
    'username' => '',
    'accepted_terms' => false,
]);

rules([
    'username' => [
        'required',
        'string',
        'min:3',
        'max:50',
        'regex:/^[a-zA-Z0-9]+$/',
        'unique:user_profiles,username',
        function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && ProfileSlug::isReserved($value)) {
                $fail('This profile link is reserved. Please choose another.');
            }
        },
    ],
    'accepted_terms' => 'accepted',
])->messages([
    'accepted_terms.accepted' => 'You must read and accept the Terms of Service to create an account.',
]);

$complete = function (CompleteGoogleRegistration $action) {
    $throttleKey = 'google-complete|'.request()->ip();

    if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
        $seconds = RateLimiter::availableIn($throttleKey);
        $this->addError('username', 'Too many attempts. Please try again in '.$seconds.' seconds.');

        return;
    }

    $pending = HandleGoogleCallback::pendingPayload();

    if ($pending === null) {
        $this->redirect(route('login'), navigate: true);

        return;
    }

    try {
        $this->validate();
        $action->execute($pending, $this->username);
    } catch (ValidationException $e) {
        RateLimiter::hit($throttleKey, 60);
        $this->setErrorBag($e->validator->getMessageBag());

        return;
    }

    RateLimiter::clear($throttleKey);
    $this->redirect(AuthRedirect::afterLogin(), navigate: true);
};

?>

<div class="min-h-screen flex items-center justify-center bg-[#FFF8F0]">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="flex justify-center mb-3">
                <x-brand-logo :size="40" wordmark-class="text-3xl font-bold text-[#3D2B1F]" />
            </h1>
            <p class="text-[#3D2B1F]/60 mt-1">Finish setting up your account</p>
        </div>

        <flux:card class="bg-[#FFF0E0] p-8">
            <p class="text-sm text-[#3D2B1F]/70 mb-5">
                Choose a username for your public profile link. Your Google email is already verified.
            </p>

            <form wire:submit="complete" class="space-y-5">
                <flux:input wire:model="username" label="Username" placeholder="speakloudmate.com/ alex" />

                <label class="flex items-start gap-3 text-sm text-[#3D2B1F]/75 cursor-pointer">
                    <input
                        type="checkbox"
                        wire:model="accepted_terms"
                        class="mt-1 rounded border-[#3D2B1F]/25 text-[#FF8C42] focus:ring-[#FF8C42]"
                    />
                    <span>
                        I have read and agree to the
                        <a href="{{ route('terms') }}" target="_blank" rel="noopener noreferrer" class="text-[#FF8C42] font-semibold hover:underline">Terms of Service</a>,
                        including rules, and privacy policy.
                    </span>
                </label>
                @error('accepted_terms')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror

                <flux:button type="submit" variant="primary" class="w-full">
                    Continue
                </flux:button>
            </form>

            <p class="text-center text-sm text-[#3D2B1F]/60 mt-6">
                <a href="{{ route('login') }}" class="text-[#FF8C42] font-medium">Cancel</a>
            </p>
        </flux:card>
    </div>
</div>
