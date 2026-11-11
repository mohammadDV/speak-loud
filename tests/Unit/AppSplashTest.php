<?php

use Tests\TestCase;

uses(TestCase::class);

test('splash renders the brand and stays hidden until its script shows it', function () {
    $this->blade('<x-app-splash />')
        ->assertSee('class="app-splash"', false)
        ->assertSee('images/logo-mark.webp', false)
        ->assertSee('SpeakLoudMate')
        ->assertSee('Practice languages with real people')
        ->assertSee("var storageKey = 'slm-splash-seen';", false)
        ->assertSee('sessionStorage.getItem(storageKey)', false)
        ->assertSee('livewire:navigating', false)
        ->assertSee('clearSplashClasses', false);
});

test('marketing pages render the splash before the navbar', function () {
    $this->withoutVite();

    $this->view('pages.about')
        ->assertSeeInOrder(['<body', 'class="app-splash"', 'data-app-request-loader', 'data-mobile-nav'], false);
});

test('every page shell includes the splash', function (string $view) {
    expect(file_get_contents(resource_path("views/{$view}.blade.php")))
        ->toContain('<x-app-splash />');
})->with([
    'layouts/app',
    'layouts/guest',
    'pages/home',
    'components/marketing-page',
]);
