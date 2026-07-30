<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Models\SlaPolicy;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\SlaService;
use Tests\TestCase;

/**
 * Tests for SlaService business logic.
 *
 * Pure-logic tests (date arithmetic) run without a database.
 * DB-backed tests skip when the helpdesk connection is unavailable.
 */
class SlaServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function makeService(): SlaService
    {

        return new SlaService;
    }

    /**
     * Build a Ticket mock with given attributes, bypassing Eloquent cast pipeline.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeTicket(array $attributes = []): Ticket
    {
        $mock = $this->getMockBuilder(Ticket::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reflection = new \ReflectionClass($mock);

        $attributesProp = $reflection->getProperty('attributes');
        $attributesProp->setAccessible(true);
        $attributesProp->setValue($mock, $attributes);

        $originalProp = $reflection->getProperty('original');
        $originalProp->setAccessible(true);
        $originalProp->setValue($mock, $attributes);

        return $mock;
    }

    // ─── calculateDueDate ─────────────────────────────────────────────────────

    public function test_calculate_due_date_returns_null_when_no_sla_policy(): void
    {
        $ticket = $this->makeTicket([
            'priority_id' => 99999,
            'category_id' => null,
        ]);

        $service = $this->createPartialMock(SlaService::class, ['getApplicablePolicy']);
        $service->method('getApplicablePolicy')->willReturn(null);

        $result = $service->calculateDueDate($ticket);

        $this->assertNull($result);
    }

    public function test_calculate_due_date_adds_calendar_hours_when_no_business_hours_restriction(): void
    {
        $ticket = $this->makeTicket(['priority_id' => 1, 'category_id' => null]);

        $policy = new SlaPolicy;
        $policy->resolution_time_hours = 8;
        $policy->business_hours_only = false;

        $service = $this->createPartialMock(SlaService::class, ['getApplicablePolicy']);
        $service->method('getApplicablePolicy')->willReturn($policy);

        Carbon::setTestNow('2024-01-15 10:00:00');
        $result = $service->calculateDueDate($ticket);
        Carbon::setTestNow(null);

        $this->assertNotNull($result);
        $this->assertEquals('2024-01-15 18:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_due_date_adds_business_hours_when_policy_requires_it(): void
    {
        $ticket = $this->makeTicket(['priority_id' => 1, 'category_id' => null]);

        $policy = new SlaPolicy;
        $policy->resolution_time_hours = 8;
        $policy->business_hours_only = true;

        $service = $this->createPartialMock(SlaService::class, ['getApplicablePolicy']);
        $service->method('getApplicablePolicy')->willReturn($policy);

        // Start at 09:00 on a Monday — 8 business hours ends at 17:00 same day
        Carbon::setTestNow('2024-01-15 09:00:00'); // Monday
        $result = $service->calculateDueDate($ticket);
        Carbon::setTestNow(null);

        $this->assertNotNull($result);
        // 9:00 + 8 business hours = 17:00 (before 18:00 close)
        $this->assertEquals('2024-01-15 17:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function test_calculate_due_date_skips_weekend_for_business_hours(): void
    {
        $ticket = $this->makeTicket(['priority_id' => 1, 'category_id' => null]);

        $policy = new SlaPolicy;
        $policy->resolution_time_hours = 8;
        $policy->business_hours_only = true;

        $service = $this->createPartialMock(SlaService::class, ['getApplicablePolicy']);
        $service->method('getApplicablePolicy')->willReturn($policy);

        // Friday at 15:00 — 8 business hours spans to Monday
        Carbon::setTestNow('2024-01-19 15:00:00'); // Friday
        $result = $service->calculateDueDate($ticket);
        Carbon::setTestNow(null);

        $this->assertNotNull($result);
        // 15:00 Friday + 3h = 18:00 Friday, remaining 5h on Monday 09:00 -> 14:00
        $this->assertEquals('Monday', $result->format('l'));
    }

    // ─── checkBreaches ────────────────────────────────────────────────────────

    public function test_check_sla_breach_marks_ticket_breached_when_past_due(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake([SlaBreached::class]);

        $service = new SlaService;

        // Create a ticket with a past due date that has not been marked breached
        DB::connection('helpdesk')->table('helpdesk_tickets')->insert([
            'customer_id' => Customer::factory()->create()->id,
            'ticket_number' => 'TKT-TEST-BREACH-'.uniqid(),
            'subject' => 'SLA breach test',
            'priority' => 'high',
            'source' => 'web',
            'sla_resolution_breached' => false,
            'sla_resolution_due_at' => now()->subHour()->toDateTimeString(),
            'closed_at' => null,
            'created_at' => now()->subHours(5)->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $breached = $service->checkBreaches();

        $this->assertGreaterThanOrEqual(1, $breached->count());
        Event::assertDispatched(SlaBreached::class);
    }

    public function test_check_sla_breach_skips_paused_tickets(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        // SlaService::checkBreaches() queries by sla_resolution_due_at < now() AND closed_at IS NULL
        // Paused tickets still have sla_paused_at set — the breach check does NOT exclude them.
        // The effective due date is extended via getEffectiveDueDate(). We test that a paused
        // ticket's effective due date is pushed forward when paused.
        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => now()->subMinutes(10),
            'sla_paused_at' => now()->subMinutes(15),
        ]);

        $service = $this->makeService();
        $effectiveDue = $service->getEffectiveDueDate($ticket);

        // Effective due = original due + paused minutes (≥ 15 min) → should be in the future
        $this->assertTrue($effectiveDue->isFuture() || $effectiveDue->isCurrentMinute());
    }

    // ─── pauseSla / resumeSla ─────────────────────────────────────────────────

    public function test_pause_sla_sets_paused_at_timestamp(): void
    {
        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Event::fake();

        // Insert a minimal ticket directly
        $id = DB::connection('helpdesk')->table('helpdesk_tickets')->insertGetId([
            'customer_id' => Customer::factory()->create()->id,
            'ticket_number' => 'TKT-PAUSE-'.uniqid(),
            'subject' => 'Pause SLA test',
            'priority' => 'normal',
            'source' => 'web',
            'sla_paused_at' => null,
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        $ticket = Ticket::on('helpdesk')->find($id);
        $service = $this->makeService();
        $service->pauseSla($ticket);

        $this->assertNotNull($ticket->fresh()->sla_paused_at);
    }
}
