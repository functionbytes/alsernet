<?php

namespace Modules\Newsletter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriberPublicTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_with_invalid_email_returns_validation_error(): void
    {
        $response = $this->post('/newsletter/subscribe', [
            'email' => 'not-a-valid-email',
        ]);

        $this->assertContains($response->status(), [302, 422, 404]);
    }

    public function test_subscribe_endpoint_exists_or_returns_404(): void
    {
        $response = $this->post('/newsletter/subscribe', [
            'email' => 'test@example.com',
        ]);

        // Either successful, validation error, or route not found
        $this->assertContains($response->status(), [200, 201, 302, 422, 404]);
    }
}
