<?php

test('home page includes google tag manager when configured', function () {
    config(['services.google_tag_manager.id' => 'GTM-NGDBQ4W4']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtm.js?id=GTM-NGDBQ4W4', false)
        ->assertSee('window.dataLayer = window.dataLayer || [];', false)
        ->assertSee('function gtag(){dataLayer.push(arguments);}', false)
        ->assertSee("gtag('js', new Date());", false);
});

test('home page omits google tag manager when not configured', function () {
    config(['services.google_tag_manager.id' => null]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
});
