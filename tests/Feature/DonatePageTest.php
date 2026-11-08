<?php

use App\Support\ProfileSlug;
use App\Support\SitemapBuilder;
use Illuminate\Support\Facades\Cache;

test('donate page renders paypal donation options', function () {
    $email = config('services.paypal.donate_email');

    $this->get(route('donate'))
        ->assertOk()
        ->assertSee('Support SpeakLoudMate', false)
        ->assertSee('Donate with PayPal', false)
        ->assertSee('https://www.paypal.com/cgi-bin/webscr', false)
        ->assertSee($email, false)
        ->assertSee('name="cmd" value="_donations"', false)
        ->assertSee('name="amount" value="5"', false)
        ->assertSee('name="amount" value="10"', false)
        ->assertSee('name="amount" value="25"', false)
        ->assertSee('name="amount" value="50"', false)
        ->assertSee('aria-label="Footer"', false);
});

test('sitemap includes the donate page', function () {
    Cache::forget(SitemapBuilder::CACHE_KEY);

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee(route('donate'), false);
});

test('donate is a reserved profile slug', function () {
    expect(ProfileSlug::isReserved('donate'))->toBeTrue();
});
