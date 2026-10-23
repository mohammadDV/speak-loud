<?php

use App\Support\HtmlSanitizer;

test('html sanitizer strips script tags', function () {
    $html = '<p>Hello</p><script>alert(1)</script>';

    expect(HtmlSanitizer::sanitize($html))
        ->toBe('<p>Hello</p>');
});

test('html sanitizer strips event handlers and javascript urls', function () {
    $html = '<p onclick="alert(1)">Hi</p><a href="javascript:alert(1)">x</a><img src=x onerror=alert(1)>';

    $clean = HtmlSanitizer::sanitize($html);

    expect($clean)
        ->not->toContain('onclick')
        ->not->toContain('javascript:')
        ->not->toContain('onerror')
        ->not->toContain('<img')
        ->toContain('<p>Hi</p>');
});

test('html sanitizer keeps safe links and formatting', function () {
    $html = '<p><strong>Bold</strong> <a href="https://example.com">link</a></p>';

    expect(HtmlSanitizer::sanitize($html))
        ->toContain('<strong>Bold</strong>')
        ->toContain('href="https://example.com"')
        ->toContain('rel="noopener noreferrer"');
});
