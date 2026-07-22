<?php

namespace Modules\Helpdesk\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

class InstagramWebhookTest extends TestCase
{
    use DatabaseTransactions;
    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    // Instagram shares the Facebook app_secret (same Meta platform)
    private string $appSecret = 'test-facebook-app-secret';

    private string $verifyToken = 'test-instagram-verify-token';

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
            'helpdesk.integrations.instagram.enabled' => true,
            'helpdesk.integrations.instagram.access_token' => 'test-ig-token',
            'helpdesk.integrations.instagram.business_account_id' => '17841405747428478',
        ]);
    }

    // Direct path because route name 'webhooks.instagram.*' has a duplicate
    // from another module that takes precedence over the Helpdesk registration.
    private string $verifyUrl = '/api/helpdesk/webhooks/instagram';

    // ─── Verification (GET) ───────────────────────────────────────────────────

    public function test_instagram_webhook_verify_returns_challenge_with_valid_token(): void
    {
        $this->get($this->verifyUrl.'?hub_mode=subscribe&hub_challenge=igchallenge789&hub_verify_token='.$this->verifyToken)
            ->assertOk()
            ->assertContent('igchallenge789');
    }

    public function test_instagram_webhook_verify_returns_403_with_wrong_token(): void
    {
        $this->get($this->verifyUrl.'?hub_mode=subscribe&hub_challenge=igchallenge789&hub_verify_token=wrong-token')
            ->assertStatus(403);
    }

    public function test_instagram_webhook_verify_returns_403_with_wrong_hub_mode(): void
    {
        $this->get($this->verifyUrl.'?hub_mode=unsubscribe&hub_challenge=igchallenge789&hub_verify_token='.$this->verifyToken)
            ->assertStatus(403);
    }

    public function test_instagram_webhook_verify_returns_403_with_missing_verify_token(): void
    {
        $this->get($this->verifyUrl.'?hub_mode=subscribe&hub_challenge=igchallenge789')
            ->assertStatus(403);
    }

    // ─── Message POST (happy path) ────────────────────────────────────────────

    public function test_instagram_webhook_post_with_valid_signature_dispatches_job(): void
    {
        Queue::fake();

        $payload = $this->buildMessagePayload('9876543210', 'igm.abc123', 'Hey there!');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertPushed(ProcessSocialWebhookJob::class, function ($job) {
            return $job->channel === 'instagram'
                && $job->eventType === 'message';
        });
    }

    public function test_instagram_webhook_story_reply_dispatches_job(): void
    {
        Queue::fake();

        $payload = $this->buildStoryReplyPayload('9876543210', 'igm.story123', 'Cool story!');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertPushed(ProcessSocialWebhookJob::class, function ($job) {
            return $job->channel === 'instagram'
                && $job->eventType === 'story_reply';
        });
    }

    // ─── Signature validation ─────────────────────────────────────────────────

    public function test_instagram_webhook_post_without_signature_header_returns_403(): void
    {
        $payload = $this->buildMessagePayload('9876543210', 'igm.abc123', 'Hey there!');

        $this->postJson($this->verifyUrl, $payload)
            ->assertForbidden();
    }

    public function test_instagram_webhook_post_with_invalid_signature_returns_403(): void
    {
        $payload = $this->buildMessagePayload('9876543210', 'igm.abc123', 'Hey there!');

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => 'sha256=badsignaturehex']
        )->assertForbidden();
    }

    public function test_instagram_webhook_post_with_wrong_prefix_in_signature_returns_403(): void
    {
        $payload = $this->buildMessagePayload('9876543210', 'igm.abc123', 'Hey there!');
        $body = json_encode($payload);
        $correctHmac = hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => 'md5='.$correctHmac]
        )->assertForbidden();
    }

    public function test_instagram_webhook_rejects_unsigned_request_when_no_secret_configured(): void
    {
        Queue::fake();

        config(['helpdesk.integrations.facebook.app_secret' => '']);

        $payload = $this->buildMessagePayload('9876543210', 'igm.abc123', 'Hey there!');

        // Fail-closed: sin secreto configurado (fuera de entorno local) se rechaza.
        $this->postJson($this->verifyUrl, $payload)
            ->assertForbidden();

        Queue::assertNotPushed(ProcessSocialWebhookJob::class);
    }

    // ─── Non-actionable events (should NOT dispatch job) ─────────────────────

    public function test_instagram_webhook_read_event_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildReadEventPayload('9876543210', 'igm.read123');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk()->assertJson(['status' => 'ok']);

        Queue::assertNothingPushed();
    }

    public function test_instagram_webhook_reaction_event_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildReactionPayload('9876543210', 'igm.react123');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_instagram_webhook_echo_message_does_not_dispatch_job(): void
    {
        Queue::fake();

        $payload = $this->buildEchoMessagePayload('9876543210', 'igm.echo789');
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    public function test_instagram_webhook_wrong_object_type_does_not_dispatch_job(): void
    {
        Queue::fake();

        // Instagram parser only processes object='instagram', not 'page'
        $payload = ['object' => 'page', 'entry' => []];
        $body = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $body, $this->appSecret);

        $this->postJson(
            $this->verifyUrl,
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        Queue::assertNothingPushed();
    }

    // ─── Payload helpers ──────────────────────────────────────────────────────

    private function buildMessagePayload(string $igUserId, string $mid, string $text): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '17841405747428478',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $igUserId],
                            'recipient' => ['id' => '17841405747428478'],
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

    private function buildStoryReplyPayload(string $igUserId, string $mid, string $text): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '17841405747428478',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $igUserId],
                            'recipient' => ['id' => '17841405747428478'],
                            'timestamp' => time() * 1000,
                            'message' => [
                                'mid' => $mid,
                                'text' => $text,
                            ],
                            'referral' => [
                                'url' => 'https://www.instagram.com/stories/user/123/',
                                'type' => 'STORY_MENTION',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildReadEventPayload(string $igUserId, string $mid): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '17841405747428478',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $igUserId],
                            'recipient' => ['id' => '17841405747428478'],
                            'timestamp' => time() * 1000,
                            'message_status' => [
                                'mid' => $mid,
                                'status' => 'READ',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildReactionPayload(string $igUserId, string $mid): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '17841405747428478',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $igUserId],
                            'recipient' => ['id' => '17841405747428478'],
                            'timestamp' => time() * 1000,
                            'reaction' => [
                                'mid' => $mid,
                                'action' => 'react',
                                'emoji' => '❤️',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function buildEchoMessagePayload(string $igUserId, string $mid): array
    {
        return [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '17841405747428478',
                    'time' => time(),
                    'messaging' => [
                        [
                            'sender' => ['id' => $igUserId],
                            'recipient' => ['id' => '17841405747428478'],
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
