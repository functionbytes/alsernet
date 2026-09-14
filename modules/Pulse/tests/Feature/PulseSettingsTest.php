<?php

namespace Modules\Pulse\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nwidart\Modules\Facades\Module;
use Tests\TestCase;

class PulseSettingsTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Module::find('Pulse')?->isEnabled()) {
            $this->markTestSkipped('Pulse module is disabled in modules_statuses.json');
        }
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get('/panel/settings/pulse');

        $response->assertRedirect();
    }
}
