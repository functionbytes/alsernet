<?php

namespace Modules\Ecommerce\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase {
        migrateDatabases as baseMigrateDatabases;
    }

    /**
     * Override to avoid migrate:fresh failures from broken migrations
     * in other modules. Assumes system_testing is already migrated.
     */
    protected function migrateDatabases(): void
    {
        $this->artisan('migrate', ['--force' => true]);
    }
}
