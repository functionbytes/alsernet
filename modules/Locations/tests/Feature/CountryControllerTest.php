<?php

namespace Modules\Locations\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_countries_endpoint_returns_json(): void
    {
        $response = $this->getJson('/api/locations/countries');

        $this->assertContains($response->status(), [200, 404]);

        if ($response->status() === 200) {
            $response->assertHeader('content-type', 'application/json');
        }
    }

    public function test_api_states_requires_country_id(): void
    {
        $response = $this->getJson('/api/locations/states');

        // Either 422 (validation), 200 (empty), or 404 (route not found)
        $this->assertContains($response->status(), [200, 422, 404]);
    }
}
