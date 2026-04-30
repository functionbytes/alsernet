<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\Chat\Jobs\Webhooks\ProcessWhatsappMessageJob;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class WhatsappEvolutionApiWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $account;

    protected Inbox $inbox;

    protected Whatsapp $whatsapp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->inbox = Inbox::factory()->create(['account_id' => $this->account->id]);
        $this->whatsapp = Whatsapp::create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'phone_number' => '15551234567',
            'provider' => 'evolution',
            'api_key' => 'test_evolution_api_key',
            'active' => true,
        ]);
    }

    public function test_webhook_handles_incoming_text_message(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => [
                        'remoteJid' => '15559876543@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'evolution_msg_123',
                    ],
                    'message' => [
                        'conversation' => 'Hello from Evolution API',
                    ],
                    'messageTimestamp' => '1234567890',
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessWhatsappMessageJob::class, function ($job) {
            return $job->whatsapp->id === $this->whatsapp->id;
        });
    }

    public function test_webhook_handles_image_message(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => [
                        'remoteJid' => '15559876543@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'evolution_img_123',
                    ],
                    'message' => [
                        'imageMessage' => [
                            'url' => 'https://example.com/image.jpg',
                            'mimetype' => 'image/jpeg',
                            'caption' => 'Check this out',
                        ],
                    ],
                    'messageTimestamp' => '1234567890',
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_webhook_handles_document_message(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => [
                        'remoteJid' => '15559876543@s.whatsapp.net',
                        'fromMe' => false,
                        'id' => 'evolution_doc_123',
                    ],
                    'message' => [
                        'documentMessage' => [
                            'url' => 'https://example.com/document.pdf',
                            'mimetype' => 'application/pdf',
                            'fileName' => 'report.pdf',
                        ],
                    ],
                    'messageTimestamp' => '1234567890',
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_webhook_handles_message_status_update(): void
    {
        $payload = [
            'event' => 'messages.update',
            'instance' => 'test_instance',
            'data' => [
                'key' => [
                    'remoteJid' => '15559876543@s.whatsapp.net',
                    'id' => 'evolution_msg_123',
                ],
                'update' => [
                    'status' => 3,
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
    }

    public function test_webhook_handles_qr_code_update(): void
    {
        $payload = [
            'event' => 'qrcode.updated',
            'instance' => 'test_instance',
            'data' => [
                'qrcode' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertNotNull($this->whatsapp->qr_code);
        $this->assertNotNull($this->whatsapp->qr_code_updated_at);
        $this->assertStringStartsWith('data:image/png;base64,', $this->whatsapp->qr_code);
    }

    public function test_webhook_handles_connection_open(): void
    {
        $this->whatsapp->update(['active' => false]);

        $payload = [
            'event' => 'connection.update',
            'instance' => 'test_instance',
            'data' => [
                'state' => 'open',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertTrue($this->whatsapp->active);
    }

    public function test_webhook_handles_connection_close(): void
    {
        $this->whatsapp->update(['active' => true]);

        $payload = [
            'event' => 'connection.update',
            'instance' => 'test_instance',
            'data' => [
                'state' => 'close',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertFalse($this->whatsapp->active);
    }

    public function test_webhook_handles_connection_connecting(): void
    {
        $this->whatsapp->update(['active' => true]);

        $payload = [
            'event' => 'connection.update',
            'instance' => 'test_instance',
            'data' => [
                'state' => 'connecting',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertFalse($this->whatsapp->active);
    }

    public function test_webhook_rejects_invalid_api_key(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'wrong_api_key']
        );

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Unauthorized']);
    }

    public function test_webhook_rejects_missing_api_key(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload
        );

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_api_key_in_different_headers(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => ['remoteJid' => '15559876543@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg123'],
                    'message' => ['conversation' => 'Test'],
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['api-key' => 'test_evolution_api_key']
        );

        $response->assertOk();
    }

    public function test_webhook_accepts_x_api_key_header(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => ['remoteJid' => '15559876543@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg123'],
                    'message' => ['conversation' => 'Test'],
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['x-api-key' => 'test_evolution_api_key']
        );

        $response->assertOk();
    }

    public function test_webhook_handles_nonexistent_whatsapp_account(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', 99999),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_rejects_cloud_api_provider(): void
    {
        $this->whatsapp->update(['provider' => 'cloud_api']);

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
    }

    public function test_webhook_handles_unknown_event(): void
    {
        $payload = [
            'event' => 'unknown.event',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_uses_global_api_key_when_not_set_on_channel(): void
    {
        Queue::fake();

        $this->whatsapp->update(['api_key' => null]);
        Config::set('services.evolution.api_key', 'global_evolution_key');

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => ['remoteJid' => '15559876543@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg123'],
                    'message' => ['conversation' => 'Test'],
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'global_evolution_key']
        );

        $response->assertOk();
        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_webhook_handles_multiple_messages_in_single_request(): void
    {
        Queue::fake();

        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [
                [
                    'key' => ['remoteJid' => '15559876543@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg1'],
                    'message' => ['conversation' => 'First message'],
                ],
                [
                    'key' => ['remoteJid' => '15559876543@s.whatsapp.net', 'fromMe' => false, 'id' => 'msg2'],
                    'message' => ['conversation' => 'Second message'],
                ],
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        Queue::assertPushed(ProcessWhatsappMessageJob::class, 2);
    }

    public function test_webhook_handles_empty_data_array(): void
    {
        $payload = [
            'event' => 'messages.upsert',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
    }

    public function test_webhook_always_returns_200_on_exception(): void
    {
        $payload = ['event' => 'messages.upsert'];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload,
            ['apikey' => 'test_evolution_api_key']
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }
}
