<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskSocial\Contracts\SocialApiClientInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Jobs\SyncSocialCommentsJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Tests\TestCase;

/**
 * Regression coverage for the `social.integration_enabled` admin toggle
 * (panel/settings/helpdesk/integrations) on SyncSocialCommentsJob.
 *
 * Before this fix, the job dispatched SocialCommentReceived unconditionally,
 * evading the toggle even when the scheduled `helpdesk-social:sync-comments`
 * command was gated — e.g. when the job was triggered manually.
 */
class SyncSocialCommentsJobIntegrationToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function tearDown(): void
    {
        Setting::set('social.integration_enabled', '1', 'integrations');

        parent::tearDown();
    }

    public function test_no_comment_received_event_is_dispatched_when_toggle_is_disabled(): void
    {
        Setting::set('social.integration_enabled', '0', 'integrations');

        Event::fake([SocialCommentReceived::class]);
        Http::fake();

        $account = SocialAccount::factory()->create([
            'platform' => 'facebook',
            'page_access_token' => 'fake_token',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $job = new SyncSocialCommentsJob($account->id, 'post_123');
        $job->handle(app(SocialApiClientInterface::class));

        Event::assertNotDispatched(SocialCommentReceived::class);
        Http::assertNothingSent();
        $this->assertDatabaseCount('helpdesk_social_comments', 0);
    }

    public function test_comment_received_event_is_dispatched_when_toggle_is_enabled(): void
    {
        Setting::set('social.integration_enabled', '1', 'integrations');

        Event::fake([SocialCommentReceived::class]);
        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => 'comment_1',
                        'message' => 'Great post!',
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

        $job = new SyncSocialCommentsJob($account->id, 'post_123');
        $job->handle(app(SocialApiClientInterface::class));

        Event::assertDispatched(SocialCommentReceived::class);
        $this->assertDatabaseCount('helpdesk_social_comments', 1);
    }
}
