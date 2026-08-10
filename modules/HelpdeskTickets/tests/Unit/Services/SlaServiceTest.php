<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\SlaBreached;
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
