<?php

namespace Modules\Chat\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Modules\Chat\Models\Accounts\Account;
use Modules\Chat\Models\Channels\Facebook;
use Modules\Chat\Models\Channels\Instagram;
use Modules\Chat\Models\Channels\Whatsapp;
use Modules\Chat\Models\Inbox\Inbox;
use Tests\TestCase;

class WebhookSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->account = Account::factory()->create();
    }

    public function test_facebook_webhook_rejects_request_without_signature(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $payload = [
            'object' => 'page',
            'entry' => [],
        ];

        $response = $this->postJson('/api/chat/webhooks/facebook', $payload);

        $response->assertStatus(403);
    }

    public function test_facebook_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

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

    public function test_facebook_webhook_rejects_signature_with_tampered_payload(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $originalPayload = [
            'object' => 'page',
            'entry' => [
                ['id' => 'page_123'],
            ],
        ];

        $tamperedPayload = [
            'object' => 'page',
            'entry' => [
                ['id' => 'hacked_page_999'],
            ],
        ];

        $signature = $this->generateSignature($originalPayload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $tamperedPayload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_instagram_webhook_rejects_request_without_signature(): void
    {
        Config::set('services.instagram.app_secret', 'test_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        $response = $this->postJson('/api/chat/webhooks/instagram', $payload);

        $response->assertStatus(403);
    }

    public function test_instagram_webhook_rejects_invalid_signature(): void
    {
        Config::set('services.instagram.app_secret', 'test_secret');

        $payload = [
            'object' => 'instagram',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/instagram',
            $payload,
            ['X-Hub-Signature-256' => 'sha256=wrong_signature']
        );

        $response->assertStatus(403);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_whatsapp_webhook_rejects_request_without_signature(): void
    {
        $whatsapp = $this->createWhatsappAccount();
        Config::set('services.whatsapp.app_secret', 'test_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/whatsapp/'.$whatsapp->phone_number,
            $payload
        );

        $response->assertStatus(403);
    }

    public function test_whatsapp_webhook_rejects_invalid_signature(): void
    {
        $whatsapp = $this->createWhatsappAccount();
        Config::set('services.whatsapp.app_secret', 'test_secret');

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/whatsapp/'.$whatsapp->phone_number,
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalid']
        );

        $response->assertStatus(403);
    }

    public function test_facebook_webhook_rejects_replay_attack_with_timestamp_check(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $facebook = $this->createFacebookPage();

        $oldPayload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebook->page_id,
                    'time' => now()->subHours(2)->timestamp,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => ['text' => 'Old message'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($oldPayload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $oldPayload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_verification_token_uses_constant_time_comparison(): void
    {
        Config::set('services.facebook.webhook_verify_token', 'correct_token_123');

        $startCorrect = microtime(true);
        $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'correct_token_123',
            'hub_challenge' => 'challenge',
        ]));
        $timeCorrect = microtime(true) - $startCorrect;

        $startWrong = microtime(true);
        $this->get('/api/chat/webhooks/facebook?'.http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong_token_1234',
            'hub_challenge' => 'challenge',
        ]));
        $timeWrong = microtime(true) - $startWrong;

        $timeDifference = abs($timeCorrect - $timeWrong);
        $this->assertLessThan(0.001, $timeDifference);
    }

    public function test_webhook_signature_uses_constant_time_comparison(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $payload = ['object' => 'page', 'entry' => []];
        $correctSignature = $this->generateSignature($payload, 'test_secret');
        $wrongSignature = 'sha256='.str_repeat('0', 64);

        $startCorrect = microtime(true);
        $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $correctSignature]
        );
        $timeCorrect = microtime(true) - $startCorrect;

        $startWrong = microtime(true);
        $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $wrongSignature]
        );
        $timeWrong = microtime(true) - $startWrong;

        $timeDifference = abs($timeCorrect - $timeWrong);
        $this->assertLessThan(0.001, $timeDifference);
    }

    public function test_webhook_handles_extremely_large_payload_gracefully(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $largePayload = [
            'object' => 'page',
            'entry' => array_fill(0, 1000, [
                'id' => 'page_123',
                'messaging' => array_fill(0, 100, [
                    'sender' => ['id' => 'user_'.random_int(1, 10000)],
                    'message' => ['text' => str_repeat('A', 5000)],
                ]),
            ]),
        ];

        $signature = $this->generateSignature($largePayload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $largePayload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_rejects_malformed_json_payload(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $malformedJson = '{"object": "page", "entry": [invalid json}';

        $response = $this->post(
            '/api/chat/webhooks/facebook',
            [],
            [
                'X-Hub-Signature-256' => 'sha256='.hash_hmac('sha256', $malformedJson, 'test_secret'),
                'Content-Type' => 'application/json',
            ]
        );

        $response->assertStatus(403);
    }

    public function test_webhook_handles_sql_injection_attempts_in_page_id(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => "' OR '1'='1",
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => ['text' => 'test'],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_xss_attempts_in_message_content(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $facebook = $this->createFacebookPage();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebook->page_id,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => [
                                'text' => '<script>alert("XSS")</script>',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_path_traversal_attempts(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $facebook = $this->createFacebookPage();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebook->page_id,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => [
                                'attachments' => [
                                    [
                                        'type' => 'file',
                                        'payload' => [
                                            'url' => '../../../../../../etc/passwd',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_null_byte_injection(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $facebook = $this->createFacebookPage();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebook->page_id,
                    'messaging' => [
                        [
                            'sender' => ['id' => "user\x00_123"],
                            'message' => ['text' => "test\x00message"],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_prevents_account_enumeration_via_timing(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $existingFacebook = $this->createFacebookPage();

        $existingPayload = [
            'object' => 'page',
            'entry' => [['id' => $existingFacebook->page_id]],
        ];

        $nonExistentPayload = [
            'object' => 'page',
            'entry' => [['id' => 'nonexistent_page_id_999']],
        ];

        $startExisting = microtime(true);
        $signature1 = $this->generateSignature($existingPayload, 'test_secret');
        $this->postJson(
            '/api/chat/webhooks/facebook',
            $existingPayload,
            ['X-Hub-Signature-256' => $signature1]
        );
        $timeExisting = microtime(true) - $startExisting;

        $startNonExistent = microtime(true);
        $signature2 = $this->generateSignature($nonExistentPayload, 'test_secret');
        $this->postJson(
            '/api/chat/webhooks/facebook',
            $nonExistentPayload,
            ['X-Hub-Signature-256' => $signature2]
        );
        $timeNonExistent = microtime(true) - $startNonExistent;

        $timeDifference = abs($timeExisting - $timeNonExistent);
        $this->assertLessThan(0.1, $timeDifference);
    }

    public function test_webhook_handles_unicode_normalization_attacks(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $facebook = $this->createFacebookPage();

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => $facebook->page_id,
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => [
                                'text' => "\u{0041}\u{0301}",
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_deeply_nested_json_objects(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $nestedData = ['level' => 0];
        for ($i = 1; $i <= 100; $i++) {
            $nestedData = ['level' => $i, 'nested' => $nestedData];
        }

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_123',
                    'messaging' => [
                        [
                            'sender' => ['id' => 'user_123'],
                            'message' => $nestedData,
                        ],
                    ],
                ],
            ],
        ];

        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertOk();
    }

    public function test_webhook_handles_case_sensitivity_in_headers(): void
    {
        Config::set('services.facebook.app_secret', 'test_secret');

        $payload = ['object' => 'page', 'entry' => []];
        $signature = $this->generateSignature($payload, 'test_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['x-hub-signature-256' => $signature]
        );

        $response->assertStatus(403);
    }

    public function test_evolution_webhook_rejects_missing_api_key(): void
    {
        $whatsapp = $this->createWhatsappAccount(['provider' => 'evolution']);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/evolution/'.$whatsapp->id,
            $payload
        );

        $response->assertStatus(401);
    }

    public function test_evolution_webhook_rejects_invalid_api_key(): void
    {
        $whatsapp = $this->createWhatsappAccount([
            'provider' => 'evolution',
            'provider_config' => ['api_key' => 'correct_key_123'],
        ]);

        $payload = [
            'event' => 'messages.upsert',
            'data' => [],
        ];

        $response = $this->postJson(
            '/api/chat/webhooks/evolution/'.$whatsapp->id,
            $payload,
            ['apikey' => 'wrong_key_456']
        );

        $response->assertStatus(401);
    }

    public function test_webhook_fails_when_app_secret_not_configured(): void
    {
        Config::set('services.facebook.app_secret', null);

        $payload = ['object' => 'page', 'entry' => []];
        $signature = 'sha256='.hash_hmac('sha256', json_encode($payload), 'any_secret');

        $response = $this->postJson(
            '/api/chat/webhooks/facebook',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        );

        $response->assertStatus(403);
    }

    protected function createFacebookPage(array $attributes = []): Facebook
    {
        $facebook = Facebook::create(array_merge([
            'account_id' => $this->account->id,
            'page_id' => 'test_page_'.random_int(100, 999),
            'page_name' => 'Test Page',
            'page_access_token' => 'test_page_token',
            'user_access_token' => 'test_user_token',
        ], $attributes));

        Inbox::factory()->create([
            'account_id' => $this->account->id,
            'channel_type' => Facebook::class,
            'channel_id' => $facebook->id,
        ]);

        return $facebook;
    }

    protected function createInstagramAccount(array $attributes = []): Instagram
    {
        $instagram = Instagram::create(array_merge([
            'account_id' => $this->account->id,
            'instagram_id' => 'test_ig_'.random_int(100, 999),
            'username' => 'testaccount',
            'user_access_token' => 'test_user_token',
            'page_access_token' => 'test_page_token',
        ], $attributes));

        Inbox::factory()->create([
            'account_id' => $this->account->id,
            'channel_type' => Instagram::class,
            'channel_id' => $instagram->id,
        ]);

        return $instagram;
    }

    protected function createWhatsappAccount(array $attributes = []): Whatsapp
    {
        $inbox = Inbox::factory()->create(['account_id' => $this->account->id]);

        return Whatsapp::create(array_merge([
            'account_id' => $this->account->id,
            'inbox_id' => $inbox->id,
            'phone_number' => '1555'.random_int(1000000, 9999999),
            'provider' => 'cloud_api',
            'webhook_verify_token' => 'test_verify_token',
        ], $attributes));
    }

    protected function generateSignature(array $payload, string $secret): string
    {
        $jsonPayload = json_encode($payload);

        return 'sha256='.hash_hmac('sha256', $jsonPayload, $secret);
    }
}
