<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Events\SlaBreached;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\SlaService;
use Tests\TestCase;

class SlaBreachTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private Customer $customer;

    private TicketStatus $openStatus;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );

        $this->customer = Customer::create([
            'name' => 'SLA Customer',
            'email' => 'sla@example.com',
        ]);
    }

    public function test_sla_service_detects_breach_and_marks_ticket(): void
    {
        Event::fake([SlaBreached::class]);

        // Ticket whose SLA resolution was due 1 hour ago
        $ticket = Ticket::create([
            'subject' => 'Overdue ticket',
            'description' => 'This ticket is overdue.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'sla_resolution_due_at' => Carbon::now()->subHour(),
            'sla_resolution_breached' => false,
        ]);

        $service = app(SlaService::class);
        $breached = $service->checkBreaches();

        $this->assertCount(1, $breached);
        $this->assertEquals($ticket->id, $breached->first()->id);

        $ticket->refresh();
        $this->assertTrue($ticket->sla_resolution_breached);

        Event::assertDispatched(SlaBreached::class, function (SlaBreached $event) use ($ticket): bool {
            return $event->ticket->id === $ticket->id;
        });
    }

    public function test_already_breached_ticket_is_not_counted_again(): void
    {
        Event::fake([SlaBreached::class]);

        Ticket::create([
            'subject' => 'Already breached',
            'description' => 'Already marked as breached.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'sla_resolution_due_at' => Carbon::now()->subHours(2),
            'sla_resolution_breached' => true,
        ]);

        $service = app(SlaService::class);
        $breached = $service->checkBreaches();

        $this->assertCount(0, $breached);
        Event::assertNotDispatched(SlaBreached::class);
    }

    public function test_closed_ticket_is_not_marked_as_breached(): void
    {
        Event::fake([SlaBreached::class]);

        Ticket::create([
            'subject' => 'Closed ticket',
            'description' => 'This was already closed.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'sla_resolution_due_at' => Carbon::now()->subHour(),
            'sla_resolution_breached' => false,
            'closed_at' => Carbon::now()->subMinutes(30),
        ]);

        $service = app(SlaService::class);
        $breached = $service->checkBreaches();

        $this->assertCount(0, $breached);
        Event::assertNotDispatched(SlaBreached::class);
    }

    public function test_ticket_with_future_sla_due_date_is_not_breached(): void
    {
        Event::fake([SlaBreached::class]);

        Ticket::create([
            'subject' => 'Future deadline',
            'description' => 'Plenty of time left.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'portal',
            'sla_resolution_due_at' => Carbon::now()->addHours(4),
            'sla_resolution_breached' => false,
        ]);

        $service = app(SlaService::class);
        $breached = $service->checkBreaches();

        $this->assertCount(0, $breached);
        Event::assertNotDispatched(SlaBreached::class);
    }

    private function helpdeskConnectionAvailable(): bool
    {
        try {
            DB::connection('helpdesk')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
