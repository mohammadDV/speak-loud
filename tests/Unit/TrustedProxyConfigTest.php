<?php

use Tests\TestCase;

uses(TestCase::class);

test('trusted proxy config file is registered', function () {
    expect(config()->has('trustedproxy.proxies'))->toBeTrue();
});
