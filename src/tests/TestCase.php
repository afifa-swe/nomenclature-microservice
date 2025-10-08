<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel will automatically load .env.testing when tests are run with
        // `php artisan test --env=testing` or `phpunit --env=testing`.
        // Avoid calling putenv() in individual tests; environment should come
        // from the framework bootstrap.
        $this->assertEquals('testing', app()->environment());
    }
}
