<?php

use Database\Seeders\DemoDataSeeder;

test('demo data seeder refuses to run outside local and testing', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(fn () => (new DemoDataSeeder)->run())
        ->toThrow(RuntimeException::class, 'DemoDataSeeder may only run in local or testing environments.');
});
