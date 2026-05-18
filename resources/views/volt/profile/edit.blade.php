<?php

use App\Support\CountryCodes;
use function Livewire\Volt\{state, mount, rules};
use App\Models\Language;
use App\Models\Interest;

state([
    'display_name' => '',
    'username'     => '',
    'bio'          => '',
    'country_code' => '',
    'languages'    => [],
    'interests'    => [],
]);

mount(function () {
    $user = auth()->user()->load(['profile', 'tags']);
    if ($user->profile) {
        $this->display_name = $user->profile->display_name;
        $this->username     = $user->profile->username;
        $this->bio          = $user->profile->bio ?? '';
        $this->country_code = $user->profile->country_code ?? '';
    }
    $this->languages = Language::where('is_active', true)->orderBy('name_en')->get();
    $this->interests = Interest::orderBy('name_en')->get();
});

rules([
    'display_name' => 'required|string|max:100',
    'bio'          => 'nullable|string|max:500',
    'country_code' => 'nullable|string|size:2|in:'.implode(',', array_keys(CountryCodes::LIST)),
]);

$save = function () {
    $this->validate();

    $user = auth()->user();
    if ($user->profile) {
        $user->profile->update([
            'display_name' => $this->display_name,
            'bio'          => $this->bio,
            'country_code' => $this->country_code ?: null,
            'nationality'  => null,
        ]);
    }

    session()->flash('saved', true);
};

?>

<div class="max-w-xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Edit profile</h1>
            <p class="text-sm text-[#3D2B1F]/50 mt-1">This is what other learners see on Discover.</p>
        </div>
        <a href="{{ route('profile') }}" class="text-sm text-[#FF8C42] hover:underline shrink-0">← Profile</a>
    </div>

    @if (session('saved'))
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">Profile saved!</div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <flux:input wire:model="display_name" label="Display name" placeholder="Alex" />

        <flux:input wire:model="username" label="Username" disabled
            description="Username cannot be changed after registration." />

        <flux:select wire:model="country_code" label="Country" placeholder="Select your country">
            <flux:select.option value="">Not set</flux:select.option>
            @foreach (CountryCodes::options() as $code => $name)
                <flux:select.option value="{{ $code }}">{{ $name }} ({{ $code }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea wire:model="bio" label="Bio" rows="4"
            placeholder="Engineer learning English. Movies, sci-fi, climbing." />

        <div class="flex gap-3 pt-2">
            <flux:button type="submit" variant="primary">Save profile</flux:button>
            <flux:button href="{{ route('profile') }}" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
