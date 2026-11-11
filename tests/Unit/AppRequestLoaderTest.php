<?php

use App\Support\Seo;
use Tests\TestCase;

uses(TestCase::class);

test('request loader renders bar and overlay hooks with the brand mark', function () {
    $this->blade('<x-app-request-loader />')
        ->assertSee('data-app-request-loader', false)
        ->assertSee('app-request-loader__bar', false)
        ->assertSee('app-request-loader__overlay', false)
        ->assertSee('images/logo-mark.webp', false)
        ->assertSee('Loading');
});

test('request loader script clears waits safely and heals splash class leaks', function () {
    $js = file_get_contents(resource_path('js/request-loader.js'));

    expect($js)
        ->toContain('NAV_SAFETY_MS')
        ->toContain('clearSplashClasses')
        ->toContain('livewire:navigating')
        ->toContain('livewire:navigated')
        ->toContain('querySelector(\'.app-splash\')')
        ->toContain('aria-busy')
        ->toContain('aria-hidden');
});

test('brand loader styles are extracted into the shared stylesheet', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('brand-loaders.css');

    expect(file_get_contents(resource_path('css/brand-loaders.css')))
        ->toContain('.app-splash')
        ->toContain('.app-request-loader__bar')
        ->toContain('app-request-loading-overlay');
});

test('every page shell includes the request loader', function (string $view) {
    expect(file_get_contents(resource_path("views/{$view}.blade.php")))
        ->toContain('<x-app-request-loader />');
})->with([
    'layouts/app',
    'layouts/guest',
    'pages/home',
    'components/marketing-page',
]);

test('marketing and home shells include livewire assets for spa navigation', function () {
    expect(file_get_contents(resource_path('views/pages/home.blade.php')))
        ->toContain('@livewireStyles')
        ->toContain('@livewireScripts');

    expect(file_get_contents(resource_path('views/components/marketing-page.blade.php')))
        ->toContain('@livewireStyles')
        ->toContain('@livewireScripts');
});

test('primary chrome links use wire navigate', function () {
    expect(file_get_contents(resource_path('views/components/app-navbar.blade.php')))
        ->toContain('wire:navigate');

    expect(file_get_contents(resource_path('views/components/mobile-bottom-nav.blade.php')))
        ->toContain('wire:navigate');

    expect(file_get_contents(resource_path('views/components/site-footer.blade.php')))
        ->toContain('wire:navigate');
});

test('livewire navigate progress bar uses the brand accent', function () {
    expect(config('livewire.navigate.progress_bar_color'))->toBe('#FF8C42')
        ->and(config('livewire.navigate.show_progress_bar'))->toBeTrue();
});

test('default seo image prefers png for social crawlers', function () {
    expect(Seo::defaultImage())->toContain('images/og-default.png')
        ->and(Seo::logoUrl())->toContain('images/logo-mark.png');
});
