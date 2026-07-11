<?php

use App\Models\Language;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('user can save languages on profile edit', function () {
    $english = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);
    $spanish = Language::where('code', 'es')->first()
        ?? Language::create(['code' => 'es', 'name_en' => 'Spanish', 'name_native' => 'Español', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'languages-save@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'langsave',
        'display_name' => 'Language Save',
        'profile_slug' => 'langsave',
    ]);

    Volt::actingAs($user)->test('profile.edit')
        ->set('userLanguages', [
            ['language_id' => (string) $english->id, 'type' => 'native', 'level' => 'fluent'],
            ['language_id' => (string) $spanish->id, 'type' => 'learning', 'level' => 'intermediate'],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();

    expect($user->languages)->toHaveCount(2);
    expect($user->languages->firstWhere('type', 'native')?->language_id)->toBe($english->id);
    expect($user->languages->firstWhere('type', 'learning')?->level)->toBe('intermediate');
});

test('profile edit rejects duplicate language and type combinations', function () {
    $english = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'languages-dup@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'langdup',
        'display_name' => 'Language Dup',
        'profile_slug' => 'langdup',
    ]);

    Volt::actingAs($user)->test('profile.edit')
        ->set('userLanguages', [
            ['language_id' => (string) $english->id, 'type' => 'native', 'level' => 'fluent'],
            ['language_id' => (string) $english->id, 'type' => 'native', 'level' => 'advanced'],
        ])
        ->call('save')
        ->assertHasErrors(['userLanguages']);
});

test('profile overview shows saved languages', function () {
    $english = Language::where('code', 'en')->first()
        ?? Language::create(['code' => 'en', 'name_en' => 'English', 'name_native' => 'English', 'is_active' => true]);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'languages-overview@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'langoverview',
        'display_name' => 'Language Overview',
        'profile_slug' => 'langoverview',
    ]);

    $user->languages()->create([
        'language_id' => $english->id,
        'type'        => 'native',
        'level'       => 'fluent',
    ]);

    Volt::actingAs($user)->test('profile.overview')
        ->assertSee('Languages')
        ->assertSee('English')
        ->assertSee('Native');
});
