<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use DatabaseTransactions;

    public function test_webhook_verify_returns_challenge_with_valid_token(): void
    {
        config(['helpdesksocial.integrations.meta.verify_token' => 'test_token']);

        $response = $this->getJson('/webhooks/helpdesk/social/meta?hub_mode=subscribe&hub_verify_token=test_token&hub_challenge=12345');

        $response->assertOk();
        $this->assertEquals('12345', $response->getContent());
    }

    public function test_webhook_verify_returns_403_with_invalid_token(): void
    {
        config(['helpdesksocial.integrations.meta.verify_token' => 'test_token']);

        $response = $this->getJson('/webhooks/helpdesk/social/meta?hub_mode=subscribe&hub_verify_token=wrong_token&hub_challenge=12345');

        $response->assertForbidden();
        $response->assertSee('Forbidden');
    }

    public function test_webhook_handle_returns_ok_status(): void
    {
        config(['helpdesksocial.integrations.meta.app_secret' => null]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_123',
                    'changes' => [],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/helpdesk/social/meta', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_webhook_handle_processes_comment(): void
    {
        config([
            'helpdesksocial.integrations.meta.app_secret' => null,
            'helpdesksocial.intent_classification.provider' => 'rules',
        ]);

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $payload = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => 'page_123',
                    'changes' => [
                        [
                            'field' => 'feed',
                            'value' => [
                                'item' => 'comment',
                                'comment_id' => 'comment_456',
                                'post_id' => 'post_789',
                                'from' => [
                                    'id' => 'user_999',
                                    'name' => 'Test User',
                                ],
                                'message' => 'Test comment body',
                                'created_time' => now()->toIso8601String(),
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/webhooks/helpdesk/social/meta', $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('helpdesk_social_comments', [
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'external_comment_id' => 'comment_456',
            'external_post_id' => 'post_789',
            'external_user_id' => 'user_999',
            'author_name' => 'Test User',
            'body' => 'Test comment body',
            'status' => 'pending',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function commentPayload(string $verb, string $commentId, string $message = 'Body'): array
    {
        return [
            'object' => 'page',
            'entry' => [[
                'id' => 'page_123',
                'changes' => [[
                    'field' => 'feed',
                    'value' => [
                        'item' => 'comment',
                        'verb' => $verb,
                        'comment_id' => $commentId,
                        'post_id' => 'post_789',
                        'from' => ['id' => 'user_999', 'name' => 'Test User'],
                        'message' => $message,
                        'created_time' => now()->toIso8601String(),
                    ],
                ]],
            ]],
        ];
    }

    private function prepareWebhook(): SocialAccount
    {
        config([
            'helpdesksocial.integrations.meta.app_secret' => null,
            'helpdesksocial.intent_classification.provider' => 'rules',
        ]);

        return SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);
    }

    public function test_webhook_ignores_remove_hide_and_unhide_verbs(): void
    {
        $this->prepareWebhook();

        foreach (['remove', 'hide', 'unhide'] as $verb) {
            $this->postJson('/webhooks/helpdesk/social/meta', $this->commentPayload($verb, "comment_{$verb}"))
                ->assertOk();

            $this->assertDatabaseMissing('helpdesk_social_comments', ['external_comment_id' => "comment_{$verb}"]);
        }
    }

    public function test_webhook_processes_comment_with_add_verb(): void
    {
        $this->prepareWebhook();

        $this->postJson('/webhooks/helpdesk/social/meta', $this->commentPayload('add', 'comment_add'))
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_social_comments', ['external_comment_id' => 'comment_add']);
    }

    public function test_webhook_edited_verb_updates_existing_comment_body(): void
    {
        $this->prepareWebhook();

        $this->postJson('/webhooks/helpdesk/social/meta', $this->commentPayload('add', 'comment_edit', 'Original'))
            ->assertOk();
        $this->postJson('/webhooks/helpdesk/social/meta', $this->commentPayload('edited', 'comment_edit', 'Editado'))
            ->assertOk();

        $this->assertDatabaseHas('helpdesk_social_comments', ['external_comment_id' => 'comment_edit', 'body' => 'Editado']);
        $this->assertSame(1, SocialComment::where('external_comment_id', 'comment_edit')->count());
    }

    public function test_webhook_edited_verb_for_unknown_comment_is_skipped(): void
    {
        $this->prepareWebhook();

        $this->postJson('/webhooks/helpdesk/social/meta', $this->commentPayload('edited', 'comment_unknown'))
            ->assertOk();

        $this->assertDatabaseMissing('helpdesk_social_comments', ['external_comment_id' => 'comment_unknown']);
    }
}
