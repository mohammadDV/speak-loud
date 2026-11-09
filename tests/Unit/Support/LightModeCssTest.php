<?php

test('app stylesheet forces light mode and class-scoped dark variants', function () {
    $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/app.css');

    expect($css)->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->and($css)->toContain('color-scheme: light only;');
});

test('filament admin theme forces light mode and class-scoped dark variants', function () {
    $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/filament/admin/theme.css');

    expect($css)->toContain('@custom-variant dark (&:where(.dark, .dark *));')
        ->and($css)->toContain('color-scheme: light only;');
});

test('seo head declares light-only color scheme', function () {
    $blade = file_get_contents(dirname(__DIR__, 3).'/resources/views/components/seo-head.blade.php');

    expect($blade)->toContain('name="color-scheme" content="light only"')
        ->and($blade)->toContain('name="supported-color-schemes" content="light"');
});
