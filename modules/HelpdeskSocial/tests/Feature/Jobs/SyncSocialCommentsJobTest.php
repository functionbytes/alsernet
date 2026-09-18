<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Jobs\SyncSocialCommentsJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Tests\TestCase;

class SyncSocialCommentsJobTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_syncs_new_comments_from_api(): void
    {
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'comment_1',
                        'message' => 'Great post!',
                        'created_time' => now()->toIso8601String(),
                        'from' => ['id' => 'user_1', 'name' => 'John Doe'],
                        'parent' => ['id' => null],
                    ],
                    [
                        'id' => 'comment_2',
                        'message' => 'Thanks for sharing',
                        'created_time' => now()->toIso8601String(),
                        'from' => ['id' => 'user_2', 'name' => 'Jane Doe'],
                    ],
                ],
            ], 200),
        ]);

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'page_access_token' => 'fake_token',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $job = new SyncSocialCommentsJob($account->id, 'post_123');
        $job->handle(app(SocialApiClientInterface::class));

        $this->assertDatabaseCount('helpdesk_social_comments', 2);
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'external_comment_id' => 'comment_1',
            'body' => 'Great post!',
        ]);
        $this->assertDatabaseHas('helpdesk_social_comments', [
            'external_comment_id' => 'comment_2',
            'body' => 'Thanks for sharing',
        ]);
    }

    public function test_skips_duplicate_comments(): void
    {
        $existingCommentId = 'existing_comment_'.uniqid();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => $existingCommentId,
                        'message' => 'Duplicate comment',
                        'created_time' => now()->toIso8601String(),
                        'from' => ['id' => 'user_1', 'name' => 'John Doe'],
                    ],
                ],
            ], 200),
        ]);

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'page_access_token' => 'fake_token',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        SocialComment::factory()->create([
            'social_account_id' => $account->id,
            'platform' => 'facebook',
            'external_comment_id' => $existingCommentId,
        ]);

        $job = new SyncSocialCommentsJob($account->id, 'post_123');
        $job->handle(app(SocialApiClientInterface::class));

        $this->assertDatabaseCount('helpdesk_social_comments', 1);
    }

    public function test_records_failure_on_api_error(): void
    {
        Http::fake(function () {
            throw new \Exception('API request failed');
        });

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'page_access_token' => 'fake_token',
            'is_active' => true,
            'comments_enabled' => true,
            'consecutive_failures' => 0,
        ]);

        $job = new SyncSocialCommentsJob($account->id, 'post_123');

        try {
            $job->handle(app(SocialApiClientInterface::class));
            $this->fail('Expected exception was not thrown');
        } catch (\Throwable) {
            $account->refresh();
            $this->assertSame(1, $account->consecutive_failures);
            $this->assertNotNull($account->last_error_at);
            $this->assertSame('API request failed', $account->last_error_message);
        }
    }

    public function test_skips_inactive_account(): void
    {
        Http::fake();

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'page_access_token' => 'fake_token',
            'is_active' => false,
            'comments_enabled' => true,
        ]);

        $job = new SyncSocialCommentsJob($account->id, 'post_123');
        $job->handle(app(SocialApiClientInterface::class));

        Http::assertNothingSent();
    }
}
