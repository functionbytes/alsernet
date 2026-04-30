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

class WhatsappCloudApiWebhookTest extends TestCase
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
        $this->assertEquals('Invalid verify token', $response->getContent());
    }

    public function test_webhook_verification_fails_with_missing_mode(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(400);
        $this->assertEquals('Missing parameters', $response->getContent());
    }

    public function test_webhook_verification_fails_with_missing_token(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(400);
    }

    public function test_webhook_verification_fails_with_missing_challenge(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_token_123',
        ]));

        $response->assertStatus(400);
    }

    public function test_webhook_verification_fails_with_invalid_mode(): void
    {
        $response = $this->get(route('api.webhooks.whatsapp.verify', $this->whatsapp->phone_number).'?'.http_build_query([
            'hub_mode' => 'unsubscribe',
            'hub_verify_token' => 'test_verify_token_123',
            'hub_challenge' => 'challenge_string_12345',
        ]));

        $response->assertStatus(403);
        $this->assertEquals('Invalid mode', $response->getContent());
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
                                'metadata' => ['phone_number_id' => 'test_phone_id'],
                                'messages' => [
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.test123',
                                        'timestamp' => '1234567890',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello, I need help'],
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

    public function test_webhook_handles_incoming_image_message(): void
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
                                'messages' => [
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.image123',
                                        'type' => 'image',
                                        'image' => [
                                            'id' => 'image_media_id',
                                            'mime_type' => 'image/jpeg',
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
        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_webhook_handles_incoming_document_message(): void
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
                                'messages' => [
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.doc123',
                                        'type' => 'document',
                                        'document' => [
                                            'id' => 'doc_media_id',
                                            'filename' => 'report.pdf',
                                            'mime_type' => 'application/pdf',
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
        Queue::assertPushed(ProcessWhatsappMessageJob::class);
    }

    public function test_webhook_handles_sent_status_update(): void
    {
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
                                'statuses' => [
                                    [
                                        'id' => 'wamid.sent_test',
                                        'status' => 'sent',
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
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_delivered_status_update(): void
    {
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
                                'statuses' => [
                                    [
                                        'id' => 'wamid.delivered_test',
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
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_read_status_update(): void
    {
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
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_failed_status_update_with_error(): void
    {
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
                                'statuses' => [
                                    [
                                        'id' => 'wamid.failed_test',
                                        'status' => 'failed',
                                        'timestamp' => '1234567890',
                                        'errors' => [
                                            [
                                                'code' => 131026,
                                                'title' => 'Message undeliverable',
                                                'message' => 'Failed to send message',
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
        $response->assertJson(['status' => 'ok']);
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
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_rejects_missing_signature(): void
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
            'object' => 'instagram',
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

    public function test_webhook_handles_nonexistent_phone_number_gracefully(): void
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

    public function test_webhook_ignores_status_update_for_nonexistent_message(): void
    {
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
                                'statuses' => [
                                    [
                                        'id' => 'wamid.nonexistent',
                                        'status' => 'delivered',
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
    }

    public function test_webhook_handles_empty_entries(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
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

    public function test_webhook_handles_multiple_messages_in_single_request(): void
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
                                'messages' => [
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.msg1',
                                        'type' => 'text',
                                        'text' => ['body' => 'First message'],
                                    ],
                                    [
                                        'from' => '15559876543',
                                        'id' => 'wamid.msg2',
                                        'type' => 'text',
                                        'text' => ['body' => 'Second message'],
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
        Queue::assertPushed(ProcessWhatsappMessageJob::class, 2);
    }

    public function test_webhook_always_returns_200_on_exception(): void
    {
        Config::set('services.whatsapp.app_secret', 'test_app_secret');

        $payload = ['object' => 'whatsapp_business_account'];
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
