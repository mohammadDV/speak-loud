<?php

use function Livewire\Volt\{state, mount, rules};
use App\Models\Language;
use App\Models\Interest;

state([
    'display_name' => '',
    'username'     => '',
    'bio'          => '',
    'nationality'  => '',
    'languages'    => [],
    'interests'    => [],
]);

mount(function () {
    $user = auth()->user()->load(['profile', 'tags']);
    if ($user->profile) {
        $this->display_name = $user->profile->display_name;
        $this->username     = $user->profile->username;
        $this->bio          = $user->profile->bio ?? '';
        $this->nationality  = $user->profile->nationality ?? '';
    }
    $this->languages  = Language::where('is_active', true)->orderBy('name_en')->get();
    $this->interests  = Interest::orderBy('name_en')->get();
});

rules([
    'display_name' => 'required|string|max:100',
    'bio'          => 'nullable|string|max:500',
]);

$save = function () {
    $this->validate();

    $user = auth()->user();
    if ($user->profile) {
        $user->profile->update([
            'display_name' => $this->display_name,
            'bio'          => $this->bio,
            'nationality'  => $this->nationality,
        ]);
    }

    session()->flash('saved', true);
};

?>

<div class="max-w-xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-[#3D2B1F] mb-2">Edit profile</h1>
    <p class="text-sm text-[#3D2B1F]/50 mb-8">This is what other learners see.</p>

    @if (session('saved'))
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm">Profile saved!</div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <flux:input wire:model="display_name" label="Display name" placeholder="Alex Yousefi" />
        <flux:input wire:model="nationality" label="Nationality" placeholder="Iranian" />
        <flux:textarea wire:model="bio" label="Bio" rows="4"
            placeholder="Engineer learning English. Movies, sci-fi, climbing." />

        <flux:button type="submit" variant="primary">Save profile</flux:button>
    </form>
</div>
