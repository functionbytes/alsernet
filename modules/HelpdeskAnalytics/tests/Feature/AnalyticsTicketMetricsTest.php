<?php

namespace Modules\HelpdeskAnalytics\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskAnalytics\Services\AnalyticsAggregatorService;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Feature tests for AnalyticsAggregatorService::ticketMetrics(), which extends
 * the omnichannel Analytics dashboard with HelpdeskTickets data without
 * touching HelpdeskTickets' own reporting (HelpdeskReportsController).
 */
class AnalyticsTicketMetricsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Setting::set('tickets.integration_enabled', '1', 'tickets');
    }

    public function test_ticket_metrics_returns_aggregated_totals_for_range(): void
    {
        $from = now()->startOfMonth();
        $to = now()->endOfMonth();

        Ticket::factory()->count(2)->create([
            'priority' => 'high',
            'assignee_id' => null,
        ]);

        Ticket::factory()->create([
            'priority' => 'low',
            'assignee_id' => 1,
            'closed_at' => now(),
            'resolved_at' => now(),
            'first_response_at' => now()->addMinutes(30),
            'sla_resolution_breached' => true,
        ]);

        $metrics = app(AnalyticsAggregatorService::class)->ticketMetrics($from, $to);

        $this->assertSame(3, $metrics['total_created']);
        $this->assertSame(1, $metrics['total_closed']);
        $this->assertSame(1, $metrics['total_resolved']);
        $this->assertSame(1, $metrics['sla_breached']);
        $this->assertSame(2, $metrics['unassigned']);
        $this->assertGreaterThan(0, $metrics['avg_first_response_minutes']);
        $this->assertGreaterThanOrEqual(0, $metrics['avg_resolution_minutes']);

        $priorities = collect($metrics['by_priority'])->pluck('count', 'priority');
        $this->assertSame(2, $priorities['high']);
        $this->assertSame(1, $priorities['low']);
    }

    public function test_ticket_metrics_returns_zeroed_metrics_when_tickets_integration_disabled(): void
    {
        Setting::set('tickets.integration_enabled', '0', 'tickets');

        Ticket::factory()->create();

        $metrics = app(AnalyticsAggregatorService::class)
            ->ticketMetrics(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame([
            'total_created' => 0,
            'total_closed' => 0,
            'total_resolved' => 0,
            'sla_breached' => 0,
            'unassigned' => 0,
            'avg_first_response_minutes' => 0,
            'avg_resolution_minutes' => 0,
            'by_priority' => [],
        ], $metrics);
    }
}
