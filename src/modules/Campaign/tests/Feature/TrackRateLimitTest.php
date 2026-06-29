<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TrackRateLimitTest extends TestCase
{
    public function test_tracking_endpoint_returns_rate_limit_headers(): void
    {
        $key = 'track:127.0.0.1';
        RateLimiter::clear($key);

        $response = $this->get('/campaign/track/open/fake.png');
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_tracking_endpoint_returns_429_when_limit_exceeded(): void
    {
        $key = 'track:127.0.0.1';
        RateLimiter::clear($key);
        for ($i = 0; $i < 60; $i++) {
            RateLimiter::hit($key);
        }

        $response = $this->get('/campaign/track/open/fake.png');
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }
}
