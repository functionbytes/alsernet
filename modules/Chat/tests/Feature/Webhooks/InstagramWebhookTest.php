<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Modules\Chat\Jobs\Webhooks\ProcessInstagramMessageJob;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class InstagramWebhookTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $account;

    protected Inbox $inbox;

    protected Instagram $instagram;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
        $this->instagram = Instagram::create([
            'account_id' => $this->account->id,
            'instagram_id' => 'ig_account_123',
            'username' => 'testaccount',
            'user_access_token' => 'test_user_token',
            'page_access_token' => 'test_page_token',
        ]);

        $this->inbox = Inbox::factory()->create([
            'account_id' => $this->account->id,
            'channel_type' => Instagram::class,
            'channel_id' => $this->instagram->id,
        ]);
    }

    public function test_webhook_verification_succeeds_with_valid_token(): void
    {
        Config::set('services.instagram.webhook_verify_token', 'test_ig_verify');

        $response = $this->get('/api/chat/webhooks/instagram?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'test_ig_verify',
            'hub_challenge' => 'instagram_challenge_xyz',
        ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $this->assertEquals('instagram_challenge_xyz', $response->getContent());
    }

    public function test_webhook_verification_uses_global_token_when_no_account_id(): void
    {
        Config::set('services.instagram.webhook_verify_token', 'global_ig_token');

        $response = $this->get('/api/chat/webhooks/instagram?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'global_ig_token',
            'hub_challenge' => 'global_ig_challenge',
        ]));

        $response->assertOk();
        $this->assertEquals('global_ig_challenge', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_token(): void
    {
        Config::set('services.instagram.webhook_verify_token', 'correct_token');

        $response = $this->get('/api/chat/webhooks/instagram?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token',
            'hub_challenge' => 'challenge',
        ]));

        $response->assertStatus(403);
        $this->assertEquals('Invalid verify token', $response->getContent());
    }

    public function test_webhook_verification_fails_with_missing_parameters(): void
    {
        $response = $this->get('/api/chat/webhooks/instagram?'.http_build_query([
            'hub_mode' => 'subscribe',
        ]));

        $response->assertStatus(400);
        $this->assertEquals('Missing parameters', $response->getContent());
    }

    public function test_webhook_verification_fails_with_invalid_mode(): void
    {
        Config::set('services.instagram.webhook_verify_token', 'test_token');

        $response = $this->get('/api/chat/webhooks/instagram?'.http_build_query([
            'hub_mode' => 'unsubscribe',
            'hub_verify_token' => 'test_token',
            'hub_challenge' => 'challenge',
        ]));

        $response->assertStatus(403);
        $this->assertEquals('Invalid mode', $response->getContent());
    }

    public function test_webhook_handles_incoming_direct_message(): void
    {
        Queue::fake();
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'time' => 1234567890,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'message' => [
                                'mid' => 'ig_mid_789',
                                'text' => 'Hello from Instagram DM',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessInstagramMessageJob::class, function ($job) {
            return $job->instagram->id === $this->instagram->id;
        });
    }

    public function test_webhook_handles_message_with_image_attachment(): void
    {
        Queue::fake();
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'message' => [
                                'mid' => 'ig_mid_img',
                                'attachments' => [
                                    [
                                        'type' => 'image',
                                        'payload' => [
                                            'url' => 'https://example.com/ig-image.jpg',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        Queue::assertPushed(ProcessInstagramMessageJob::class);
    }

    public function test_webhook_handles_story_reply(): void
    {
        Queue::fake();
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'message' => [
                                'mid' => 'ig_story_reply_mid',
                                'text' => 'Great story!',
                                'reply_to' => [
                                    'story' => [
                                        'url' => 'https://example.com/story.jpg',
                                        'id' => 'story_123',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        Queue::assertPushed(ProcessInstagramMessageJob::class);
    }

    public function test_webhook_handles_read_event(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'read' => [
                                'mid' => 'ig_mid_read',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_delivery_event(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'delivery' => [
                                'mids' => ['ig_mid_delivered'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_message_reaction(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'ig_user_456'],
                            'recipient' => ['id' => 'ig_account_123'],
                            'timestamp' => 1234567890000,
                            'reaction' => [
                                'mid' => 'ig_mid_react',
                                'reaction' => '❤️',
                                'emoji' => '❤️',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_comment_change_event(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => 'comment_456',
                                'text' => 'Nice post!',
                                'media_id' => 'media_789',
                                'from' => [
                                    'id' => 'ig_user_456',
                                    'username' => 'testuser',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_mention_change_event(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'changes' => [
                        [
                            'field' => 'mentions',
                            'value' => [
                                'comment_id' => 'mention_789',
                                'media_id' => 'media_123',
                                'from' => [
                                    'id' => 'ig_user_456',
                                    'username' => 'testuser',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_story_insights_change_event(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'changes' => [
                        [
                            'field' => 'story_insights',
                            'value' => [
                                'media_id' => 'story_456',
                                'impressions' => 1234,
                                'reach' => 1000,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalid_signature']
        );

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_rejects_missing_signature(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        $response = $this->postJson('/api/chat/webhooks/instagram', $payload);

        $response->assertStatus(403);
    }

    public function test_webhook_ignores_invalid_object_type(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_nonexistent_account_gracefully(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'nonexistent_account',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => ['text' => 'Hello'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_empty_entries(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_handles_multiple_messages_in_single_request(): void
    {
        Queue::fake();
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => 'ig_account_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_1'],
                            'message' => ['mid' => 'msg1', 'text' => 'First'],
                        ],
                        [
                            'sender' => ['id' => 'user_2'],
                            'message' => ['mid' => 'msg2', 'text' => 'Second'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
        Queue::assertPushed(ProcessInstagramMessageJob::class, 2);
    }

    public function test_webhook_always_returns_200_on_exception(): void
    {
        Config::set('services.instagram.app_secret', 'test_ig_secret');

        $payload = ['object' => 'instagram'];
        $signature = $this->generateSignature($payload, 'test_ig_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
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
