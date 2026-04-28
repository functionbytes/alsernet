<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

class ApiResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_validation_error_format_is_unified(): void
    {
        $response = $this->withoutMiddleware([ThrottleRequests::class])
            ->postJson('/api/v1/ecommerce/auth/login', []);

        $response->assertUnprocessable()
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'code',
            ])
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_unauthenticated_format_is_unified(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertUnauthorized()
            ->assertJsonStructure(['success', 'message', 'code'])
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_not_found_format_is_unified(): void
    {
        $response = $this->getJson('/api/v1/this-does-not-exist');

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'message', 'code'])
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}
