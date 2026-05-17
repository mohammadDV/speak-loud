<?php

use function Livewire\Volt\{state, mount, computed, usesPagination};
use App\Repositories\Contracts\IUserRepository;
use App\Models\Language;

usesPagination();

state([
    'search'       => '',
    'language_id'  => '',
    'level'        => '',
    'country_code' => '',
    'languages'    => [],
]);

mount(function () {
    $this->languages = Language::where('is_active', true)->orderBy('name_en')->get();
});

$partners = computed(function () {
    return app(IUserRepository::class)->searchPartners([
        'search'       => $this->search,
        'language_id'  => $this->language_id ?: null,
        'level'        => $this->level ?: null,
        'country_code' => $this->country_code ?: null,
        'page'         => $this->getPage(),
    ], auth()->id());
});

$resetPageOnFilter = function () {
    $this->resetPage();
};

$updatedSearch = $resetPageOnFilter;
$updatedLanguageId = $resetPageOnFilter;
$updatedLevel = $resetPageOnFilter;
$updatedCountryCode = $resetPageOnFilter;

?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex gap-8">
        <aside class="w-64 shrink-0">
            <flux:fieldset>
                <flux:legend>Filters</flux:legend>

                <div class="space-y-4 mt-4">
                    <flux:input wire:model.live.debounce.400ms="search"
                        placeholder="Name, bio, tag..." label="Search" />

                    <flux:select wire:model.live="language_id" label="Language">
                        <flux:select.option value="">Any language</flux:select.option>
                        @foreach ($languages as $lang)
                            <flux:select.option value="{{ $lang->id }}">{{ $lang->name_en }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="level" label="Level">
                        <flux:select.option value="">Any level</flux:select.option>
                        <flux:select.option value="beginner">A1 – Beginner</flux:select.option>
                        <flux:select.option value="elementary">A2 – Elementary</flux:select.option>
                        <flux:select.option value="intermediate">B1 – Intermediate</flux:select.option>
                        <flux:select.option value="upper_intermediate">B2 – Upper Intermediate</flux:select.option>
                        <flux:select.option value="advanced">C1 – Advanced</flux:select.option>
                        <flux:select.option value="fluent">C2 – Fluent</flux:select.option>
                    </flux:select>
                </div>
            </flux:fieldset>
        </aside>

        <main class="flex-1">
            <h1 class="text-2xl font-bold text-[#3D2B1F] mb-6">Find a partner</h1>

            @if ($this->partners->isEmpty())
                <p class="text-[#3D2B1F]/50 text-center py-16">No partners match your filters.</p>
            @else
                <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach ($this->partners as $partner)
                        <flux:card class="bg-[#FFF0E0] hover:shadow-md transition-shadow cursor-pointer">
                            <div class="h-16 rounded-t-lg mb-3" style="background: linear-gradient(135deg, #FF8C42, #FFD166);"></div>
                            <div class="px-4 pb-4">
                                <div class="w-12 h-12 rounded-full bg-[#FF8C42] flex items-center justify-center text-white font-bold text-lg -mt-10 mb-2 border-2 border-[#FFF0E0]">
                                    {{ strtoupper(substr($partner->profile->display_name ?? '?', 0, 1)) }}
                                </div>
                                <p class="font-semibold text-[#3D2B1F]">{{ $partner->profile->display_name }}</p>
                                <p class="text-xs text-[#3D2B1F]/50">{{ $partner->profile->nationality }}</p>
                            </div>
                        </flux:card>
                    @endforeach
                </div>

                <div class="mt-8">{{ $this->partners->links() }}</div>
            @endif
        </main>
    </div>
</div>
