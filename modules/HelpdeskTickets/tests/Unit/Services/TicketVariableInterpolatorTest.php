<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketVariableInterpolator;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\TestCase;

class TicketVariableInterpolatorTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketVariableInterpolator $interpolator;

    private Customer $customer;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->interpolator = new TicketVariableInterpolator;
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Abierto', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::firstOrCreate(
            ['email' => 'interp@example.com'],
            ['name' => 'Ada Lovelace']
        );
    }

    public function test_replaces_ticket_and_customer_variables(): void
    {
        $ticket = $this->ticket(['priority' => 'high']);

        $text = 'Hola {{customer_name}}, tu ticket {{ticket_number}} ({{ticket_subject}}) '
            .'está en estado {{ticket_status}} con prioridad {{ticket_priority}}.';

        $result = $this->interpolator->interpolate($text, $ticket);

        $this->assertStringContainsString('Hola Ada Lovelace', $result);
        $this->assertStringContainsString($ticket->ticket_number, $result);
        $this->assertStringContainsString('Consulta de prueba', $result);
        $this->assertStringContainsString('Abierto', $result);
        $this->assertStringContainsString('high', $result);
        $this->assertStringNotContainsString('{{', $result);
    }

    public function test_ticket_title_alias_still_works_for_macros(): void
    {
        $ticket = $this->ticket([]);

        $this->assertSame(
            'Asunto: Consulta de prueba',
            $this->interpolator->interpolate('Asunto: {{ticket_title}}', $ticket)
        );
    }

    public function test_null_ticket_or_text_returns_input_unchanged(): void
    {
        $ticket = $this->ticket([]);

        $this->assertSame('sin ticket', $this->interpolator->interpolate('sin ticket', null));
        $this->assertSame('', $this->interpolator->interpolate('', $ticket));
        $this->assertSame('', $this->interpolator->interpolate(null, $ticket));
    }

    private function ticket(array $overrides): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Consulta de prueba',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
