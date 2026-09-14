<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Exceptions;

use Modules\HelpdeskAgents\Exceptions\LlmRateLimitException;
use PHPUnit\Framework\TestCase;

class LlmRateLimitExceptionTest extends TestCase
{
    public function test_exception_includes_limit_type_and_retry_after(): void
    {
        $exception = new LlmRateLimitException('per_minute', 60);

        $this->assertSame('per_minute', $exception->getLimitType());
        $this->assertSame(60, $exception->getRetryAfterSeconds());
    }

    public function test_exception_builds_message_for_per_minute_limit(): void
    {
        $exception = new LlmRateLimitException('per_minute', 60);

        $this->assertNotEmpty($exception->getMessage());
        $this->assertStringContainsString('minuto', $exception->getMessage());
    }

    public function test_exception_builds_message_for_per_5min_limit(): void
    {
        $exception = new LlmRateLimitException('per_5min', 300);

        $this->assertSame('per_5min', $exception->getLimitType());
        $this->assertSame(300, $exception->getRetryAfterSeconds());
        $this->assertNotEmpty($exception->getMessage());
    }

    public function test_exception_builds_message_for_per_day_limit(): void
    {
        $exception = new LlmRateLimitException('per_day', 86400);

        $this->assertSame('per_day', $exception->getLimitType());
        $this->assertSame(86400, $exception->getRetryAfterSeconds());
        $this->assertStringContainsString('diario', $exception->getMessage());
    }

    public function test_exception_accepts_custom_message(): void
    {
        $exception = new LlmRateLimitException('per_minute', 60, 'Custom error message');

        $this->assertSame('Custom error message', $exception->getMessage());
    }

    public function test_exception_uses_default_retry_after_when_not_provided(): void
    {
        $exception = new LlmRateLimitException('per_minute');

        $this->assertSame(60, $exception->getRetryAfterSeconds());
    }

    public function test_exception_extends_runtime_exception(): void
    {
        $exception = new LlmRateLimitException('per_minute', 60);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
