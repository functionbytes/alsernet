<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Motor de automatizaciones: triggers y acciones ampliados (add_internal_note,
 * operador not_equals) sobre los nuevos eventos (ticket.updated/assigned/closed).
 */
class AutomationEngineTest extends TestCase
{
    use SharesHelpdeskPdo;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::firstOrCreate(
            ['email' => 'automation@example.com'],
            ['name' => 'Automation Customer']
        );
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_add_internal_note_action_runs_on_a_matching_trigger(): void
    {
        Automation::create([
            'name' => 'Nota en urgentes actualizados',
            'trigger_event' => 'ticket.updated',
            'conditions' => [['field' => 'priority', 'op' => 'equals', 'value' => 'urgent']],
            'actions' => [['type' => 'add_internal_note', 'value' => 'Ticket urgente actualizado']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket(['priority' => 'urgent']);

        app(AutomationEngine::class)->handle('ticket.updated', $ticket);

        $this->assertDatabaseHas('helpdesk_ticket_items', [
            'ticket_id' => $ticket->id,
            'body' => 'Ticket urgente actualizado',
            'is_internal' => true,
        ], 'helpdesk');
    }

    public function test_condition_not_equals_prevents_the_action(): void
    {
        Automation::create([
            'name' => 'Solo si NO es low',
            'trigger_event' => 'ticket.assigned',
            'conditions' => [['field' => 'priority', 'op' => 'not_equals', 'value' => 'low']],
            'actions' => [['type' => 'add_tag', 'value' => 'revisar']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket(['priority' => 'low']);

        app(AutomationEngine::class)->handle('ticket.assigned', $ticket);

        // priority es 'low' → not_equals falla → no se añade el tag.
        $this->assertNotContains('revisar', $ticket->fresh()->tags ?? []);
    }

    public function test_resolved_trigger_runs_its_automation(): void
    {
        Automation::create([
            'name' => 'Etiqueta al resolver',
            'trigger_event' => 'ticket.resolved',
            'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'resuelto-auto']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket([]);

        app(AutomationEngine::class)->handle('ticket.resolved', $ticket);

        $this->assertContains('resuelto-auto', $ticket->fresh()->tags ?? []);
    }

    public function test_inactive_automation_does_not_run(): void
    {
        Automation::create([
            'name' => 'Inactiva',
            'trigger_event' => 'ticket.closed',
            'conditions' => [],
            'actions' => [['type' => 'add_tag', 'value' => 'cerrada']],
            'is_active' => false,
            'order' => 1,
        ]);

        $ticket = $this->ticket([]);

        app(AutomationEngine::class)->handle('ticket.closed', $ticket);

        $this->assertNotContains('cerrada', $ticket->fresh()->tags ?? []);
    }

    private function ticket(array $overrides): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Automation ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
