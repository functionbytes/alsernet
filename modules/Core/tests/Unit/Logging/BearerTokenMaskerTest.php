<?php

namespace Modules\Core\Tests\Unit\Logging;

use Modules\Core\Logging\SensitiveDataMasker;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class BearerTokenMaskerTest extends TestCase
{
    private SensitiveDataMasker $masker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->masker = new SensitiveDataMasker;
    }

    private function record(string $message, array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'test',
            level: Level::Error,
            message: $message,
            context: $context,
        );
    }

    /** @test */
    public function test_masks_bearer_tokens_in_message(): void
    {
        $record = $this->record('Request failed: Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9');

        $processed = ($this->masker)($record);

        $this->assertStringContainsString('Bearer [REDACTED]', $processed->message);
        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $processed->message);
    }

    /** @test */
    public function test_masks_sensitive_keys_in_context(): void
    {
        $record = $this->record('API call', [
            'api_key' => 'my-secret-api-key',
            'token' => 'access-token-abc123',
            'password' => 'hunter2',
            'authorization' => 'Bearer some-token-here',
        ]);

        $processed = ($this->masker)($record);

        $this->assertEquals('[REDACTED]', $processed->context['api_key']);
        $this->assertEquals('[REDACTED]', $processed->context['token']);
        $this->assertEquals('[REDACTED]', $processed->context['password']);
        $this->assertEquals('[REDACTED]', $processed->context['authorization']);
    }

    /** @test */
    public function test_preserves_non_sensitive_data(): void
    {
        $record = $this->record('User login', [
            'user_id' => 42,
            'email' => 'user@example.com',
            'action' => 'login',
            'ip' => '192.168.1.1',
        ]);

        $processed = ($this->masker)($record);

        $this->assertEquals(42, $processed->context['user_id']);
        $this->assertEquals('user@example.com', $processed->context['email']);
        $this->assertEquals('login', $processed->context['action']);
        $this->assertEquals('192.168.1.1', $processed->context['ip']);
        $this->assertEquals('User login', $processed->message);
    }

    /** @test */
    public function test_recursive_masking_in_nested_arrays(): void
    {
        $record = $this->record('Nested context', [
            'request' => [
                'headers' => [
                    'authorization' => 'Bearer nested-secret-token',
                    'content-type' => 'application/json',
                ],
                'body' => [
                    'api_key' => 'deeply-nested-key',
                    'email' => 'test@example.com',
                ],
            ],
        ]);

        $processed = ($this->masker)($record);

        $this->assertEquals('[REDACTED]', $processed->context['request']['headers']['authorization']);
        $this->assertEquals('application/json', $processed->context['request']['headers']['content-type']);
        $this->assertEquals('[REDACTED]', $processed->context['request']['body']['api_key']);
        $this->assertEquals('test@example.com', $processed->context['request']['body']['email']);
    }
}
