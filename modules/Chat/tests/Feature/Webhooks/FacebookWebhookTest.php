<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\Chat\Jobs\Webhooks\ProcessFacebookMessageJob;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class FacebookWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $account;

    protected Inbox $inbox;

    protected Facebook $facebook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->facebook = Facebook::create([
            'account_id' => $this->account->id,
            'page_id' => 'test_page_123',
            'page_name' => 'Test Page',
            'page_access_token' => 'test_page_token',
            'user_access_token' => 'test_user_token',
        ]);

        $this->inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
            'channel_type' => Facebook::class,
            'channel_id' => $this->facebook->id,
        ]);
    }

    public function test_webhook_verification_succeeds_with_valid_token(): void
    {
        Config::set('services.facebook.webhook_verify_token', 'test_verify_123');

        $response = $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_verify_123',
            'hub_challenge' => 'challenge_response_xyz',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('challenge_response_xyz', $response->getContent());
    }

    public function test_webhook_verification_uses_global_token_when_no_page_id(): void
    {
        Config::set('services.facebook.webhook_verify_token', 'global_token_456');

        $response = $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'global_token_456',
            'hub_challenge' => 'global_challenge',
        ]));

        $response->assertOk();
        $this->assertEquals('global_challenge', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_token(): void
    {
        Config::set('services.facebook.webhook_verify_token', 'correct_token');

        $response = $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge',
        ]));

        $response->assertStatus(403);
        $this->assertEquals('Invalid verify token', $response->getContent());
    }

    public function test_webhook_verification_fails_with_missing_parameters(): void
    {
        $response = $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
        ]));

        $response->assertStatus(400);
        $this->assertEquals('Missing parameters', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_mode(): void
    {
        Config::set('services.facebook.webhook_verify_token', 'test_token');

        $response = $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'unsubscribe',
            'hub_verify_token' => 'test_token',
            'hub_challenge' => 'challenge',
        ]));

        $response->assertStatus(403);
        $this->assertEquals('Invalid mode', $response->getContent());
    }

    public function test_webhook_handles_incoming_text_message(): void
    {
        Queue::fake();
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'time' => 1234567890,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'recipient' => ['id' => 'test_page_123'],
                            'timestamp' => 1234567890000,
                            'message' => [
                                'mid' => 'msg_mid_123',
                                'text' => 'Hello from Facebook',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessFacebookMessageJob::class, function ($job) {
            return $job->facebookPage->id === $this->facebook->id;
        });
    }

    public function test_webhook_handles_message_with_attachments(): void
    {
        Queue::fake();
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'recipient' => ['id' => 'test_page_123'],
                            'timestamp' => 1234567890000,
                            'message' => [
                                'mid' => 'msg_mid_456',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => [
                                            'url' => 'https://example.com/image.jpg',
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
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        Queue::assertPushed(ProcessFacebookMessageJob::class);
    }

    public function test_webhook_handles_postback_event(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'recipient' => ['id' => 'test_page_123'],
                            'timestamp' => 1234567890000,
                            'postback' => [
                                'title' => 'Get Started',
                                'payload' => 'GET_STARTED_PAYLOAD',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_read_event(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'recipient' => ['id' => 'test_page_123'],
                            'timestamp' => 1234567890000,
                            'read' => [
                                'watermark' => 1234567890000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_delivery_event(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'recipient' => ['id' => 'test_page_123'],
                            'timestamp' => 1234567890000,
                            'delivery' => [
                                'mids' => ['msg_mid_123'],
                                'watermark' => 1234567890000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_feed_change_event(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'post',
                                'verb' => 'add',
                                'post_id' => 'post_123',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_comment_change_event(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'item' => 'comment',
                                'verb' => 'add',
                                'comment_id' => 'comment_123',
                                'message' => 'New comment',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalid_signature']
        );

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $response = $this->postJson('/api/chat/webhooks/facebook', $payload);

        $response->assertStatus(403);
    }

    public function test_webhook_ignores_invalid_object_type(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'user',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_nonexistent_page_gracefully(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'nonexistent_page',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => ['text' => 'Hello'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_empty_entries(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_multiple_messages_in_single_request(): void
    {
        Queue::fake();
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'test_page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => ['mid' => 'msg1', 'text' => 'First'],
                        ],
                        [
                            'sender' => ['id' => 'user_456'],
                            'message' => ['mid' => 'msg2', 'text' => 'Second'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        Queue::assertPushed(ProcessFacebookMessageJob::class, 2);
    }

    public function test_webhook_always_returns_200_on_exception(): void
    {
        Config::set('services.facebook.app_secret', 'test_app_secret');

        $payload = ['object' => 'page'];
        $signature = $this->generateSignature($payload, 'test_app_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
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
