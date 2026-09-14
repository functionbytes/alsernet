<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketView;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Listado de tickets: refetch en JSON, pestañas resueltas en servidor y
 * vistas compartidas.
 *
 * Cubre el trabajo de sep-2026 sobre /panel/helpdesk/tickets, que no se puede
 * comprobar mirando la pantalla:
 *
 * - El listado responde JSON al mismo endpoint cuando se le pide con Accept:
 *   application/json, para que cambiar de filtro no recargue 350 KB de HTML.
 * - quick_filter lo aplica el SERVIDOR. Antes solo llegaba al JS, que cribaba
 *   los 50 tickets de la página cargada mientras los badges contaban contra
 *   toda la tabla: la pestaña decía 340 y la lista enseñaba 6. El test que
 *   más importa aquí es el que ata el total del paginador al badge.
 * - Una vista marcada como compartida la ve el resto del equipo (la columna
 *   is_shared existía desde el principio y nadie la consultaba).
 */
class TicketsListRefetchTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private User $manager;

    private Customer $customer;

    private TicketStatus $openStatus;

    private TicketStatus $resolvedStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
        $this->manager->givePermissionTo(['helpdesk.tickets.view', 'helpdesk.tickets.update']);

        $this->openStatus = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $this->resolvedStatus = TicketStatus::firstOrCreate(
            ['slug' => 'resolved'],
            ['name' => 'Resolved', 'color' => '#6c757d', 'is_open' => false, 'is_default' => false, 'order' => 3]
        );

        $this->customer = Customer::firstOrCreate(
            ['email' => 'refetch-customer@example.com'],
            ['name' => 'Refetch Customer']
        );
    }

    public function test_el_listado_responde_json_cuando_se_le_pide(): void
    {
        $this->createTicket();

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'tickets',
                'tab_counts',
                'pagination' => ['total', 'per_page', 'current_page', 'last_page', 'from', 'to', 'prev_url', 'next_url'],
            ]);
    }

    public function test_sin_accept_json_el_listado_sigue_devolviendo_la_vista(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index'));

        $response->assertOk();
        $this->assertStringContainsString('<', $response->getContent());
        $this->assertStringContainsString('id="tkt-data"', $response->getContent());
    }

    public function test_la_pestana_filtra_en_el_servidor_y_cuadra_con_su_contador(): void
    {
        // Un resuelto y dos abiertos: si la pestaña se resolviera en cliente,
        // el total del paginador seguiría contando los tres.
        $resuelto = $this->createTicket(['status_id' => $this->resolvedStatus->id, 'subject' => 'Ya resuelto']);
        $this->createTicket(['subject' => 'Abierto uno']);
        $this->createTicket(['subject' => 'Abierto dos']);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.index', ['quick_filter' => 'resolved']));

        $response->assertOk();

        $ids = collect($response->json('tickets'))->pluck('id');

        $this->assertTrue($ids->contains($resuelto->id), 'El resuelto debe salir en su pestaña.');
        $this->assertSame(
            $response->json('tab_counts.resolved'),
            $response->json('pagination.total'),
            'El total del paginador y el badge de la pestaña tienen que ser el mismo número.'
        );
    }

    public function test_la_pestana_de_urgentes_incluye_los_incumplidos_de_sla(): void
    {
        // Mismo criterio que la columna c_urgent de sharedTabCounts(): urgente
        // por prioridad O por SLA incumplido. Si los dos criterios se separan,
        // el badge y la lista vuelven a discrepar.
        $porPrioridad = $this->createTicket(['priority' => 'urgent', 'subject' => 'Urgente de verdad']);
        $porSla = $this->createTicket(['sla_resolution_breached' => true, 'subject' => 'SLA incumplido']);
        $normal = $this->createTicket(['subject' => 'Del montón']);

        $ids = collect(
            $this->actingAs($this->manager)
                ->getJson(route('manager.helpdesk.tickets.index', ['quick_filter' => 'urgent']))
                ->assertOk()
                ->json('tickets')
        )->pluck('id');

        $this->assertTrue($ids->contains($porPrioridad->id));
        $this->assertTrue($ids->contains($porSla->id));
        $this->assertFalse($ids->contains($normal->id));
    }

    public function test_una_pestana_desconocida_no_devuelve_la_tabla_entera(): void
    {
        // El JS trataba un filtro desconocido como slug de estado; el servidor
        // hace lo mismo, y sin coincidencias la lista queda vacía. Lo que NO
        // puede pasar es que se ignore el filtro y salgan todos.
        $this->createTicket();

        $total = $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.index', ['quick_filter' => 'no-existe-este-estado']))
            ->assertOk()
            ->json('pagination.total');

        $this->assertSame(0, $total);
    }

    public function test_el_orden_por_defecto_es_el_que_marca_el_selector(): void
    {
        // El <select> marca "Fecha ↓" sin ?sort; el servidor tiene que ordenar
        // por fecha descendente. Antes el control decía "SLA más urgente" y la
        // lista venía por fecha: el orden solo se cumplía si se tocaba.
        $viejo = $this->createTicket(['subject' => 'El viejo']);
        $viejo->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();
        $nuevo = $this->createTicket(['subject' => 'El nuevo']);

        $ids = collect(
            $this->actingAs($this->manager)
                ->getJson(route('manager.helpdesk.tickets.index'))
                ->json('tickets')
        )->pluck('id');

        $this->assertTrue(
            $ids->search($nuevo->id) < $ids->search($viejo->id),
            'Sin ?sort el listado va por fecha descendente.'
        );
    }

    public function test_las_vistas_compartidas_las_ve_todo_el_equipo(): void
    {
        $otroAgente = User::factory()->create();

        $compartida = TicketView::create([
            'user_id' => $otroAgente->id,
            'name' => 'Urgentes del equipo',
            'filters' => ['priority' => 'urgent'],
            'is_shared' => true,
            'is_system' => false,
        ]);

        $privada = TicketView::create([
            'user_id' => $otroAgente->id,
            'name' => 'Mis cosas',
            'filters' => [],
            'is_shared' => false,
            'is_system' => false,
        ]);

        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($compartida->name, $html);
        $this->assertStringNotContainsString($privada->name, $html, 'Una vista privada de otro agente no debe verse.');
    }

    public function test_guardar_una_vista_puede_compartirla(): void
    {
        // storeView() guardaba is_shared => false a pelo: marcar "compartir"
        // no tenía ningún efecto.
        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.views.store'), [
                'name' => 'Vista del equipo',
                'filters' => ['priority' => 'high'],
                'shared' => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('view.is_shared', true);

        $this->assertDatabaseHas('helpdesk_ticket_views', [
            'name' => 'Vista del equipo',
            'is_shared' => 1,
        ], 'helpdesk');
    }

    public function test_los_chips_arrastran_los_filtros_que_no_controlan(): void
    {
        // La barra es un GET: lo que no viaja en el formulario se pierde. Antes
        // filtrabas por Estado en el modal, tocabas el chip "Prioridad" y el
        // Estado desaparecía sin decir nada.
        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index', [
                'status' => $this->resolvedStatus->id,
                'quick_filter' => 'resolved',
                'sort' => 'date_desc',
                'search' => 'garantía',
            ]))
            ->assertOk()
            ->getContent();

        preg_match('#<form method="get" id="tkt-filter-form".*?</form>#s', $html, $form);
        $this->assertNotEmpty($form, 'La barra de filtros debe estar en la página.');

        foreach (['status', 'quick_filter', 'sort', 'search'] as $campo) {
            $this->assertMatchesRegularExpression(
                '/<input type="hidden" name="'.$campo.'"/',
                $form[0],
                "El filtro '{$campo}' tiene que viajar con el formulario de la barra."
            );
        }

        // 'page' NO se arrastra: cambiar de filtro vuelve a la primera página.
        $this->assertDoesNotMatchRegularExpression('/<input type="hidden" name="page"/', $form[0]);
    }

    public function test_estado_y_grupo_aparecen_como_chip_activo(): void
    {
        // Los dos son filtros reales de TicketFilter y faltaban en la lista de
        // chips: se filtraba por ellos y no había forma de ver que estaban
        // puestos, ni de quitarlos sin borrarlo todo.
        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index', ['status' => $this->resolvedStatus->id]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Estado: '.$this->resolvedStatus->name, $html);
    }

    public function test_limpiar_conserva_el_contexto_de_pantalla(): void
    {
        // "limpiar" quita filtros; la pestaña, el orden y el ticket abierto no
        // son filtros y antes se los llevaba por delante.
        $html = $this->actingAs($this->manager)
            ->get(route('manager.helpdesk.tickets.index', [
                'priority' => 'urgent',
                'quick_filter' => 'closed',
                'sort' => 'date_desc',
            ]))
            ->assertOk()
            ->getContent();

        preg_match('/<a href="([^"]*)" class="tkt-clear-link"/', $html, $m);
        $limpiar = html_entity_decode($m[1] ?? '');

        $this->assertStringContainsString('quick_filter=closed', $limpiar);
        $this->assertStringContainsString('sort=date_desc', $limpiar);
        $this->assertStringNotContainsString('priority=', $limpiar);
    }

    public function test_el_orden_por_sla_tiene_desempate_estable(): void
    {
        // reorder() borra el orden de la query base: sin un segundo criterio,
        // todos los tickets sin vencimiento quedan empatados y el motor los
        // devuelve como quiera — con LIMIT/OFFSET eso son filas repetidas en
        // una página y ausentes en la siguiente.
        // El ?search acota la lista a estos dos: con el orden por SLA, los
        // tickets que SÍ tienen vencimiento van delante y llenarían la primera
        // página en cualquier base con datos, dejando fuera a los de la prueba.
        $marca = 'DesempateSlaTest';
        $viejo = $this->createTicket(['subject' => $marca.' antiguo']);
        $viejo->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
        $nuevo = $this->createTicket(['subject' => $marca.' reciente']);

        $ids = collect(
            $this->actingAs($this->manager)
                ->getJson(route('manager.helpdesk.tickets.index', ['sort' => 'sla', 'search' => $marca]))
                ->assertOk()
                ->json('tickets')
        )->pluck('id');

        $this->assertCount(2, $ids, 'La búsqueda debe dejar solo los dos tickets de la prueba.');

        $this->assertTrue(
            $ids->search($nuevo->id) < $ids->search($viejo->id),
            'Entre dos tickets sin SLA manda la fecha, no el azar del motor.'
        );
    }

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket de prueba',
            'description' => 'Descripción de prueba.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->openStatus->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
