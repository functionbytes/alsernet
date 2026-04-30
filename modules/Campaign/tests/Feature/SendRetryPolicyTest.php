<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\SendRetryPolicy;
use Tests\TestCase;

class SendRetryPolicyTest extends TestCase
{
    public function test_dns_error_should_retry(): void
    {
        $policy = new SendRetryPolicy;
        $ex = new \Exception('DNS lookup failed: getaddrinfo error');

        $this->assertTrue($policy->shouldRetry($ex));
        $this->assertSame(60, $policy->getDelay($ex));
    }

    public function test_greylisting_should_retry(): void
    {
        $policy = new SendRetryPolicy;
        $ex = new \Exception('451 4.7.1 Greylisting in effect');

        $this->assertTrue($policy->shouldRetry($ex));
        $this->assertSame(600, $policy->getDelay($ex));
    }

    public function test_auth_error_should_not_retry(): void
    {
        $policy = new SendRetryPolicy;
        $ex = new \Exception('Authentication failed: invalid api key');

        $this->assertFalse($policy->shouldRetry($ex));
        $this->assertSame(0, $policy->getDelay($ex));
    }

    public function test_unknown_error_uses_default(): void
    {
        $policy = new SendRetryPolicy;
        $ex = new \Exception('Something weird happened');

        $this->assertTrue($policy->shouldRetry($ex));
        $this->assertSame(300, $policy->getDelay($ex));
    }
}
