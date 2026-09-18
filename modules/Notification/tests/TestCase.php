<?php

namespace Modules\Notification\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];
}
