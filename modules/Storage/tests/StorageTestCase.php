<?php

namespace Modules\Storage\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Base test case for Storage module.
 *
 * Requires the system_testing database to be pre-initialized with migrations.
 * Uses DatabaseTransactions to roll back DML changes after each test.
 * DDL setup must be run once externally (tables are already in system_testing).
 */
abstract class StorageTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        activity()->disableLogging();
    }
}
