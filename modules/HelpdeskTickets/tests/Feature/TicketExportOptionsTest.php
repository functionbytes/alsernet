<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Exportación de tickets: alcance, columnas y contenido real del CSV.
 *
 * Dos columnas salían VACÍAS en todas las filas de todas las exportaciones:
 * "Titulo" leía `$ticket->title`, que no existe (la tabla guarda el asunto en
 * `subject`), y "Agente" leía `$user->name`, que en este User siempre es null.
 */
class TicketExportOptionsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager = User::factory()->create(['firstname' => 'Casilda', 'lastname' => 'Verdemar']);
        $this->manager->assignRole($role);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'export-options-open'],
            ['name' => 'Abierto (export test)']
        );
    }

    private function makeTicket(array $attributes = []): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Exporta',
            'email' => 'exporta-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create(array_merge([
            'subject' => 'Zarandaja pendiente de exportar',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ], $attributes));
    }

    private function csv(array $params): string
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.export', array_merge(['format' => 'csv'], $params)));

        $response->assertOk();

        return $response->streamedContent();
    }

    // ─── contenido ───────────────────────────────────────────────────────────

    public function test_el_csv_incluye_el_asunto_del_ticket(): void
    {
        // Leía `title`, que no existe como columna: la celda salía vacía.
        $this->makeTicket();

        $this->assertStringContainsString('Zarandaja pendiente de exportar', $this->csv(['scope' => 'all']));
    }

    public function test_el_csv_incluye_el_nombre_del_agente(): void
    {
        // Leía ->name, que en este User es siempre null.
        $this->makeTicket(['assignee_id' => $this->manager->id]);

        $this->assertStringContainsString('Casilda Verdemar', $this->csv(['scope' => 'all']));
    }

    // ─── columnas ────────────────────────────────────────────────────────────

    public function test_exporta_solo_las_columnas_pedidas(): void
    {
        $this->makeTicket();

        $csv = $this->csv(['scope' => 'all', 'columns' => 'ticket_number,subject']);

        $this->assertStringContainsString('Numero,Asunto', $csv);
        $this->assertStringNotContainsString('Prioridad', $csv);
    }

    public function test_ignora_columnas_inventadas_y_cae_a_las_de_siempre(): void
    {
        $this->makeTicket();

        $csv = $this->csv(['scope' => 'all', 'columns' => 'lo_que_sea,ni_idea']);

        $this->assertStringContainsString('Numero', $csv);
        $this->assertStringContainsString('Prioridad', $csv);
    }

    // ─── alcance ─────────────────────────────────────────────────────────────

    public function test_el_alcance_seleccion_exporta_solo_los_ids_marcados(): void
    {
        $elegido = $this->makeTicket(['subject' => 'Zarandaja elegida']);
        $this->makeTicket(['subject' => 'Zarandaja descartada']);

        $csv = $this->csv(['scope' => 'selection', 'ids' => (string) $elegido->id]);

        $this->assertStringContainsString('Zarandaja elegida', $csv);
        $this->assertStringNotContainsString('Zarandaja descartada', $csv);
    }

    public function test_una_seleccion_vacia_no_exporta_el_listado_entero(): void
    {
        // Sin esto, marcar cero tickets y pulsar "exportar selección" se
        // llevaría toda la base por descuido.
        $this->makeTicket();

        $csv = $this->csv(['scope' => 'selection', 'ids' => '']);

        $this->assertStringNotContainsString('Zarandaja', $csv);
    }

    public function test_el_alcance_todo_ignora_los_filtros_de_pantalla(): void
    {
        $this->makeTicket(['priority' => 'baja']);

        // Aunque se pida prioridad urgente, "todo" no filtra.
        $csv = $this->csv(['scope' => 'all', 'priority' => 'urgent']);

        $this->assertStringContainsString('Zarandaja pendiente de exportar', $csv);
    }

    // ─── estimación ──────────────────────────────────────────────────────────

    public function test_la_estimacion_cuenta_los_tickets_del_alcance(): void
    {
        $a = $this->makeTicket();
        $b = $this->makeTicket();

        $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.export-estimate', [
                'scope' => 'selection',
                'ids' => $a->id.','.$b->id,
            ]))
            ->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_la_estimacion_publica_el_catalogo_de_columnas(): void
    {
        $columnas = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.export-estimate', ['scope' => 'all']))
            ->assertOk()
            ->json('columns');

        $claves = array_column($columnas, 'key');
        $this->assertContains('subject', $claves);
        $this->assertContains('assignee', $claves);
        $this->assertTrue(collect($columnas)->firstWhere('key', 'subject')['default']);
    }
}
