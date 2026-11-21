<?php

use Illuminate\Support\Facades\Route;

test('custom 404 page uses branded speakloudmate design', function () {
    $this->get('/this-page-definitely-does-not-exist-speakloud')
        ->assertNotFound()
        ->assertSee('Page not found', false)
        ->assertSee('We can’t find that page.', false)
        ->assertSee('SpeakLoudMate', false)
        ->assertSee('Back to home', false)
        ->assertSee('Open Learn', false)
        ->assertSee(asset('images/logo-mark.webp'), false);
});

test('custom 500 page uses branded speakloudmate design', function () {
    Route::get('/__testing/server-error', function () {
        abort(500);
    });

    $this->get('/__testing/server-error')
        ->assertStatus(500)
        ->assertSee('Something went wrong', false)
        ->assertSee('Something went wrong on our side.', false)
        ->assertSee('SpeakLoudMate', false)
        ->assertSee('Get support', false)
        ->assertSee(asset('images/logo-mark.webp'), false);
});
