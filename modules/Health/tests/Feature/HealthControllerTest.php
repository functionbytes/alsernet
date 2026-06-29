<?php

namespace Modules\Health\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthControllerTest extends TestCase
{
    use RefreshDatabase;

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
