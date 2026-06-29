<?php

namespace Modules\HelpdeskErp\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Laravel\Pulse\Pulse;
use Modules\HelpdeskErp\Events\ErpOrdersReady;
use Tests\TestCase;

/**
 * Tests for POST /api/helpdeskErp/webhooks/orders-ready
 *
 * Auth: HMAC-SHA256 signature on "timestamp:rawBody" using ERP_WEBHOOK_SECRET.
 * No Sanctum – webhook is hit by the manager process, not a user.
 */
class ErpWebhookTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'test-webhook-secret-32-chars-long';

    private const URL = '/api/helpdeskErp/webhooks/orders-ready';

    protected function setUp(): void
    {
        parent::setUp();

        // Stub Pulse to prevent TypeError (ErpContextService passes array to Pulse::set)
        $this->app->instance(Pulse::class, new class
        {
            public function set(string $type, string $key, mixed $value, mixed $timestamp = null): object
            {
                return new \stdClass;
            }

            public function record(mixed ...$args): object
            {
                return new \stdClass;
            }
        });

        config(['helpdeskErp.manager_url' => 'http://manager.test']);
        config(['helpdeskErp.webhook_secret' => self::SECRET]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Builds a valid signed request body and headers.
     *
     * @param  array<string, mixed>  $payload
     * @return array{body: string, headers: array<string, string>}
     */
    private function signedRequest(array $payload, ?int $timestamp = null, ?string $secret = null): array
    {
        $timestamp ??= time();
        $secret ??= self::SECRET;
        $body = json_encode($payload);

        $signature = hash_hmac('sha256', $timestamp.':'.$body, $secret);

        return [
            'body' => $body,
            'headers' => [
                'X-Erp-Timestamp' => (string) $timestamp,
                'X-Erp-Signature' => $signature,
                'Content-Type' => 'application/json',
            ],
        ];
    }

    // ── Happy path ─────────────────────────────────────────────────────────

    public function test_valid_signature_returns_ok_and_broadcasts(): void
    {
        Event::fake([ErpOrdersReady::class]);

        $email = 'cliente@ejemplo.com';
        $cacheKey = 'erp_ctx_'.md5(strtolower(trim($email)));
        Cache::put($cacheKey, ['customer' => ['found' => true]], 600);
        $this->assertTrue(Cache::has($cacheKey));

        ['body' => $body, 'headers' => $headers] = $this->signedRequest([
            'email' => $email,
            'customer_id' => 42,
        ]);

        $response = $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body);

        $response->assertOk()->assertJson(['ok' => true]);

        // Cache for this customer must have been invalidated
        $this->assertFalse(Cache::has($cacheKey));

        // ErpOrdersReady must have been broadcast
        Event::assertDispatched(ErpOrdersReady::class, function (ErpOrdersReady $event) use ($email) {
            return $event->email === $email && $event->customerId === 42;
        });
    }

    public function test_valid_signature_without_customer_id_is_accepted(): void
    {
        Event::fake([ErpOrdersReady::class]);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(['email' => 'otro@example.com']);

        $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertOk()
            ->assertJson(['ok' => true]);

        Event::assertDispatched(ErpOrdersReady::class, fn (ErpOrdersReady $e) => $e->customerId === null);
    }

    // ── Auth failures ──────────────────────────────────────────────────────

    public function test_invalid_signature_returns_401(): void
    {
        $timestamp = time();
        $body = json_encode(['email' => 'x@example.com']);

        $response = $this->call(
            'POST',
            self::URL,
            [],
            [],
            [],
            $this->serverHeaders([
                'X-Erp-Timestamp' => (string) $timestamp,
                'X-Erp-Signature' => 'totally-wrong-signature',
                'Content-Type' => 'application/json',
            ]),
            $body
        );

        $response->assertStatus(401);
    }

    public function test_expired_timestamp_returns_401(): void
    {
        $expiredTimestamp = time() - 301; // > 300s in the past

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(
            ['email' => 'x@example.com'],
            $expiredTimestamp
        );

        $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(401);
    }

    public function test_future_timestamp_beyond_tolerance_returns_401(): void
    {
        $futureTimestamp = time() + 301; // > 300s in the future

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(
            ['email' => 'x@example.com'],
            $futureTimestamp
        );

        $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(401);
    }

    // ── Validation failures ────────────────────────────────────────────────

    public function test_invalid_email_in_body_returns_422(): void
    {
        // Signature must be valid over the body that contains the bad email
        ['body' => $body, 'headers' => $headers] = $this->signedRequest(['email' => 'not-an-email']);

        $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(422);
    }

    // ── Config failures ────────────────────────────────────────────────────

    public function test_missing_webhook_secret_returns_503(): void
    {
        config(['helpdeskErp.webhook_secret' => '']);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(['email' => 'x@example.com']);

        $this->call('POST', self::URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(503);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    /**
     * Converts plain header array to $_SERVER-style keys for $this->call().
     *
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function serverHeaders(array $headers): array
    {
        $server = [];

        foreach ($headers as $name => $value) {
            $key = 'HTTP_'.strtoupper(str_replace('-', '_', $name));
            $server[$key] = $value;
        }

        // Content-Type must not have the HTTP_ prefix
        if (isset($headers['Content-Type'])) {
            $server['CONTENT_TYPE'] = $headers['Content-Type'];
            unset($server['HTTP_CONTENT_TYPE']);
        }

        return $server;
    }
}
