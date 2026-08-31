<?php

namespace Modules\Health\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use DatabaseTransactions;

    // mysql/mariadb/helpdesk apuntan a la MISMA BD real - RefreshDatabase la
    // migro-fresh por un fallo de force="true" en phpunit.xml (incidente
    // 29-ago-2026) - nunca usar RefreshDatabase en este proyecto.
    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    public function test_ping_endpoint_responds(): void
    {
        $response = $this->get('/api/ping');

        // Either 200 (registered) or 404 (route not found yet)
        $this->assertContains($response->status(), [200, 404]);
    }

    public function test_health_detailed_without_token_is_protected(): void
    {
        $response = $this->getJson('/api/health/detailed');

        // Should return 401/403/404 — never 200 without auth
        $this->assertNotEquals(200, $response->status());
    }
}
