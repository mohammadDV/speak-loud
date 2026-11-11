<?php

use App\Models\User;

test('home page includes mobile quick access and menu controls', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee('aria-label="Open menu"', false)
        ->assertSee('aria-label="Mobile menu"', false)
        ->assertSee('data-mobile-nav-toggle', false)
        ->assertSee(route('home'), false)
        ->assertSee(route('discover'), false)
        ->assertSee(route('schedule'), false)
        ->assertSee(route('messages'), false)
        ->assertSee(route('profile'), false)
        ->assertSee(route('blog.index'), false)
        ->assertSee(route('faq.index'), false)
        ->assertSee(route('about'), false);
});

test('authenticated discover page includes app and account drawer links', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('discover'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee(route('discover'), false)
        ->assertSee(route('schedule'), false)
        ->assertSee(route('claims'), false)
        ->assertSee(route('messages'), false)
        ->assertSee(route('profile'), false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee(route('profile.security'), false)
        ->assertSee(route('tickets.index'), false)
        ->assertSee(route('blog.index'), false)
        ->assertSee(route('faq.index'), false)
        ->assertSee('Log out', false)
        ->assertDontSee('Start free')
        ->assertSee('aria-label="Footer"', false);
});

test('authenticated schedule page includes mobile nav destinations', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('schedule'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee('aria-label="Open menu"', false)
        ->assertSee(route('schedule'), false)
        ->assertSee(route('messages'), false)
        ->assertSee(route('profile'), false)
        ->assertSee(route('claims'), false)
        ->assertSee('Log out', false);
});

test('authenticated messages page includes mobile nav but omits site footer', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('messages'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee(route('messages'), false)
        ->assertSee(route('schedule'), false)
        ->assertDontSee('aria-label="Footer"', false);
});

test('authenticated profile page includes mobile nav and footer', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee(route('profile'), false)
        ->assertSee(route('profile.edit'), false)
        ->assertSee(route('tickets.index'), false)
        ->assertSee('aria-label="Footer"', false);
});

test('marketing about page includes mobile nav with livewire for spa nav', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('aria-label="Quick access"', false)
        ->assertSee('data-mobile-nav-toggle', false)
        ->assertSee('viewer_timezone=', false)
        ->assertSee('aria-label="Footer"', false)
        ->assertSee('/livewire/livewire', false)
        ->assertSee('wire:navigate', false);
});
