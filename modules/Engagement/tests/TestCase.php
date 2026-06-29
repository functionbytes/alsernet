<?php

namespace Modules\Engagement\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        config()->set('database.connections.sqlite_testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('database.default', 'sqlite_testing');
        config()->set('database.connections.helpdesk', config('database.connections.sqlite_testing'));
    }
}
