<?php

namespace Modules\Mailing\Tests\Unit\Collections;

use Modules\Mailing\Collections\CampaignCollection;
use Modules\Mailing\Models\Campaign;
use Tests\TestCase;

/**
 * Test suite for CampaignCollection
 *
 * This is an EXAMPLE template. Rename to CampaignCollectionTest.php when ready to use.
 * Tests all custom methods in the CampaignCollection class.
 */
class CampaignCollectionTest extends TestCase
{
    /**
     * Test filtering campaigns by status
     */
    public function test_by_status_filters_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'paused']),
            Campaign::factory()->make(['status' => 'active']),
        ]);

        $active = $campaigns->byStatus('active');

        $this->assertCount(2, $active);
        $this->assertTrue($active->every(fn ($c) => $c->status === 'active'));
    }

    /**
     * Test active() method returns only active campaigns
     */
    public function test_active_filters_active_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'paused']),
            Campaign::factory()->make(['status' => 'completed']),
            Campaign::factory()->make(['status' => 'active']),
        ]);

        $active = $campaigns->active();

        $this->assertCount(2, $active);
        $this->assertTrue($active->every(fn ($c) => $c->status === 'active'));
    }

    /**
     * Test paused() method returns only paused campaigns
     */
    public function test_paused_filters_paused_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'paused']),
            Campaign::factory()->make(['status' => 'paused']),
        ]);

        $paused = $campaigns->paused();

        $this->assertCount(2, $paused);
        $this->assertTrue($paused->every(fn ($c) => $c->status === 'paused'));
    }

    /**
     * Test completed() method returns only completed campaigns
     */
    public function test_completed_filters_completed_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'completed']),
            Campaign::factory()->make(['status' => 'completed']),
        ]);

        $completed = $campaigns->completed();

        $this->assertCount(2, $completed);
        $this->assertTrue($completed->every(fn ($c) => $c->status === 'completed'));
    }

    /**
     * Test draft() method returns only draft campaigns
     */
    public function test_draft_filters_draft_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'draft']),
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'draft']),
        ]);

        $drafts = $campaigns->draft();

        $this->assertCount(2, $drafts);
        $this->assertTrue($drafts->every(fn ($c) => $c->status === 'draft'));
    }

    /**
     * Test totalSent() calculates sum correctly
     */
    public function test_total_sent_calculates_sum(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['emails_sent_count' => 100]),
            Campaign::factory()->make(['emails_sent_count' => 250]),
            Campaign::factory()->make(['emails_sent_count' => 150]),
        ]);

        $this->assertEquals(500, $campaigns->totalSent());
    }

    /**
     * Test totalSent() handles null values
     */
    public function test_total_sent_handles_null(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['emails_sent_count' => 100]),
            Campaign::factory()->make(['emails_sent_count' => null]),
        ]);

        $this->assertEquals(100, $campaigns->totalSent());
    }

    /**
     * Test totalClicks() calculates sum correctly
     */
    public function test_total_clicks_calculates_sum(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['total_clicks' => 50]),
            Campaign::factory()->make(['total_clicks' => 75]),
            Campaign::factory()->make(['total_clicks' => 25]),
        ]);

        $this->assertEquals(150, $campaigns->totalClicks());
    }

    /**
     * Test totalOpens() calculates sum correctly
     */
    public function test_total_opens_calculates_sum(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['total_opens' => 200]),
            Campaign::factory()->make(['total_opens' => 300]),
            Campaign::factory()->make(['total_opens' => 100]),
        ]);

        $this->assertEquals(600, $campaigns->totalOpens());
    }

    /**
     * Test averageOpenRate() calculates correctly
     */
    public function test_average_open_rate_calculates_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 50,
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 200,
                'total_opens' => 100,
            ]),
        ]);

        // Total: 150 opens / 300 sent = 50%
        $this->assertEquals(50.0, $campaigns->averageOpenRate());
    }

    /**
     * Test averageOpenRate() handles zero sent emails
     */
    public function test_average_open_rate_handles_zero_sent(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 0,
                'total_opens' => 0,
            ]),
        ]);

        $this->assertEquals(0.0, $campaigns->averageOpenRate());
    }

    /**
     * Test averageClickRate() calculates correctly
     */
    public function test_average_click_rate_calculates_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_clicks' => 25,
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 200,
                'total_clicks' => 50,
            ]),
        ]);

        // Total: 75 clicks / 300 sent = 25%
        $this->assertEquals(25.0, $campaigns->averageClickRate());
    }

    /**
     * Test averageClickRate() handles zero sent emails
     */
    public function test_average_click_rate_handles_zero_sent(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 0,
                'total_clicks' => 0,
            ]),
        ]);

        $this->assertEquals(0.0, $campaigns->averageClickRate());
    }

    /**
     * Test scheduled() filters future scheduled campaigns
     */
    public function test_scheduled_filters_future_campaigns(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['scheduled_at' => now()->addDays(2)]),
            Campaign::factory()->make(['scheduled_at' => now()->subDays(1)]),
            Campaign::factory()->make(['scheduled_at' => now()->addHours(5)]),
            Campaign::factory()->make(['scheduled_at' => null]),
        ]);

        $scheduled = $campaigns->scheduled();

        $this->assertCount(2, $scheduled);
        $this->assertTrue($scheduled->every(fn ($c) => $c->scheduled_at?->isFuture()));
    }

    /**
     * Test betweenDates() filters by date range
     */
    public function test_between_dates_filters_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['created_at' => now()->subDays(10)]),
            Campaign::factory()->make(['created_at' => now()->subDays(5)]),
            Campaign::factory()->make(['created_at' => now()->subDays(2)]),
            Campaign::factory()->make(['created_at' => now()]),
        ]);

        $filtered = $campaigns->betweenDates(
            now()->subDays(6),
            now()->subDays(1)
        );

        $this->assertCount(2, $filtered);
    }

    /**
     * Test betweenDates() with only start date
     */
    public function test_between_dates_with_only_start_date(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['created_at' => now()->subDays(10)]),
            Campaign::factory()->make(['created_at' => now()->subDays(3)]),
            Campaign::factory()->make(['created_at' => now()]),
        ]);

        $filtered = $campaigns->betweenDates(now()->subDays(5));

        $this->assertCount(2, $filtered);
    }

    /**
     * Test groupedByStatus() groups correctly
     */
    public function test_grouped_by_status(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'active']),
            Campaign::factory()->make(['status' => 'paused']),
            Campaign::factory()->make(['status' => 'completed']),
        ]);

        $grouped = $campaigns->groupedByStatus();

        $this->assertCount(3, $grouped);
        $this->assertCount(2, $grouped['active']);
        $this->assertCount(1, $grouped['paused']);
        $this->assertCount(1, $grouped['completed']);
    }

    /**
     * Test highEngagement() filters campaigns with >50% open rate
     */
    public function test_high_engagement_filters_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 60, // 60% open rate
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 30, // 30% open rate
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 55, // 55% open rate
            ]),
        ]);

        $highEngagement = $campaigns->highEngagement();

        $this->assertCount(2, $highEngagement);
    }

    /**
     * Test lowEngagement() filters campaigns with <10% open rate
     */
    public function test_low_engagement_filters_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 5, // 5% open rate
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 15, // 15% open rate
            ]),
            Campaign::factory()->make([
                'emails_sent_count' => 100,
                'total_opens' => 8, // 8% open rate
            ]),
        ]);

        $lowEngagement = $campaigns->lowEngagement();

        $this->assertCount(2, $lowEngagement);
    }

    /**
     * Test byPerformance() sorts by open rate descending
     */
    public function test_by_performance_sorts_correctly(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'id' => 1,
                'emails_sent_count' => 100,
                'total_opens' => 30, // 30%
            ]),
            Campaign::factory()->make([
                'id' => 2,
                'emails_sent_count' => 100,
                'total_opens' => 70, // 70%
            ]),
            Campaign::factory()->make([
                'id' => 3,
                'emails_sent_count' => 100,
                'total_opens' => 50, // 50%
            ]),
        ]);

        $sorted = $campaigns->byPerformance();

        $this->assertEquals(2, $sorted->first()->id); // 70% first
        $this->assertEquals(1, $sorted->last()->id); // 30% last
    }

    /**
     * Test method chaining works correctly
     */
    public function test_method_chaining(): void
    {
        $campaigns = new CampaignCollection([
            Campaign::factory()->make([
                'status' => 'active',
                'emails_sent_count' => 100,
                'total_opens' => 60,
            ]),
            Campaign::factory()->make([
                'status' => 'active',
                'emails_sent_count' => 200,
                'total_opens' => 80,
            ]),
            Campaign::factory()->make([
                'status' => 'paused',
                'emails_sent_count' => 150,
                'total_opens' => 90,
            ]),
        ]);

        $result = $campaigns
            ->active()
            ->highEngagement()
            ->totalSent();

        $this->assertEquals(300, $result); // Sum of two active high-engagement campaigns
    }

    /**
     * Test empty collection returns appropriate defaults
     */
    public function test_empty_collection_defaults(): void
    {
        $campaigns = new CampaignCollection([]);

        $this->assertEquals(0, $campaigns->totalSent());
        $this->assertEquals(0, $campaigns->totalClicks());
        $this->assertEquals(0, $campaigns->totalOpens());
        $this->assertEquals(0.0, $campaigns->averageOpenRate());
        $this->assertEquals(0.0, $campaigns->averageClickRate());
    }
}
