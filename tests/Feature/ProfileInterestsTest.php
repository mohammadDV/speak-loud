<?php

use App\Models\Interest;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

test('user can save interests on profile edit', function () {
    $music = Interest::firstOrCreate(['slug' => 'music'], ['name_en' => 'Music']);
    $travel = Interest::firstOrCreate(['slug' => 'travel'], ['name_en' => 'Travel']);
    Interest::firstOrCreate(['slug' => 'tech'], ['name_en' => 'Tech']);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'interests-save@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'interestsave',
        'display_name' => 'Interest Save',
        'profile_slug' => 'interestsave',
    ]);

    Volt::actingAs($user)->test('profile.edit')
        ->set('selected_interest_ids', [(string) $music->id, (string) $travel->id])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('selected_interest_ids', [(string) $music->id, (string) $travel->id]);

    $user->refresh();

    expect($user->interests->pluck('id')->sort()->values()->all())
        ->toEqual(collect([$music->id, $travel->id])->sort()->values()->all());
});

test('profile overview shows saved interests', function () {
    $gaming = Interest::firstOrCreate(['slug' => 'gaming'], ['name_en' => 'Gaming']);

    $user = User::create([
        'uuid'              => (string) Str::uuid(),
        'email'             => 'interests-overview@speakloud.test',
        'password'          => '123456789',
        'role'              => 'user',
        'status'            => 'active',
        'email_verified_at' => now(),
    ]);

    UserProfile::create([
        'user_id'      => $user->id,
        'username'     => 'interestoverview',
        'display_name' => 'Interest Overview',
        'profile_slug' => 'interestoverview',
    ]);

    $user->interests()->sync([$gaming->id]);

    Volt::actingAs($user)->test('profile.overview')
        ->assertSee('Interests')
        ->assertSee('Gaming');
});
