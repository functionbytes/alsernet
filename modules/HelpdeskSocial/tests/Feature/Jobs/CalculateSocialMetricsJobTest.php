<?php

namespace Modules\HelpdeskSocial\Tests\Feature\Jobs;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskSocial\Jobs\CalculateSocialMetricsJob;
use Modules\HelpdeskSocial\Models\SocialAccount;
use Modules\HelpdeskSocial\Models\SocialComment;
use Modules\HelpdeskSocial\Models\SocialMetrics;
use Modules\HelpdeskSocial\Tests\TestCase;

class CalculateSocialMetricsJobTest extends TestCase
{
    use DatabaseTransactions;

    public function test_calculates_metrics_for_date(): void
    {
        $today = now()->toDateString();

        SocialComment::factory()->count(3)->create([
            'platform' => 'facebook',
            'posted_at' => now(),
            'status' => 'pending',
        ]);

        SocialComment::factory()->create([
            'platform' => 'facebook',
            'posted_at' => now(),
            'status' => 'escalated',
        ]);

        $job = new CalculateSocialMetricsJob($today);
        $job->handle();

        $this->assertDatabaseHas('helpdesk_social_metrics', [
            'date' => $today,
            'social_account_id' => null,
            'comments_received' => 4,
            'escalated_count' => 1,
        ]);

        $metric = SocialMetrics::query()
            ->where('date', $today)
            ->whereNull('social_account_id')
            ->first();

        $this->assertNotNull($metric);
        $this->assertArrayHasKey('positive', $metric->sentiment_breakdown);
        $this->assertArrayHasKey('neutral', $metric->sentiment_breakdown);
        $this->assertArrayHasKey('negative', $metric->sentiment_breakdown);
    }

    public function test_skips_when_no_comments(): void
    {
        $today = now()->toDateString();

        $job = new CalculateSocialMetricsJob($today);
        $job->handle();

        $this->assertDatabaseMissing('helpdesk_social_metrics', [
            'date' => $today,
        ]);
    }

    public function test_calculates_metrics_for_specific_account(): void
    {
        $today = now()->toDateString();

        $account1 = SocialAccount::factory()->create();
        $account2 = SocialAccount::factory()->create();

        SocialComment::factory()->count(2)->create([
            'social_account_id' => $account1->id,
            'platform' => 'facebook',
            'posted_at' => now(),
        ]);

        SocialComment::factory()->count(3)->create([
            'social_account_id' => $account2->id,
            'platform' => 'instagram',
            'posted_at' => now(),
        ]);

        $job = new CalculateSocialMetricsJob($today, $account1->id);
        $job->handle();

        $this->assertDatabaseHas('helpdesk_social_metrics', [
            'date' => $today,
            'social_account_id' => $account1->id,
            'comments_received' => 2,
        ]);

        $this->assertDatabaseMissing('helpdesk_social_metrics', [
            'date' => $today,
            'social_account_id' => $account2->id,
        ]);
    }
}
