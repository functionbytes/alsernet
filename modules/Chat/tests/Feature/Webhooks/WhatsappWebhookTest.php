<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Modules\Chat\Jobs\Webhooks\ProcessWhatsappMessageJob;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Conversations\ConversationMessage;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class WhatsappWebhookTest extends TestCase
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
            'phone_number_id' => 'test_phone_id',
            'business_account_id' => 'test_business_id',
            'provider' => 'cloud_api',
            'webhook_verify_token' => 'test_verify_token_123',
            'access_token' => 'test_access_token',
            'active' => true,
        ]);
    }

    public function test_webhook_verification_succeeds_with_valid_token(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('challenge_string_12345', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_token(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(403);
    }

    public function test_webhook_verification_fails_with_missing_parameters(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'subscribe',
        ]));

        $response->assertStatus(400);
    }

    public function test_webhook_verification_fails_with_invalid_mode(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'invalid_mode',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(403);
    }

    public function test_webhook_verification_fails_for_nonexistent_phone_number(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', '99999999999').'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(403);
    }

    public function test_webhook_handles_incoming_text_message(): void
    {
        Queue::fake();

        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'test_business_id',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => 'test_phone_id',
                                ],
                                'messages' => [
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.test123',
                                        'timestamp' => '1234567890',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Hello, I need help',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessWhatsappMessageJob::class, function ($job) {
            return $job->whatsapp->id === $this->whatsapp->id;
        });
    }

    public function test_webhook_handles_message_status_update(): void
    {
        $message = ConversationMessage::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'external_id' => 'wamid.status_test',
            'status' => 'sent',
        ]);

        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'test_business_id',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'metadata' => [
                                    'phone_number_id' => 'test_phone_id',
                                ],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.status_test',
                                        'status' => 'delivered',
                                        'timestamp' => '1234567890',
                                        'recipient_id' => '15559876543',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();

        $message->refresh();
        $this->assertEquals('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    public function test_webhook_handles_read_status_update(): void
    {
        $message = ConversationMessage::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'external_id' => 'wamid.read_test',
            'status' => 'delivered',
        ]);

        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'test_business_id',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.read_test',
                                        'status' => 'read',
                                        'timestamp' => '1234567890',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();

        $message->refresh();
        $this->assertEquals('read', $message->status);
        $this->assertNotNull($message->read_at);
    }

    public function test_webhook_handles_failed_status_update(): void
    {
        $message = ConversationMessage::factory()->create([
            'account_id' => $this->account->id,
            'inbox_id' => $this->inbox->id,
            'external_id' => 'wamid.failed_test',
            'status' => 'sent',
        ]);

        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'test_business_id',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    [
                                        'id' => 'wamid.failed_test',
                                        'status' => 'failed',
                                        'timestamp' => '1234567890',
                                        'errors' => [
                                            [
                                                'code' => 131026,
                                                'title' => 'Message undeliverable',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();

        $message->refresh();
        $this->assertEquals('failed', $message->status);
        $this->assertNotNull($message->failed_at);
        $this->assertEquals('Message undeliverable', $message->error_message);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalid_signature']
        );

        $response->assertStatus(403);
    }

    public function test_webhook_handles_missing_signature_gracefully(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload
        );

        $response->assertStatus(403);
    }

    public function test_webhook_ignores_invalid_object_type(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'invalid_object_type',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_nonexistent_whatsapp_account_gracefully(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', '99999999999'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_evolution_webhook_handles_incoming_message(): void
    {
        Queue::fake();

        $this->whatsapp->update(['provider' => 'evolution']);

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
            $payload
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_evolution_webhook_updates_qr_code(): void
    {
        $this->whatsapp->update(['provider' => 'evolution']);

        $payload = [
            'event' => 'qrcode.updated',
            'instance' => 'test_instance',
            'data' => [
                'qrcode' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertNotNull($this->whatsapp->qr_code);
        $this->assertNotNull($this->whatsapp->qr_code_updated_at);
    }

    public function test_evolution_webhook_updates_connection_status(): void
    {
        $this->whatsapp->update(['provider' => 'evolution', 'active' => false]);

        $payload = [
            'event' => 'connection.update',
            'instance' => 'test_instance',
            'data' => [
                'state' => 'open',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertTrue($this->whatsapp->active);
    }

    public function test_evolution_webhook_deactivates_on_close(): void
    {
        $this->whatsapp->update(['provider' => 'evolution', 'active' => true]);

        $payload = [
            'event' => 'connection.update',
            'instance' => 'test_instance',
            'data' => [
                'state' => 'close',
            ],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload
        );

        $response->assertOk();

        $this->whatsapp->refresh();
        $this->assertFalse($this->whatsapp->active);
    }

    public function test_evolution_webhook_handles_unknown_event(): void
    {
        Log::shouldReceive('info')->once()->with('Unknown Evolution event', ['event' => 'unknown.event']);

        $this->whatsapp->update(['provider' => 'evolution']);

        $payload = [
            'event' => 'unknown.event',
            'instance' => 'test_instance',
            'data' => [],
        ];

        $response = $this->postJson(
            route('api.webhooks.evolution.handle', $this->whatsapp->id),
            $payload
        );

        $response->assertOk();
    }

    public function test_webhook_always_returns_200_on_exception(): void
    {
        Log::shouldReceive('error')->once();

        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = ['malformed' => 'data'];
        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            route('api.webhooks.whatsapp.handle', $this->whatsapp->phone_number),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    protected function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);

        return 'sha256='.hash_hmac('sha256', $jsonPayload, $secret);
    }
}
