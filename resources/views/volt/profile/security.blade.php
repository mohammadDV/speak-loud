<?php

use App\Actions\ChangePassword;
use function Livewire\Volt\{state, rules, mount};

mount(function () {
    if (! auth()->user()->hasVerifiedEmail()) {
        $this->redirect(route('verification.notice'), navigate: true);
    }
});

state([
    'current_password'      => '',
    'password'              => '',
    'password_confirmation' => '',
    'status'                => '',
]);

rules([
    'current_password'      => 'required|current_password',
    'password'              => 'required|min:8|confirmed',
    'password_confirmation' => 'required',
]);

$updatePassword = function (ChangePassword $action) {
    $this->validate();

    try {
        $action->execute(auth()->user(), $this->current_password, $this->password);
    } catch (\Illuminate\Validation\ValidationException $e) {
        $this->setErrorBag($e->validator->getMessageBag());

        return;
    }

    $this->reset(['current_password', 'password', 'password_confirmation']);
    $this->status = 'password-changed';
};

?>

<div class="max-w-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Security</h1>
            <p class="text-sm text-[#3D2B1F]/50 mt-1">Update your account password.</p>
        </div>
        <a href="{{ route('profile') }}" class="text-sm text-[#FF8C42] hover:underline shrink-0">← Profile</a>
    </div>

    @if ($status === 'password-changed')
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">
            Your password has been updated.
        </div>
    @endif

    <flux:card class="bg-[#FFF0E0] p-6">
        <form wire:submit="updatePassword" class="space-y-5">
            <flux:input wire:model="current_password" label="Current password" type="password" placeholder="••••••••" />
            <flux:input wire:model="password" label="New password" type="password" placeholder="Min. 8 characters" />
            <flux:input wire:model="password_confirmation" label="Confirm new password" type="password" placeholder="Repeat password" />

            <flux:button type="submit" variant="primary">Update password</flux:button>
        </form>
    </flux:card>
</div>
