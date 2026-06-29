<?php

namespace Modules\Campaign\Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Modules\Campaign\Services\DomainThrottler;
use Modules\CampaignSendingServers\Library\Exception\RateLimitExceeded;
use Tests\TestCase;

class DomainThrottlerTest extends TestCase
{
    public function test_allows_email_under_limit(): void
    {
        Cache::flush();
        $throttler = new DomainThrottler;
        $throttler->throttle('user@gmail.com');
        $this->assertTrue(true);
    }

    public function test_throws_when_limit_exceeded(): void
    {
        Cache::flush();
        $throttler = new DomainThrottler;

        for ($i = 0; $i < 120; $i++) {
            Cache::increment('domain-throttle:gmail.com');
        }

        $this->expectException(RateLimitExceeded::class);
        $throttler->throttle('user@gmail.com');
    }

    public function test_non_throttled_domain_is_ignored(): void
    {
        $throttler = new DomainThrottler;
        $throttler->throttle('user@example.com');
        $this->assertTrue(true);
    }
}
