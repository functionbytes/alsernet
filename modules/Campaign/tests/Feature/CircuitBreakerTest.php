<?php

namespace Modules\Campaign\Tests\Feature;

use Modules\Campaign\Services\CircuitBreaker;
use Tests\TestCase;

class CircuitBreakerTest extends TestCase
{
    public function test_circuit_starts_closed(): void
    {
        $cb = new CircuitBreaker;
        $this->assertFalse($cb->isOpen(1));
        $this->assertSame('closed', $cb->status(1));
    }

    public function test_circuit_opens_after_threshold_failures(): void
    {
        $cb = new CircuitBreaker(failureThreshold: 3, windowSeconds: 60, cooldownSeconds: 60);
        $cb->recordFailure(1);
        $cb->recordFailure(1);
        $cb->recordFailure(1);

        $this->assertTrue($cb->isOpen(1));
        $this->assertSame('open', $cb->status(1));
    }

    public function test_circuit_closes_after_success(): void
    {
        $cb = new CircuitBreaker(failureThreshold: 3);
        $cb->recordFailure(1);
        $cb->recordSuccess(1);

        $this->assertFalse($cb->isOpen(1));
    }
}
