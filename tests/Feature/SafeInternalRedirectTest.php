<?php

use App\Support\SafeInternalRedirect;

test('safe internal redirect allows same-app absolute urls', function () {
    $appUrl = url('/discover');

    expect(SafeInternalRedirect::url($appUrl, route('home')))->toBe($appUrl);
});

test('safe internal redirect allows relative paths', function () {
    expect(SafeInternalRedirect::url('/discover', route('home')))
        ->toBe(url('/discover'));
});

test('safe internal redirect rejects external urls', function () {
    expect(SafeInternalRedirect::url('https://evil.example/phish', route('discover')))
        ->toBe(route('discover'));
});

test('safe internal redirect rejects protocol-relative urls', function () {
    expect(SafeInternalRedirect::url('//evil.example/phish', route('discover')))
        ->toBe(route('discover'));
});
