<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskSocial\Contracts\AutoReplyEngineInterface;
use Modules\HelpdeskSocial\Events\SocialCommentReceived;
use Modules\HelpdeskSocial\Jobs\ProcessSocialCommentJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialIntent;
use Modules\HelpdeskSocial\Services\AuditLogService;
use Modules\HelpdeskSocial\Services\ConversationThreadingService;
use Modules\HelpdeskSocial\Services\CrisisModeService;
use Modules\HelpdeskSocial\Services\IntentClassificationService;
use Modules\HelpdeskSocial\Services\SentimentAnalysisService;
use Modules\HelpdeskSocial\Services\SlaTrackingService;
use Modules\HelpdeskSocial\Services\SmartAssignmentService;
use Modules\HelpdeskSocial\Services\SocialListeningService;
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

    public function test_job_dedupes_mentions_without_external_comment_id(): void
    {
        SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        // Mención sin external_comment_id (caso real FB/IG): antes cada reentrega
        // creaba un duplicado porque la dedupe comparaba external_comment_id = NULL.
        $payload = [
            'platform' => 'facebook',
            'page_id' => 'page_123',
            'external_comment_id' => null,
            'external_post_id' => 'post_789',
            'external_user_id' => 'user_456',
            'author_name' => 'Mentioner',
            'body' => '@marca gran producto',
            'is_mention' => true,
            'posted_at' => now()->toIso8601String(),
        ];

        ProcessSocialCommentJob::dispatch($payload);
        ProcessSocialCommentJob::dispatch($payload);

        $this->assertDatabaseCount('helpdesk_social_comments', 1);
    }

    /**
     * Regression test for the "Integraciones" admin toggle (panel/settings/helpdesk/integrations).
     * With `social.integration_enabled` = '0', helpdesk_social_enabled() is false and the job
     * must bail out before creating any SocialComment.
     */
    public function test_job_skips_processing_when_social_integration_disabled(): void
    {
        Setting::set('social.integration_enabled', '0', 'integrations');

        SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $externalId = 'fb_'.uniqid();

        $job = new ProcessSocialCommentJob([
            'platform' => 'facebook',
            'page_id' => 'page_123',
            'external_comment_id' => $externalId,
            'external_post_id' => 'post_123',
            'external_user_id' => 'user_456',
            'author_name' => 'Test User',
            'body' => 'Test comment body',
            'posted_at' => now()->toIso8601String(),
        ]);

        $job->handle(...$this->processingDependencies());

        $this->assertDatabaseMissing('helpdesk_social_comments', ['external_comment_id' => $externalId]);
    }

    /**
     * With the setting left at its default (no row = falls back to '1'), the job must run
     * its normal processing and persist the incoming comment.
     */
    public function test_job_processes_when_social_integration_enabled_by_default(): void
    {
        SocialAccount::factory()->create([
            'platform' => 'facebook',
            'external_id' => 'page_123',
            'is_active' => true,
            'comments_enabled' => true,
        ]);

        $externalId = 'fb_'.uniqid();

        $job = new ProcessSocialCommentJob([
            'platform' => 'facebook',
            'page_id' => 'page_123',
            'external_comment_id' => $externalId,
            'external_post_id' => 'post_123',
            'external_user_id' => 'user_456',
            'author_name' => 'Test User',
            'body' => 'Test comment body',
            'posted_at' => now()->toIso8601String(),
        ]);

        $job->handle(...$this->processingDependencies());

        $this->assertDatabaseHas('helpdesk_social_comments', ['external_comment_id' => $externalId]);
    }

    /**
     * Real, dependency-free service instances for the job's `handle()` signature.
     *
     * HelpdeskSocial is disabled in this environment's modules_statuses.json, so its
     * ServiceProvider never boots and the container has no binding for
     * IntentClassifierInterface / AutoReplyEngineInterface. Building the collaborators by
     * hand keeps these tests independent of that module-enabled state while still exercising
     * the job's real processing pipeline.
     *
     * @return array{0: IntentClassificationService, 1: AutoReplyEngineInterface, 2: ConversationThreadingService, 3: SentimentAnalysisService, 4: SlaTrackingService, 5: SmartAssignmentService, 6: SocialListeningService, 7: CrisisModeService}
     */
    private function processingDependencies(): array
    {
        Http::preventStrayRequests();
        Http::fake([]);

        $classificationService = new class extends IntentClassificationService
        {
            public function __construct() {}

            public function classify(SocialComment $comment): SocialIntent
            {
                return new SocialIntent;
            }
        };

        $responder = new class implements AutoReplyEngineInterface
        {
            public function evaluate(SocialComment $comment): ?array
            {
                return null;
            }

            public function isEnabled(): bool
            {
                return false;
            }
        };

        return [
            $classificationService,
            $responder,
            new ConversationThreadingService,
            new SentimentAnalysisService,
            new SlaTrackingService,
            new SmartAssignmentService,
            new SocialListeningService,
            new CrisisModeService(new AuditLogService),
        ];
    }
}
