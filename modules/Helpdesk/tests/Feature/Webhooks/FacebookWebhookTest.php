<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class FacebookWebhookTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private string $appSecret = 'test-facebook-app-secret';

    private string $verifyToken = 'test-facebook-verify-token';

    protected function setUp(): void
    {
        parent::setUp();

        // Los listeners de inbound consultan User::role('helpdesk-agent').
        $this->seedHelpdeskRoles();

        config([
            'helpdesk.integrations.facebook.app_secret' => $this->appSecret,
            'helpdesk.integrations.facebook.verify_token' => $this->verifyToken,
            'helpdesk.integrations.facebook.page_access_token' => 'test-page-token',
            'helpdesk.integrations.facebook.enabled' => true,
        ]);
    }

    // ─── Verification (GET) ───────────────────────────────────────────────────

    public function test_facebook_webhook_verify_returns_challenge_with_valid_token(): void
    {
        $this->get(route('webhooks.facebook.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123challenge',
            'hub_verify_token' => $this->verifyToken,
        ]))->assertOk()->assertContent('abc123challenge');
    }

    public function test_facebook_webhook_verify_returns_403_with_wrong_token(): void
    {
        $this->get(route('webhooks.facebook.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => 'wrong-token',
        ]))->assertStatus(403);
    }

    public function test_facebook_webhook_verify_returns_403_with_wrong_hub_mode(): void
    {
        $this->get(route('webhooks.facebook.verify', [
            'hub_mode' => 'unsubscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => $this->verifyToken,
        ]))->assertStatus(403);
    }

    public function test_facebook_webhook_verify_returns_403_with_missing_verify_token(): void
    {
        $this->get(route('webhooks.facebook.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123',
        ]))->assertStatus(403);
    }

    public function test_facebook_webhook_verify_returns_403_when_no_verify_token_configured(): void
    {
        config(['helpdesk.integrations.facebook.verify_token' => '']);

        $this->get(route('webhooks.facebook.verify', [
            'hub_mode' => 'subscribe',
            'hub_challenge' => 'abc123',
            'hub_verify_token' => '',
        ]))->assertStatus(403);
    }

    // ─── Message POST (happy path) ────────────────────────────────────────────

    public function test_facebook_webhook_post_with_valid_signature_dispatches_job(): void
    {
        Queue::fake();

        $payload = $this->buildMessagePayload('1234567890', 'mid.test123', 'Hello World');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessSocialWebhookJob::class, function ($job) {
            return $job->channel === 'facebook'
                && $job->eventType === 'message';
        });
    }

    public function test_facebook_webhook_postback_dispatches_job(): void
    {
        Queue::fake();

        $payload = $this->buildPostbackPayload('1234567890', 'Get Started', 'GET_STARTED');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertPushed(ProcessSocialWebhookJob::class, function ($job) {
            return $job->channel === 'facebook'
                && $job->eventType === 'postback';
        });
    }

    // ─── Signature validation ─────────────────────────────────────────────────

    public function test_facebook_webhook_post_without_signature_header_returns_403(): void
    {
        $payload = $this->buildMessagePayload('1234567890', 'mid.test123', 'Hello World');

        $this->postJson(route('webhooks.facebook.handle'), $payload)
            ->assertForbidden();
    }

    public function test_facebook_webhook_post_with_invalid_signature_returns_403(): void
    {
        $payload = $this->buildMessagePayload('1234567890', 'mid.test123', 'Hello World');

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => 'sha256=invalidsignaturehex']
        )->assertForbidden();
    }

    public function test_facebook_webhook_post_with_wrong_prefix_in_signature_returns_403(): void
    {
        $payload = $this->buildMessagePayload('1234567890', 'mid.test123', 'Hello World');
        $body = json_encode($payload);
        $correctHmac = hash_hmac('sha256', $body, $this->appSecret);

        // md5= prefix instead of sha256=
        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => 'md5='.$correctHmac]
        )->assertForbidden();
    }

    public function test_facebook_webhook_rejects_unsigned_request_when_no_secret_configured(): void
    {
        Queue::fake();

        config(['helpdesk.integrations.facebook.app_secret' => '']);

        $payload = $this->buildMessagePayload('1234567890', 'mid.test123', 'Hello World');

        // Fail-closed: sin secreto configurado (fuera de entorno local) se rechaza.
        $this->postJson(route('webhooks.facebook.handle'), $payload)
            ->assertForbidden();

        Queue::assertNotPushed(ProcessSocialWebhookJob::class);
    }

    // ─── Non-actionable events (should NOT dispatch job) ─────────────────────

    public function test_facebook_webhook_messaging_read_event_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildReadEventPayload('1234567890', 1234567890000);
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertNothingPushed();
    }

    public function test_facebook_webhook_delivery_event_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildDeliveryEventPayload('1234567890', 1234567890000);
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_facebook_webhook_echo_message_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildEchoMessagePayload('1234567890', 'mid.echo456');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_facebook_webhook_wrong_object_type_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = ['object' => 'user', 'entry' => []];
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            route('webhooks.facebook.handle'),
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    // ─── Payload helpers ──────────────────────────────────────────────────────

    private function buildMessagePayload(string $psid, string $mid, string $text): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '109442377559389',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '109442377559389'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => $mid,
                                'text' => $text,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildPostbackPayload(string $psid, string $title, string $payloadValue): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '109442377559389',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '109442377559389'],
                            'timestamp' => time() * 1000,
                            'postback' => [
                                'title' => $title,
                                'payload' => $payloadValue,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildReadEventPayload(string $psid, int $watermark): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '109442377559389',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '109442377559389'],
                            'timestamp' => time() * 1000,
                            'read' => [
                                'watermark' => $watermark,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildDeliveryEventPayload(string $psid, int $watermark): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '109442377559389',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '109442377559389'],
                            'timestamp' => time() * 1000,
                            'delivery' => [
                                'watermark' => $watermark,
                                'seq' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildEchoMessagePayload(string $psid, string $mid): array
    {
        return [
            'object' => 'page',
            'entry' => [
                [
                    'id' => '109442377559389',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $psid],
                            'recipient' => ['id' => '109442377559389'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => $mid,
                                'text' => 'Echo from page',
                                'is_echo' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
