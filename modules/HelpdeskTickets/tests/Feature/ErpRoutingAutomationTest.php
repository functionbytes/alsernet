<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Events\CustomerErpResolved;
use Modules\HelpdeskErp\Jobs\LinkCustomerToErpJob;
use Modules\HelpdeskErp\Services\ErpFactsService;
use Modules\HelpdeskTickets\Jobs\Helpdesks\FetchTicketEmailsJob;
use Modules\HelpdeskTickets\Listeners\RunAutomationsOnErpResolved;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Support\AutomationCatalog;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

/**
 * Enrutar y priorizar con los datos del cliente en gestión.
 *
 * Las condiciones erp_* no son columnas del ticket: las resuelve
 * ErpFactsService. Y no pueden evaluarse en ticket.created, porque en ese
 * momento la búsqueda sigue en la cola helpdesk-erp — de ahí el disparador
 * ticket.erp_resolved, que es lo que este test comprueba de punta a punta.
 */
class ErpRoutingAutomationTest extends TestCase
{
    use SharesHelpdeskPdo;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::firstOrCreate(
            ['email' => 'erp-routing@example.com'],
            ['name' => 'Cliente con deuda']
        );

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_a_customer_with_debt_gets_the_ticket_escalated(): void
    {
        $this->fakeErpFacts(['erp_linked' => true, 'erp_balance_pending' => 1250.75]);

        Automation::create([
            'name' => 'Deuda pendiente a prioridad alta',
            'trigger_event' => 'ticket.erp_resolved',
            'conditions' => [['field' => 'erp_balance_pending', 'op' => 'greater_than', 'value' => 500]],
            'actions' => [['type' => 'set_priority', 'value' => 'high']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket();

        app(AutomationEngine::class)->handle('ticket.erp_resolved', $ticket);

        $this->assertSame('high', $ticket->fresh()->priority);
    }

    public function test_the_rule_does_not_fire_below_the_threshold(): void
    {
        $this->fakeErpFacts(['erp_linked' => true, 'erp_balance_pending' => 12.0]);

        Automation::create([
            'name' => 'Deuda pendiente a prioridad alta',
            'trigger_event' => 'ticket.erp_resolved',
            'conditions' => [['field' => 'erp_balance_pending', 'op' => 'greater_than', 'value' => 500]],
            'actions' => [['type' => 'set_priority', 'value' => 'high']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket();

        app(AutomationEngine::class)->handle('ticket.erp_resolved', $ticket);

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    /** "No está en gestión" tiene que poder ser una condición como cualquier otra. */
    public function test_a_sender_missing_from_the_erp_can_be_tagged(): void
    {
        $this->fakeErpFacts(['erp_linked' => false]);

        Automation::create([
            'name' => 'Marcar posibles clientes nuevos',
            'trigger_event' => 'ticket.erp_resolved',
            'conditions' => [['field' => 'erp_linked', 'op' => 'equals', 'value' => false]],
            'actions' => [['type' => 'add_tag', 'value' => 'sin-ficha']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket();

        app(AutomationEngine::class)->handle('ticket.erp_resolved', $ticket);

        $this->assertContains('sin-ficha', $ticket->fresh()->tags ?? []);
    }

    /* ── El listener ──────────────────────────────────────────────────────── */

    public function test_the_listener_only_touches_the_ticket_that_triggered_the_lookup(): void
    {
        $this->fakeErpFacts(['erp_linked' => true, 'erp_balance_pending' => 900.0]);

        Automation::create([
            'name' => 'Deuda pendiente a prioridad alta',
            'trigger_event' => 'ticket.erp_resolved',
            'conditions' => [['field' => 'erp_balance_pending', 'op' => 'greater_than', 'value' => 500]],
            'actions' => [['type' => 'set_priority', 'value' => 'high']],
            'is_active' => true,
            'order' => 1,
        ]);

        $source = $this->ticket();
        $other = $this->ticket();

        app(RunAutomationsOnErpResolved::class)->handle(
            new CustomerErpResolved($this->customer->id, 4242, 'linked', 'ticket', $source->id)
        );

        $this->assertSame('high', $source->fresh()->priority);
        // Resolver la ficha de un cliente no es motivo para reordenar tickets
        // antiguos que otro agente ya estaba llevando.
        $this->assertSame('normal', $other->fresh()->priority);
    }

    public function test_the_listener_ignores_events_from_the_inbox(): void
    {
        $this->fakeErpFacts(['erp_linked' => true, 'erp_balance_pending' => 900.0]);

        Automation::create([
            'name' => 'Deuda pendiente a prioridad alta',
            'trigger_event' => 'ticket.erp_resolved',
            'conditions' => [['field' => 'erp_balance_pending', 'op' => 'greater_than', 'value' => 500]],
            'actions' => [['type' => 'set_priority', 'value' => 'high']],
            'is_active' => true,
            'order' => 1,
        ]);

        $ticket = $this->ticket();

        app(RunAutomationsOnErpResolved::class)->handle(
            new CustomerErpResolved($this->customer->id, 4242, 'linked', 'conversation', $ticket->id)
        );

        $this->assertSame('normal', $ticket->fresh()->priority);
    }

    /* ── Respuestas a tickets ya abiertos ─────────────────────────────────── */

    /**
     * La rama que faltaba: hasta ahora la búsqueda solo se pedía al CREAR el
     * ticket. Si el ERP estaba caído ese día, o el cliente aún no estaba de
     * alta en gestión, ninguna respuesta posterior volvía a intentarlo.
     */
    public function test_a_reply_to_an_open_ticket_asks_the_erp_again(): void
    {
        Queue::fake();

        $ticket = $this->ticket();

        $job = new FetchTicketEmailsJob;
        $method = new \ReflectionMethod($job, 'threadedTicket');
        $method->invoke($job, $ticket);

        Queue::assertPushed(LinkCustomerToErpJob::class, function (LinkCustomerToErpJob $pushed) use ($ticket) {
            return (new \ReflectionProperty($pushed, 'sourceType'))->getValue($pushed) === 'ticket'
                && (new \ReflectionProperty($pushed, 'sourceId'))->getValue($pushed) === $ticket->id;
        });
    }

    /* ── Catálogo ─────────────────────────────────────────────────────────── */

    public function test_the_catalog_offers_the_erp_trigger_and_fields(): void
    {
        $triggers = array_column(AutomationCatalog::triggers(), 'value');

        $this->assertContains('ticket.erp_resolved', $triggers);

        $fields = array_column(AutomationCatalog::fields(), 'field');

        // Solo si el módulo está encendido: ofrecer condiciones que nunca van a
        // poder evaluarse confunde más que ayuda.
        if (helpdesk_erp_enabled()) {
            $this->assertContains('erp_linked', $fields);
            $this->assertContains('erp_balance_pending', $fields);
        } else {
            $this->assertNotContains('erp_linked', $fields);
        }
    }

    /** Los importes del ERP llevan decimales: con 'int' se guardaban truncados. */
    public function test_money_conditions_keep_their_decimals(): void
    {
        $this->assertSame(150.5, AutomationCatalog::castValue('float', '150.50'));
    }

    /* ── Helpers ──────────────────────────────────────────────────────────── */

    /**
     * @param  array<string, mixed>  $facts
     */
    private function fakeErpFacts(array $facts): void
    {
        $this->app->instance(ErpFactsService::class, new class($facts) extends ErpFactsService
        {
            /** @param array<string, mixed> $facts */
            public function __construct(private readonly array $facts) {}

            public function forCustomer(?Customer $customer): array
            {
                return $this->facts;
            }
        });
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket de enrutado ERP',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ], $overrides));
    }
}
