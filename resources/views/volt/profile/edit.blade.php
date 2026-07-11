<?php

use App\Support\CountryCodes;
use App\Support\ProfileSlug;
use App\Support\UserLanguageLevels;
use function Livewire\Volt\{state, mount, rules, usesFileUploads};
use App\Models\Language;
use App\Models\Interest;
use App\Services\Uploads\UserImageUploadService;

usesFileUploads();

state([
    'display_name'          => '',
    'profile_slug'          => '',
    'bio'                   => '',
    'country_code'          => '',
    'is_private'            => false,
    'selected_interest_ids' => [],
    'userLanguages'         => [],
    'allLanguages'          => [],
    'allInterests'          => [],
    'profile_image'         => null,
    'background_image'      => null,
    'current_profile_image_url' => null,
    'current_background_image_url' => null,
]);

mount(function () {
    $user = auth()->user()->load(['profile', 'interests', 'languages']);
    if ($user->profile) {
        $this->display_name = $user->profile->display_name;
        $this->profile_slug = $user->profile->profile_slug ?? $user->profile->username;
        $this->bio          = $user->profile->bio ?? '';
        $this->country_code = $user->profile->country_code ?? '';
        $this->is_private   = (bool) $user->profile->is_private;
        $this->current_profile_image_url = $user->profile->profileImageUrl();
        $this->current_background_image_url = $user->profile->backgroundImageUrl();
    }
    $this->allLanguages = Language::where('is_active', true)->orderBy('name_en')->get();
    $this->allInterests = Interest::orderBy('name_en')->get();
    $this->selected_interest_ids = $user->interests
        ->pluck('id')
        ->map(fn ($id) => (string) $id)
        ->all();
    $this->userLanguages = $user->languages
        ->map(fn ($entry) => [
            'language_id' => (string) $entry->language_id,
            'type'        => $entry->type,
            'level'       => $entry->level ?? '',
        ])
        ->values()
        ->all();
});

rules(function () {
    $profileId = auth()->user()?->profile?->id;

    return [
        'display_name' => 'required|string|max:100',
        'profile_slug' => ProfileSlug::validationRules($profileId),
        'bio'          => 'nullable|string|max:500',
        'country_code' => 'nullable|string|size:2|in:'.implode(',', array_keys(CountryCodes::LIST)),
        'is_private'              => 'boolean',
        'selected_interest_ids'   => 'nullable|array|max:10',
        'selected_interest_ids.*' => 'integer|exists:interests,id',
        'userLanguages'           => 'nullable|array|max:10',
        'userLanguages.*.language_id' => 'required|integer|exists:languages,id',
        'userLanguages.*.type'        => 'required|in:'.implode(',', UserLanguageLevels::TYPES),
        'userLanguages.*.level'       => 'required|in:'.implode(',', UserLanguageLevels::LEVELS),
        'profile_image'           => 'nullable|image|max:4096',
        'background_image'        => 'nullable|image|max:6144',
    ];
});

$addLanguageRow = function () {
    if (count($this->userLanguages) >= 10) {
        return;
    }

    $this->userLanguages[] = [
        'language_id' => '',
        'type'        => 'learning',
        'level'       => '',
    ];
};

$removeLanguageRow = function (int $index) {
    unset($this->userLanguages[$index]);
    $this->userLanguages = array_values($this->userLanguages);
};

$save = function (UserImageUploadService $uploader) {
    $this->validate();

    $seen = [];
    foreach ($this->userLanguages ?? [] as $index => $entry) {
        $key = ($entry['language_id'] ?? '').':'.($entry['type'] ?? '');
        if (isset($seen[$key])) {
            $this->addError('userLanguages', 'Each language can only appear once per type (native or learning).');

            return;
        }
        $seen[$key] = $index;
    }

    $user = auth()->user();
    if ($user->profile) {
        $profile = $user->profile;

        $update = [
            'display_name' => $this->display_name,
            'profile_slug' => strtolower($this->profile_slug),
            'bio'          => $this->bio,
            'country_code' => $this->country_code ?: null,
            'nationality'  => null,
            'is_private'   => (bool) $this->is_private,
        ];

        if ($this->profile_image) {
            $update['profile_image_path'] = $uploader->uploadProfileImage(
                $this->profile_image,
                $profile->profile_image_path
            );
        }

        if ($this->background_image) {
            $update['background_image_path'] = $uploader->uploadBackgroundImage(
                $this->background_image,
                $profile->background_image_path
            );
        }

        $profile->update($update);

        $this->current_profile_image_url = $profile->fresh()->profileImageUrl();
        $this->current_background_image_url = $profile->fresh()->backgroundImageUrl();
        $this->profile_image = null;
        $this->background_image = null;
    }

    $user->interests()->sync(
        array_values(array_map('intval', $this->selected_interest_ids ?? []))
    );

    $user->languages()->delete();
    foreach ($this->userLanguages ?? [] as $entry) {
        $user->languages()->create([
            'language_id' => (int) $entry['language_id'],
            'type'        => $entry['type'],
            'level'       => $entry['level'],
        ]);
    }

    session()->flash('saved', true);
};

?>

<div class="max-w-xl mx-auto px-4 py-8">
    <div class="mb-8 space-y-6">
        <x-profile-nav />

        <div>
            <h1 class="text-2xl font-bold text-[#3D2B1F]">Edit profile</h1>
            <p class="text-sm text-[#3D2B1F]/50 mt-1">Help others find you by language and shared interests.</p>
        </div>
    </div>

    @if (session('verified'))
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">Email verified! Finish setting up your profile below.</div>
    @endif

    @if (session('saved'))
        <div class="mb-6 p-3 bg-green-50 text-green-700 rounded-lg text-sm ring-1 ring-green-200">Profile saved!</div>
    @endif

    <form wire:submit="save" class="space-y-5">
        <div class="rounded-xl border border-[#3D2B1F]/10 bg-[#FFF0E0] p-4">
            <h2 class="text-sm font-semibold text-[#3D2B1F]">Profile images</h2>
            <p class="text-xs text-[#3D2B1F]/55 mt-1">Upload a square avatar and a wide background header.</p>

            <div class="mt-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-[#3D2B1F]">Profile picture</label>
                    <div class="mt-2 flex items-center gap-4">
                        @php
                            $previewProfile = null;
                            try { $previewProfile = $profile_image?->temporaryUrl(); } catch (\Throwable $e) { $previewProfile = null; }
                        @endphp

                        @if ($previewProfile || $current_profile_image_url)
                            <img
                                src="{{ $previewProfile ?: $current_profile_image_url }}"
                                alt="Profile picture preview"
                                class="w-16 h-16 rounded-full object-cover ring-2 ring-[#FFF8F0]"
                            >
                        @else
                            <div class="w-16 h-16 rounded-full bg-[#FF8C42] text-white flex items-center justify-center font-bold">S</div>
                        @endif

                        <input
                            type="file"
                            accept="image/*"
                            wire:model="profile_image"
                            class="block w-full text-sm text-[#3D2B1F]/70 file:mr-4 file:rounded-lg file:border-0 file:bg-[#FF8C42] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#e67a35]"
                        >
                    </div>
                    @error('profile_image') <p class="mt-2 text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#3D2B1F]">Background picture</label>
                    @php
                        $previewBackground = null;
                        try { $previewBackground = $background_image?->temporaryUrl(); } catch (\Throwable $e) { $previewBackground = null; }
                    @endphp
                    <div class="mt-2">
                        @if ($previewBackground || $current_background_image_url)
                            <img
                                src="{{ $previewBackground ?: $current_background_image_url }}"
                                alt="Background picture preview"
                                class="w-full h-28 rounded-xl object-cover"
                            >
                        @else
                            <div class="w-full h-28 rounded-xl" style="background: linear-gradient(135deg, #FF8C42 0%, #FFD166 100%);"></div>
                        @endif
                    </div>
                    <input
                        type="file"
                        accept="image/*"
                        wire:model="background_image"
                        class="mt-3 block w-full text-sm text-[#3D2B1F]/70 file:mr-4 file:rounded-lg file:border-0 file:bg-[#FF8C42] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#e67a35]"
                    >
                    @error('background_image') <p class="mt-2 text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <flux:input wire:model="display_name" label="Display name" placeholder="Alex" />

        <div>
            <flux:input
                wire:model="profile_slug"
                label="Public profile link"
                placeholder="12345678"
                description="Letters and numbers only. Your page: {{ url('/u') }}/…"
            />
            @if ($profile_slug)
                <p class="text-xs text-[#3D2B1F]/50 mt-1.5">
                    Preview:
                    <a href="{{ route('users.show', strtolower($profile_slug)) }}" class="text-[#FF8C42] hover:underline" target="_blank" rel="noopener">
                        {{ route('users.show', strtolower($profile_slug)) }}
                    </a>
                </p>
            @endif
        </div>

        <label class="flex items-start gap-3 cursor-pointer rounded-lg border border-[#3D2B1F]/10 bg-[#FFF0E0] px-4 py-3">
            <input
                type="checkbox"
                wire:model.boolean.live="is_private"
                class="mt-0.5 h-4 w-4 shrink-0 rounded border-[#FF8C42]/50 text-[#FF8C42] focus:ring-[#FF8C42]"
            />
            <span>
                <span class="block text-sm font-medium text-[#3D2B1F]">Private profile</span>
                <span class="block text-xs text-[#3D2B1F]/60 mt-1">
                    Visitors only see your display name. Country, languages, interests, and open slots are hidden.
                </span>
            </span>
        </label>

        <flux:select wire:model="country_code" label="Country" placeholder="Select your country">
            <flux:select.option value="">Not set</flux:select.option>
            @foreach (CountryCodes::options() as $code => $name)
                <flux:select.option value="{{ $code }}">{{ $name }} ({{ $code }})</flux:select.option>
            @endforeach
        </flux:select>

        <flux:textarea wire:model="bio" label="Bio" rows="4"
            placeholder="Engineer learning English. Movies, sci-fi, climbing." />

        <fieldset>
            <legend class="text-sm font-medium text-[#3D2B1F]">Your languages</legend>
            <p class="text-xs text-[#3D2B1F]/50 mt-1 mb-3">
                Add languages you speak natively or are learning. Your level for each language appears on your practice slots and helps partners find you on Discover.
            </p>

            <div class="space-y-3">
                @foreach ($userLanguages as $index => $entry)
                    <div wire:key="user-language-{{ $index }}" class="rounded-lg border border-[#3D2B1F]/10 bg-[#FFF0E0] p-3 space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-medium text-[#3D2B1F]/60">Language {{ $index + 1 }}</span>
                            <flux:button type="button" wire:click="removeLanguageRow({{ $index }})" variant="ghost" size="sm">
                                Remove
                            </flux:button>
                        </div>

                        <flux:select wire:model="userLanguages.{{ $index }}.language_id" label="Language">
                            <flux:select.option value="">Select language</flux:select.option>
                            @foreach ($allLanguages as $language)
                                <flux:select.option value="{{ $language->id }}">{{ $language->name_en }}</flux:select.option>
                            @endforeach
                        </flux:select>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <flux:select wire:model="userLanguages.{{ $index }}.type" label="Type">
                                <flux:select.option value="native">Native</flux:select.option>
                                <flux:select.option value="learning">Learning</flux:select.option>
                            </flux:select>

                            <flux:select wire:model="userLanguages.{{ $index }}.level" label="Level">
                                <flux:select.option value="">Select level</flux:select.option>
                                @foreach (UserLanguageLevels::labels() as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>

                        @error('userLanguages.'.$index.'.language_id') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                        @error('userLanguages.'.$index.'.type') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                        @error('userLanguages.'.$index.'.level') <p class="text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            @if (count($userLanguages) < 10)
                <flux:button type="button" wire:click="addLanguageRow" variant="ghost" size="sm" class="mt-3">
                    + Add language
                </flux:button>
            @endif

            @error('userLanguages') <p class="mt-2 text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
        </fieldset>

        <fieldset>
            <legend class="text-sm font-medium text-[#3D2B1F]">Your interests</legend>
            <p class="text-xs text-[#3D2B1F]/50 mt-1 mb-3">
                Pick up to 10 topics you enjoy talking about. Others can find your slots on Discover using these tags.
            </p>
            <div class="flex flex-wrap gap-2">
                @foreach ($allInterests as $interest)
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-[#FF8C42]/30 bg-[#FFF0E0] px-3 py-1.5 text-sm text-[#3D2B1F] has-[:checked]:border-[#FF8C42] has-[:checked]:bg-[#FF8C42]/15">
                        <input
                            type="checkbox"
                            wire:model="selected_interest_ids"
                            value="{{ $interest->id }}"
                            class="rounded border-[#FF8C42]/50 text-[#FF8C42] focus:ring-[#FF8C42]"
                        />
                        {{ $interest->name_en }}
                    </label>
                @endforeach
            </div>
            @error('selected_interest_ids') <p class="mt-2 text-sm text-[#D94F3D]">{{ $message }}</p> @enderror
        </fieldset>

        <div class="flex gap-3 pt-2">
            <flux:button type="submit" variant="primary">Save profile</flux:button>
            <flux:button href="{{ route('profile') }}" variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
