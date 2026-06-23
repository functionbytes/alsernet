<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAnalytics\Services\AnalyticsAggregatorService;
use Modules\HelpdeskAnalytics\Services\HealthScoreBatchService;
use Tests\TestCase;

/**
 * Feature tests for the cross-channel analytics aggregator.
 *
 * NOTE: requires the helpdesk connection. The shared test snapshot is currently
 * blocked, so these are verified statically (php -l + pint) and via tinker, and
 * will run once the test DB is rebuilt.
 */
class AnalyticsAggregatorTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_health_score_batch_returns_clamped_scores(): void
    {
        $a = Customer::factory()->create();
        $b = Customer::factory()->create();

        $scores = app(HealthScoreBatchService::class)->scoresFor([$a->id, $b->id]);

        $this->assertArrayHasKey($a->id, $scores);
        $this->assertArrayHasKey($b->id, $scores);
        foreach ($scores as $score) {
            $this->assertGreaterThanOrEqual(0, $score);
            $this->assertLessThanOrEqual(100, $score);
        }
    }

    public function test_health_score_batch_is_constant_queries_regardless_of_customer_count(): void
    {
        $customers = Customer::factory()->count(5)->create()->pluck('id')->all();

        DB::connection('helpdesk')->flushQueryLog();
        DB::connection('helpdesk')->enableQueryLog();

        app(HealthScoreBatchService::class)->scoresFor($customers);

        $queryCount = count(DB::connection('helpdesk')->getQueryLog());
        DB::connection('helpdesk')->disableQueryLog();

        // 4 grouped aggregates — must not grow with the number of customers (no N+1).
        $this->assertLessThanOrEqual(5, $queryCount);
    }

    public function test_overview_counts_conversations_in_range(): void
    {
        $customer = Customer::factory()->create();
        Conversation::factory()->count(3)->create(['customer_id' => $customer->id]);

        $overview = app(AnalyticsAggregatorService::class)
            ->overview(now()->startOfMonth(), now()->endOfMonth());

        $this->assertGreaterThanOrEqual(3, $overview['conversations']);
        $this->assertArrayHasKey('csat_avg', $overview);
        $this->assertArrayHasKey('avg_first_response_seconds', $overview);
    }

    public function test_customer_segments_returns_band_counts(): void
    {
        $customer = Customer::factory()->create();
        Conversation::factory()->create(['customer_id' => $customer->id]);

        $segments = app(AnalyticsAggregatorService::class)
            ->customerSegments(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame(
            $segments['total'],
            $segments['healthy'] + $segments['neutral'] + $segments['at_risk']
        );
    }
}
