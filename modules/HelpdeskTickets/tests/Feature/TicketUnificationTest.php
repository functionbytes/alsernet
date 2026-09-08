<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Mail\TicketsUnifiedMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketLink;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\TicketUnificationService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Unificar duplicados (v2 del aviso de duplicados).
 *
 * Caso real: el cliente manda tres correos el mismo día y el buzón abre tres
 * tickets. La v1 solo fusionaba de uno en uno y borraba el origen; aquí se
 * unifican N conservando el más reciente, cerrando el resto (sin borrarlos) y
 * avisando al cliente con el resumen.
 */
class TicketUnificationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private Customer $cliente;

    private Ticket $primero;

    private Ticket $segundo;

    private Ticket $ultimo;

    protected function setUp(): void
    {
        parent::setUp();

        // TicketPolicy::merge exige helpdesk.tickets.update, y los endpoints de
        // la v2 lo comprueban ticket a ticket. Se da al USUARIO, no al rol, para
        // no tocar lo que pueden hacer los agentes reales.
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);

        $usuario = User::factory()->create();
        $usuario->givePermissionTo('helpdesk.tickets.update', 'helpdesk.tickets.view');
        // Las rutas de managers.php van además detrás de
        // role:super-admin|super-settings, así que sin el rol los endpoints
        // devuelven 403 antes de llegar al controlador.
        $usuario->assignRole(Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($usuario);
        $this->cliente = Customer::factory()->create();

        $this->primero = $this->crearTicket('Problema con el pedido 45012', '-3 hours');
        $this->segundo = $this->crearTicket('Re: Problema con el pedido 45012', '-2 hours');
        $this->ultimo = $this->crearTicket('Problema con el pedido 45012 (urgente)', '-1 hour');
    }

    private function crearTicket(string $asunto, string $cuando): Ticket
    {
        $ticket = Ticket::create([
            'customer_id' => $this->cliente->id,
            'subject' => $asunto,
            'description' => 'Cuerpo del correo',
            'priority' => 'normal',
            'source' => 'email',
            'status_id' => TicketStatus::where('slug', 'new')->value('id'),
        ]);

        $ticket->forceFill(['created_at' => now()->modify($cuando)])->saveQuietly();
        $ticket->items()->create(['type' => 'message', 'body' => 'Mensaje del cliente', 'is_internal' => false]);

        return $ticket;
    }

    private function unificar(bool $avisar = true): array
    {
        return app(TicketUnificationService::class)->unify(
            $this->ultimo,
            collect([$this->primero, $this->segundo]),
            $avisar,
        );
    }

    // ── El resultado de unificar ──────────────────────────────

    public function test_los_duplicados_se_cierran_pero_no_se_borran(): void
    {
        Mail::fake();

        $this->unificar();

        foreach ([$this->primero, $this->segundo] as $cerrado) {
            $fresco = Ticket::find($cerrado->id);

            // A diferencia de la fusión de la v1 (TicketMergeService borra el
            // origen), aquí el número sigue existiendo: el cliente puede
            // mencionarlo y el agente tiene que poder encontrarlo.
            $this->assertNotNull($fresco, 'el duplicado no debe borrarse');
            $this->assertNotNull($fresco->closed_at);
            $this->assertStringContainsString($this->ultimo->ticket_number, (string) $fresco->close_reason);
        }

        $this->assertNull(Ticket::find($this->ultimo->id)->closed_at, 'el superviviente sigue abierto');
    }

    public function test_los_mensajes_pasan_al_ticket_que_queda(): void
    {
        Mail::fake();

        $this->unificar();

        // Uno suyo + uno de cada duplicado = 3 mensajes visibles.
        $this->assertSame(
            3,
            Ticket::find($this->ultimo->id)->items()->where('is_internal', false)->count()
        );
    }

    public function test_el_que_queda_recibe_una_nota_con_el_resumen(): void
    {
        Mail::fake();

        $this->unificar();

        $nota = Ticket::find($this->ultimo->id)->items()
            ->where('is_internal', true)
            ->latest('id')
            ->value('body');

        $this->assertStringContainsString('Unificados 2', (string) $nota);
        $this->assertStringContainsString($this->primero->ticket_number, (string) $nota);
        $this->assertStringContainsString($this->segundo->ticket_number, (string) $nota);
    }

    public function test_los_duplicados_quedan_enlazados_al_que_queda(): void
    {
        Mail::fake();

        $this->unificar();

        // Se comprueban SUS dos enlaces, no el total de la fila: contar todos
        // hacía fallar el test cuando otra clase de la misma corrida dejaba
        // enlaces sueltos en la tabla.
        $enlazados = TicketLink::where('ticket_id', $this->ultimo->id)
            ->pluck('linked_ticket_id')
            ->all();

        $this->assertContains($this->primero->id, $enlazados);
        $this->assertContains($this->segundo->id, $enlazados);
    }

    // ── Aviso al cliente ──────────────────────────────────────

    public function test_avisa_al_cliente_con_el_resumen(): void
    {
        Mail::fake();

        $resultado = $this->unificar(true);

        $this->assertTrue($resultado['notified']);
        Mail::assertQueued(TicketsUnifiedMail::class, function (TicketsUnifiedMail $mail) {
            return str_contains($mail->emailContent, $this->primero->ticket_number)
                && str_contains($mail->emailContent, $this->ultimo->ticket_number);
        });
    }

    public function test_sin_marcar_la_casilla_no_se_avisa(): void
    {
        Mail::fake();

        $resultado = $this->unificar(false);

        $this->assertFalse($resultado['notified']);
        Mail::assertNothingQueued();
    }

    // ── Guardas ───────────────────────────────────────────────

    public function test_no_se_unifica_un_ticket_consigo_mismo(): void
    {
        Mail::fake();

        $resultado = app(TicketUnificationService::class)
            ->unify($this->ultimo, collect([$this->ultimo]), true);

        $this->assertSame([], $resultado['closed']);
        $this->assertNull(Ticket::find($this->ultimo->id)->closed_at);
    }

    public function test_un_ticket_ya_cerrado_no_se_vuelve_a_unificar(): void
    {
        Mail::fake();

        $this->primero->close('Cerrado antes de la prueba');

        $resultado = app(TicketUnificationService::class)
            ->unify($this->ultimo, collect([$this->primero]), true);

        // Volver a unificarlo dejaría otra nota y otro correo al cliente por
        // algo que ya se hizo.
        $this->assertSame([], $resultado['closed']);
        Mail::assertNothingQueued();
    }

    // ── Endpoints ─────────────────────────────────────────────

    public function test_el_resumen_sugiere_conservar_el_mas_reciente(): void
    {
        $datos = $this->getJson(route('manager.helpdesk.tickets.unify.summary', $this->primero))
            ->assertOk()
            ->json();

        $this->assertSame($this->ultimo->id, $datos['suggested_survivor_id']);

        $numeros = array_column($datos['tickets'], 'ticket_number');
        $this->assertContains($this->ultimo->ticket_number, $numeros);
        $this->assertContains($this->primero->ticket_number, $numeros);
    }

    public function test_el_resumen_trae_lo_necesario_para_decidir(): void
    {
        $primero = $this->getJson(route('manager.helpdesk.tickets.unify.summary', $this->ultimo))
            ->assertOk()
            ->json('tickets.0');

        foreach (['ticket_number', 'subject', 'status', 'created_at', 'messages', 'last_message'] as $clave) {
            $this->assertArrayHasKey($clave, $primero);
        }
    }

    public function test_no_unifica_tickets_de_otro_cliente(): void
    {
        Mail::fake();

        $ajeno = Ticket::create([
            'customer_id' => Customer::factory()->create()->id,
            'subject' => 'Ticket de otro cliente',
            'description' => 'x',
            'priority' => 'normal',
            'status_id' => TicketStatus::where('slug', 'new')->value('id'),
        ]);

        // Mezclar clientes movería la conversación de uno al historial del otro.
        $this->postJson(route('manager.helpdesk.tickets.unify', $this->ultimo), [
            'survivor_id' => $this->ultimo->id,
            'ticket_ids' => [$ajeno->id],
        ])->assertStatus(422);

        $this->assertNull(Ticket::find($ajeno->id)->closed_at);
    }
}
