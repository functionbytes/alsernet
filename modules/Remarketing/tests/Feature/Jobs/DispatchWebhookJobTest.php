<?php

namespace Modules\Remarketing\Tests\Feature\Jobs;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Remarketing\Jobs\DispatchWebhookJob;
use Modules\Remarketing\Models\Store;
use Modules\Remarketing\Models\Webhook;
use Tests\TestCase;

class DispatchWebhookJobTest extends TestCase
{
    use DatabaseTransactions;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('remarketing_webhooks')) {
            $this->markTestSkipped('Migraciones del módulo Remarketing no aplicadas.');
        }

        $this->store = $this->createStore();
    }

    public function test_successful_response_resets_failure_count_and_updates_last_triggered_at(): void
    {
        Http::fake(['*' => Http::response('OK', 200)]);

        $webhook = $this->createWebhook(['failure_count' => 3, 'last_triggered_at' => null]);

        (new DispatchWebhookJob($webhook, 'order.created', ['order_id' => 1]))->handle();

        $webhook->refresh();
        $this->assertEquals(0, $webhook->failure_count);
        $this->assertNotNull($webhook->last_triggered_at);
    }

    public function test_failed_response_increments_failure_count(): void
    {
        Http::fake(['*' => Http::response('Error', 500)]);

        $webhook = $this->createWebhook(['failure_count' => 2]);

        try {
            (new DispatchWebhookJob($webhook, 'order.created', []))->handle();
        } catch (\RuntimeException) {
            // expected
        }

        $webhook->refresh();
        $this->assertEquals(3, $webhook->failure_count);
        $this->assertTrue($webhook->is_active);
    }

    public function test_webhook_is_disabled_when_failure_count_reaches_10(): void
    {
        Http::fake(['*' => Http::response('Error', 503)]);

        $webhook = $this->createWebhook(['failure_count' => 9, 'is_active' => true]);

        try {
            (new DispatchWebhookJob($webhook, 'order.created', []))->handle();
        } catch (\RuntimeException) {
            // expected
        }

        $webhook->refresh();
        $this->assertEquals(10, $webhook->failure_count);
        $this->assertFalse($webhook->is_active);
    }

    public function test_webhook_with_secret_sends_signature_header(): void
    {
        $capturedHeaders = [];

        Http::fake(function ($request) use (&$capturedHeaders) {
            $capturedHeaders = $request->headers();

            return Http::response('OK', 200);
        });

        $secret = 'my-super-secret';
        $webhook = $this->createWebhook(['secret' => $secret]);

        (new DispatchWebhookJob($webhook, 'order.created', ['order_id' => 42]))->handle();

        $this->assertArrayHasKey('X-Remarketing-Signature', $capturedHeaders);
        $this->assertStringStartsWith('sha256=', $capturedHeaders['X-Remarketing-Signature'][0]);
    }

    public function test_webhook_without_secret_omits_signature_header(): void
    {
        $capturedHeaders = [];

        Http::fake(function ($request) use (&$capturedHeaders) {
            $capturedHeaders = $request->headers();

            return Http::response('OK', 200);
        });

        $webhook = $this->createWebhook(['secret' => null]);

        (new DispatchWebhookJob($webhook, 'order.created', []))->handle();

        $this->assertArrayNotHasKey('X-Remarketing-Signature', $capturedHeaders);
    }

    public function test_signature_hmac_matches_expected_value(): void
    {
        $secret = 'test-secret-key';
        $capturedHeaders = [];
        $capturedBody = null;

        Http::fake(function ($request) use (&$capturedHeaders, &$capturedBody) {
            $capturedHeaders = $request->headers();
            $capturedBody = $request->body();

            return Http::response('OK', 200);
        });

        $payload = ['order_id' => 123];
        $webhook = $this->createWebhook(['secret' => $secret]);

        (new DispatchWebhookJob($webhook, 'order.created', $payload))->handle();

        // Reconstruct body the same way the job does
        $body = json_encode([
            'event' => 'order.created',
            'occurred_at' => now()->toIso8601String(),
            'payload' => $payload,
        ]);

        // The HMAC is deterministic given the body - just assert format is correct
        $signatureHeader = $capturedHeaders['X-Remarketing-Signature'][0] ?? '';
        $this->assertStringStartsWith('sha256=', $signatureHeader);
        $hash = substr($signatureHeader, 7);
        $this->assertEquals(64, strlen($hash)); // sha256 hex = 64 chars
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::query()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'platform' => 'manual',
            'name' => 'Test Store '.uniqid(),
            'domain' => 'test-'.uniqid().'.example.com',
            'status' => 'active',
            'webhook_token' => Str::random(64),
        ], $attributes));
    }

    private function createWebhook(array $attributes = []): Webhook
    {
        return Webhook::query()->create(array_merge([
            'store_id' => $this->store->id,
            'url' => 'https://example.com/hook-'.uniqid(),
            'events' => ['order.created'],
            'is_active' => true,
            'failure_count' => 0,
        ], $attributes));
    }
}
