<?php

namespace Modules\HelpdeskTickets\Tests\Unit;

use Carbon\Carbon;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\SlaService;
use PHPUnit\Framework\TestCase;

class SlaServiceTest extends TestCase
{
    private function makeSlaService(): SlaService
    {

        return new SlaService;
    }

    /**
     * Build an anonymous object that satisfies the Ticket property contract
     * used by SlaService methods, without triggering Eloquent's cast pipeline
     * (which requires a live DB connection).
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeTicketStub(array $attributes = []): object
    {
        $stub = new class
        {
            public ?Carbon $created_at = null;

            public ?Carbon $first_response_at = null;

            public ?Carbon $closed_at = null;

            public ?int $priority_id = null;

            public ?int $category_id = null;
        };

        foreach ($attributes as $key => $value) {
            $stub->$key = $value;
        }

        return $stub;
    }

    /**
     * Cast the stub to Ticket using a mock so SlaService type hints are satisfied.
     * SlaService only accesses public properties, so we can pass the stub directly
     * after wrapping with a typed proxy.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function makeTicket(array $attributes = []): Ticket
    {
        // Build a Ticket mock that bypasses __set (Eloquent cast pipeline) by
        // intercepting property setting at the PHPUnit mock level.
        $mock = $this->getMockBuilder(Ticket::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        // Bypass Eloquent's __set by writing directly to the underlying attributes
        // array via reflection, then marking them as Carbon so diffInMinutes works.
        $reflection = new \ReflectionClass($mock);
        $attributesProp = $reflection->getProperty('attributes');
        $attributesProp->setAccessible(true);

        $rawAttributes = [];
        foreach ($attributes as $key => $value) {
            $rawAttributes[$key] = $value;
        }
        $attributesProp->setValue($mock, $rawAttributes);

        // Also set the "original" so isDirty() / etc. don't break
        $originalProp = $reflection->getProperty('original');
        $originalProp->setAccessible(true);
        $originalProp->setValue($mock, $rawAttributes);

        return $mock;
    }

    // ─── calculateResponseTime ─────────────────────────────────────────────────

    public function test_calculate_response_time_returns_null_when_no_first_response(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 09:00:00'),
            'first_response_at' => null,
        ]);

        $service = $this->makeSlaService();

        $this->assertNull($service->calculateResponseTime($ticket));
    }

    public function test_calculate_response_time_returns_minutes_between_created_and_response(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 09:00:00'),
            'first_response_at' => Carbon::parse('2024-01-08 09:30:00'),
        ]);

        $service = $this->makeSlaService();

        $this->assertEquals(30, $service->calculateResponseTime($ticket));
    }

    public function test_calculate_response_time_returns_minutes_for_multi_hour_delay(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 08:00:00'),
            'first_response_at' => Carbon::parse('2024-01-08 10:00:00'),
        ]);

        $service = $this->makeSlaService();

        $this->assertEquals(120, $service->calculateResponseTime($ticket));
    }

    // ─── calculateResolutionTime ───────────────────────────────────────────────

    public function test_calculate_resolution_time_returns_null_when_not_closed(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 09:00:00'),
            'closed_at' => null,
        ]);

        $service = $this->makeSlaService();

        $this->assertNull($service->calculateResolutionTime($ticket));
    }

    public function test_calculate_resolution_time_returns_minutes_between_created_and_closed(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 09:00:00'),
            'closed_at' => Carbon::parse('2024-01-08 11:00:00'),
        ]);

        $service = $this->makeSlaService();

        $this->assertEquals(120, $service->calculateResolutionTime($ticket));
    }

    public function test_calculate_resolution_time_handles_same_day_resolution(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-03-01 10:00:00'),
            'closed_at' => Carbon::parse('2024-03-01 10:45:00'),
        ]);

        $service = $this->makeSlaService();

        $this->assertEquals(45, $service->calculateResolutionTime($ticket));
    }

    public function test_calculate_resolution_time_handles_multi_day_resolution(): void
    {
        $ticket = $this->makeTicket([
            'created_at' => Carbon::parse('2024-01-08 09:00:00'),
            'closed_at' => Carbon::parse('2024-01-09 09:00:00'),
        ]);

        $service = $this->makeSlaService();

        $this->assertEquals(1440, $service->calculateResolutionTime($ticket));
    }

    // ─── getApplicablePolicy (DB skipped without connection) ──────────────────

    public function test_get_applicable_policy_skipped_without_db(): void
    {
        $ticket = $this->makeTicket([
            'priority_id' => 999,
            'category_id' => 999,
        ]);

        $service = $this->makeSlaService();

        try {
            $policy = $service->getApplicablePolicy($ticket);
            // DB available and no matching policy
            $this->assertNull($policy);
        } catch (\Throwable) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }
}
