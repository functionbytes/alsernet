<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskContacts\Services\ContactAggregatorService;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\CustomerSummaryService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Modal 25 "Cliente 360": estadística de 1ª respuesta (propia de
 * HelpdeskTickets, siempre calculada) y pedidos PrestaShop (bajo demanda,
 * vía HelpdeskContacts — mockeado, nunca llama al bridge real en tests).
 */
class CustomerSummaryServiceTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_avg_first_response_minutes_promedia_los_tickets_con_dato(): void
    {
        $this->makeTicketWithTiming(now()->subHours(2), now()->subHours(2)->addMinutes(10));
        $this->makeTicketWithTiming(now()->subHours(2), now()->subHours(2)->addMinutes(30));
        // Sin first_response_at: no debe contar como "0 minutos" y bajar la media.
        $this->makeTicket();

        $resumen = app(CustomerSummaryService::class)->summarize($this->customer);

        $this->assertSame(20.0, $resumen['avg_first_response_minutes']);
    }

    public function test_avg_first_response_minutes_es_null_sin_ningun_dato(): void
    {
        $this->makeTicket();

        $resumen = app(CustomerSummaryService::class)->summarize($this->customer);

        $this->assertNull($resumen['avg_first_response_minutes']);
    }

    public function test_prestashop_orders_sin_helpdeskcontacts_disponible_no_falla(): void
    {
        // Sin mockear ContactAggregatorService: si HelpdeskContacts está
        // deshabilitado en este entorno, el guard ya corta antes de tocar
        // nada real; si está habilitado, la llamada real puede fallar (sin
        // bridge accesible en test) pero prestashopOrders() la atrapa.
        $resumen = app(CustomerSummaryService::class)->prestashopOrders($this->customer);

        $this->assertArrayHasKey('available', $resumen);
        $this->assertArrayHasKey('orders', $resumen);
    }

    public function test_prestashop_orders_normaliza_la_forma_del_pedido(): void
    {
        $this->mock(ContactAggregatorService::class, function ($mock) {
            $mock->shouldReceive('prestashop')->once()->andReturn([
                'available' => true,
                'orders' => [
                    ['reference' => 'ABC123', 'placed_at' => '2026-01-01 10:00:00', 'totals' => ['total' => 49.9], 'currency_sign' => '€', 'state' => ['name' => 'Entregado']],
                ],
            ]);
        });

        $resumen = app(CustomerSummaryService::class)->prestashopOrders($this->customer);

        $this->assertTrue($resumen['available']);
        $this->assertSame([
            'reference' => 'ABC123',
            'placed_at' => '2026-01-01 10:00:00',
            'total' => 49.9,
            'currency_sign' => '€',
            'state' => 'Entregado',
        ], $resumen['orders'][0]);
    }

    public function test_customer_orders_endpoint_exige_ver_el_ticket(): void
    {
        $this->mock(ContactAggregatorService::class, function ($mock) {
            $mock->shouldReceive('prestashop')->andReturn(['available' => false, 'orders' => []]);
        });

        $agent = User::factory()->create();
        $agent->givePermissionTo('helpdesk.tickets.view');
        $ticket = $this->makeTicket();

        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.tickets.customer-360.orders', $ticket))
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_customer_orders_endpoint_rechaza_sin_permiso(): void
    {
        $agent = User::factory()->create(); // sin helpdesk.tickets.view ni ser el asignado
        $ticket = $this->makeTicket();

        $this->actingAs($agent)
            ->getJson(route('manager.helpdesk.tickets.customer-360.orders', $ticket))
            ->assertForbidden();
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket cliente 360',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    /**
     * created_at no es fillable (protección estándar de Eloquent) — se
     * guarda tal cual al crear y se pisa después con forceFill()+save(),
     * que ya no dispara updateTimestamps() sobre created_at porque el
     * modelo existe (esa rama solo actúa en el insert).
     */
    private function makeTicketWithTiming($createdAt, $firstResponseAt): Ticket
    {
        $ticket = $this->makeTicket(['first_response_at' => $firstResponseAt]);
        $ticket->forceFill(['created_at' => $createdAt])->save();

        return $ticket;
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
