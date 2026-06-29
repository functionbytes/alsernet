<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Jobs\ProcessSocialCommentJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

class ProcessSocialCommentJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_job_dispatches_social_comment_received_event(): void
    {
        Event::fake([SocialCommentReceived::class]);

        SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $payload = [
            'platform' => 'facebook',
            'page_id' => 'page_123',
            'external_comment_id' => 'fb_'.uniqid(),
            'external_post_id' => 'post_123',
            'external_user_id' => 'user_456',
            'author_name' => 'Test User',
            'body' => 'Test comment body',
            'posted_at' => now()->toIso8601String(),
        ];

        ProcessSocialCommentJob::dispatch($payload);

        Event::assertDispatched(SocialCommentReceived::class);
    }

    public function test_job_prevents_duplicate_comments(): void
    {
        $externalId = 'fb_'.uniqid();

        SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $payload = [
            'platform' => 'facebook',
            'page_id' => 'page_123',
            'external_comment_id' => $externalId,
            'external_post_id' => 'post_123',
            'external_user_id' => 'user_456',
            'author_name' => 'Test User',
            'body' => 'Test comment body',
            'posted_at' => now()->toIso8601String(),
        ];

        ProcessSocialCommentJob::dispatch($payload);
        ProcessSocialCommentJob::dispatch($payload);

        $this->assertDatabaseCount('helpdesk_social_comments', 1);
    }
}
