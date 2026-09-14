<?php

namespace Modules\HelpdeskTickets\Tests\Unit\Services;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketCategory;
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

    public function test_replaces_category_and_fecha_variables(): void
    {
        $category = TicketCategory::firstOrCreate(
            ['slug' => 'soporte-interpolator-test'],
            ['name' => 'Soporte Interpolator']
        );
        $ticket = $this->ticket(['category_id' => $category->id]);

        $result = $this->interpolator->interpolate('{{ticket_category}} — {{fecha}}', $ticket);

        $this->assertStringContainsString('Soporte Interpolator', $result);
        $this->assertStringContainsString(now()->format('d/m/Y'), $result);
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

    public function test_agent_name_uses_firstname_and_lastname_not_missing_name_attribute(): void
    {
        // Bug encontrado 29-ago-2026: User no tiene columna/accessor `name`,
        // asi que {{agent_name}} siempre caia al fallback "Agente" aunque
        // hubiera un agente autenticado.
        $agent = User::factory()->create(['firstname' => 'Marta', 'lastname' => 'Ruiz']);
        $this->actingAs($agent);

        $ticket = $this->ticket([]);

        $this->assertSame('Marta Ruiz', $this->interpolator->interpolate('{{agent_name}}', $ticket));
    }

    public function test_agent_name_falls_back_when_no_authenticated_user(): void
    {
        $ticket = $this->ticket([]);

        $this->assertSame('Agente', $this->interpolator->interpolate('{{agent_name}}', $ticket));
    }

    public function test_does_not_call_erp_when_text_has_no_erp_placeholders(): void
    {
        $ticket = $this->ticket([]);

        $result = $this->interpolator->interpolate('Hola {{customer_name}}', $ticket);

        $this->assertStringContainsString('Ada Lovelace', $result);
    }

    public function test_resolves_erp_placeholders_when_customer_is_linked(): void
    {
        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('Módulo HelpdeskErp no activo en este entorno.');
        }

        $ticket = $this->ticket([]);

        $this->mock(ErpContextService::class, function ($mock) {
            $mock->shouldReceive('getCustomerContext')->once()->andReturn([
                'customer' => ['found' => true, 'id' => 777, 'nif' => 'X1', 'city' => 'Sevilla', 'balance_pending' => 10, 'credit_limit' => 2000],
                'orders' => [['number' => 'PED-1', 'date' => '2026-01-01']],
            ]);
        });

        $text = '{{erp_id_cliente}} {{erp_nif}} {{erp_ciudad}} {{erp_saldo_pendiente}} {{erp_limite_credito}} {{erp_ultimo_pedido_numero}} {{erp_ultimo_pedido_fecha}}';
        $result = $this->interpolator->interpolate($text, $ticket);

        $this->assertSame('777 X1 Sevilla 10 2000 PED-1 2026-01-01', $result);
    }

    public function test_erp_placeholders_resolve_to_empty_when_customer_not_found_in_erp(): void
    {
        if (! helpdesk_erp_enabled()) {
            $this->markTestSkipped('Módulo HelpdeskErp no activo en este entorno.');
        }

        $ticket = $this->ticket([]);

        $this->mock(ErpContextService::class, function ($mock) {
            $mock->shouldReceive('getCustomerContext')->once()->andReturn(['customer' => ['found' => false]]);
        });

        $this->assertSame('', $this->interpolator->interpolate('{{erp_id_cliente}}', $ticket));
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
