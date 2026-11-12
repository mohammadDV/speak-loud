<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertUsingIsolatedTestDatabase();
    }

    /**
     * Refuse to run against the app/production MySQL database.
     */
    private function assertUsingIsolatedTestDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (! is_string($database) || $database === '') {
            throw new RuntimeException('Tests require an explicit database name.');
        }

        $forbidden = [
            'speakloud',
            'speakloudmate',
            'production',
            'prod',
        ];

        $normalized = strtolower($database);

        if (in_array($normalized, $forbidden, true) || ! str_contains($normalized, 'test')) {
            throw new RuntimeException(
                "Refusing to run tests against database [{$database}] on [{$connection}]. ".
                'Configure phpunit.xml to use a dedicated database such as speakloud_testing.'
            );
        }
    }
}
