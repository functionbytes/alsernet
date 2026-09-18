<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentStatus;
use Tests\TestCase;

/**
 * Tests for the two incoming webhooks in DocumentsController: prestashopOrderPaid
 * and erpOrderStatus. Both previously accepted unauthenticated requests (no secret
 * configured / '!==' comparison instead of hash_equals()) — now both require
 * HMAC-SHA256 over "{timestamp}:{rawBody}", fail-closed (503) when unconfigured.
 *
 * prestashopOrderPaid's happy path is not covered here: it references
 * Modules\Prestashop\Entities\Orders\Order, a class that does not exist in this
 * codebase (no "Prestashop" module — only HelpdeskPrestashop), so the method
 * always fatals past the auth check. Only the auth layer is testable today.
 */
class DocumentWebhooksTest extends TestCase
{
    use DatabaseTransactions;

    private const PRESTASHOP_SECRET = 'test-prestashop-secret-32-chars';

    private const ERP_SECRET = 'test-erp-secret-32-characters-x';

    private const PRESTASHOP_URL = '/api/documents/webhooks/prestashop/order-paid';

    private const ERP_URL = '/api/documents/webhooks/erp/order-status';

    protected function setUp(): void
    {
        parent::setUp();

        config(['documents.webhooks.prestashop_secret' => self::PRESTASHOP_SECRET]);
        config(['documents.webhooks.erp_secret' => self::ERP_SECRET]);

        // DocumentStatusSeeder envuelve su trabajo en DB::beginTransaction()/
        // rollBack() manual, lo que choca con DatabaseTransactions, y su catch
        // llama a $this->command->error() (null fuera de un artisan real) —
        // se crean las 2 filas necesarias directamente en vez de usarlo.
        foreach (['pending', 'approved'] as $key) {
            DocumentStatus::firstOrCreate(['key' => $key], ['label' => ucfirst($key), 'is_active' => true]);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{body: string, headers: array<string, string>}
     */
    private function signedRequest(array $payload, string $secret, ?int $timestamp = null): array
    {
        $timestamp ??= time();
        $body = json_encode($payload);

        return [
            'body' => $body,
            'headers' => [
                'X-Webhook-Timestamp' => (string) $timestamp,
                'X-Webhook-Signature' => hash_hmac('sha256', $timestamp.':'.$body, $secret),
                'Content-Type' => 'application/json',
            ],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function serverHeaders(array $headers): array
    {
        $server = ['HTTP_ACCEPT' => 'application/json'];

        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        if (isset($headers['Content-Type'])) {
            $server['CONTENT_TYPE'] = $headers['Content-Type'];
            unset($server['HTTP_CONTENT_TYPE']);
        }

        return $server;
    }

    // ─── erpOrderStatus: auth layer ─────────────────────────────────────────────

    public function test_erp_webhook_rejects_missing_secret_with_503(): void
    {
        config(['documents.webhooks.erp_secret' => '']);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(['order_id' => 1, 'status' => 'approved'], self::ERP_SECRET);

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(503);
    }

    public function test_erp_webhook_rejects_invalid_signature_with_401(): void
    {
        $body = json_encode(['order_id' => 1, 'status' => 'approved']);

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders([
            'X-Webhook-Timestamp' => (string) time(),
            'X-Webhook-Signature' => 'not-the-right-signature',
            'Content-Type' => 'application/json',
        ]), $body)->assertStatus(401);
    }

    public function test_erp_webhook_rejects_expired_timestamp_with_401(): void
    {
        ['body' => $body, 'headers' => $headers] = $this->signedRequest(
            ['order_id' => 1, 'status' => 'approved'],
            self::ERP_SECRET,
            time() - 301
        );

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(401);
    }

    public function test_erp_webhook_rejects_request_with_no_signature_headers_at_all(): void
    {
        $body = json_encode(['order_id' => 1, 'status' => 'approved']);

        $this->call('POST', self::ERP_URL, [], [], [], ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'], $body)
            ->assertStatus(401);
    }

    // ─── erpOrderStatus: happy path (no external dependency, fully local) ──────

    public function test_erp_webhook_creates_document_for_unknown_order(): void
    {
        $orderId = random_int(100000, 999999);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest([
            'order_id' => $orderId,
            'status' => 'approved',
            'customer_email' => 'cliente@example.com',
        ], self::ERP_SECRET);

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('new_status', 'approved');

        $document = Document::where('order_id', $orderId)->first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentStatus::getByKey('approved')->id, $document->status_id);
        $this->assertSame('cliente@example.com', $document->customer_email);
    }

    public function test_erp_webhook_updates_status_of_existing_document(): void
    {
        $orderId = random_int(100000, 999999);
        $pending = DocumentStatus::getByKey('pending');

        $document = Document::create([
            'order_id' => $orderId,
            'status_id' => $pending->id,
        ]);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest([
            'order_id' => $orderId,
            'status' => 'approved',
        ], self::ERP_SECRET);

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertOk();

        $this->assertSame(DocumentStatus::getByKey('approved')->id, $document->refresh()->status_id);
    }

    public function test_erp_webhook_rejects_unknown_status_key_with_422(): void
    {
        ['body' => $body, 'headers' => $headers] = $this->signedRequest([
            'order_id' => random_int(100000, 999999),
            'status' => 'not-a-real-status-key',
        ], self::ERP_SECRET);

        $this->call('POST', self::ERP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(422);
    }

    // ─── prestashopOrderPaid: auth layer only ───────────────────────────────────

    public function test_prestashop_webhook_rejects_missing_secret_with_503(): void
    {
        config(['documents.webhooks.prestashop_secret' => '']);

        ['body' => $body, 'headers' => $headers] = $this->signedRequest(['order_id' => 1], self::PRESTASHOP_SECRET);

        $this->call('POST', self::PRESTASHOP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(503);
    }

    public function test_prestashop_webhook_rejects_invalid_signature_with_401(): void
    {
        $body = json_encode(['order_id' => 1]);

        $this->call('POST', self::PRESTASHOP_URL, [], [], [], $this->serverHeaders([
            'X-Webhook-Timestamp' => (string) time(),
            'X-Webhook-Signature' => 'wrong',
            'Content-Type' => 'application/json',
        ]), $body)->assertStatus(401);
    }

    public function test_prestashop_webhook_rejects_expired_timestamp_with_401(): void
    {
        ['body' => $body, 'headers' => $headers] = $this->signedRequest(
            ['order_id' => 1],
            self::PRESTASHOP_SECRET,
            time() - 301
        );

        $this->call('POST', self::PRESTASHOP_URL, [], [], [], $this->serverHeaders($headers), $body)
            ->assertStatus(401);
    }
}
