<?php

test('home page includes the request loader and spa navigate chrome', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-app-request-loader', false)
        ->assertSee('images/logo-mark.webp', false)
        ->assertSee('wire:navigate', false)
        ->assertSee('aria-label="Footer"', false);
});

test('discover page includes the request loader and spa navigate chrome', function () {
    $this->get(route('discover'))
        ->assertOk()
        ->assertSee('data-app-request-loader', false)
        ->assertSee('wire:navigate', false);
});

test('about page includes the request loader for marketing shells', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('data-app-request-loader', false)
        ->assertSee('wire:navigate', false);
});
