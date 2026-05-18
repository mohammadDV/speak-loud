<?php

use App\Support\CountryCodes;

it('resolves country names from codes', function () {
    expect(CountryCodes::name('IR'))->toBe('Iran')
        ->and(CountryCodes::name('us'))->toBe('United States');
});

it('provides sorted country options', function () {
    $options = CountryCodes::options();

    expect($options)->toHaveKey('DE')
        ->and(array_values($options))->toBe(collect($options)->sort()->values()->all());
});
