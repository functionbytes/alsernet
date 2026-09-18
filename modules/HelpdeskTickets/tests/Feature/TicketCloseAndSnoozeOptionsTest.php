<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Models\TicketWatcher;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Opciones del cierre, del aplazamiento y de los seguidores.
 *
 * Tres cosas que la interfaz prometía a medias: cerrar no dejaba clasificar
 * el caso ni evitar la encuesta, aplazar consumía el SLA del rato aplazado, y
 * seguir un ticket no daba ningún aviso ni permitía elegir cuáles.
 */
class TicketCloseAndSnoozeOptionsTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        TicketStatus::firstOrCreate(['slug' => 'closed'], ['name' => 'Cerrado']);
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'close-options-open'],
            ['name' => 'Abierto (close options)']
        );
    }

    private function makeTicket(array $attributes = []): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Cierre',
            'email' => 'cierre-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create(array_merge([
            'subject' => 'Ticket de cierre',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ], $attributes));
    }

    // ─── cierre ──────────────────────────────────────────────────────────────

    public function test_guarda_la_causa_raiz_y_el_resumen_del_cierre(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), [
                'reason' => 'resolved',
                'root_cause' => 'documentation',
                'summary' => 'Faltaba la constancia fiscal; el cliente la envió.',
            ])
            ->assertOk();

        $ticket->refresh();
        $this->assertSame('documentation', $ticket->close_root_cause);
        $this->assertSame('Faltaba la constancia fiscal; el cliente la envió.', $ticket->close_summary);
    }

    public function test_descarta_una_causa_raiz_que_no_esta_en_el_catalogo(): void
    {
        // Es un campo de informe: una clave inventada lo estropearía en
        // silencio al agrupar.
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), [
                'reason' => 'resolved',
                'root_cause' => 'lo-que-sea',
            ])
            ->assertOk();

        $this->assertNull($ticket->refresh()->close_root_cause);
    }

    public function test_permite_cerrar_sin_enviar_la_encuesta_de_satisfaccion(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), [
                'reason' => 'spam',
                'skip_survey' => 1,
            ])
            ->assertOk();

        $this->assertTrue($ticket->refresh()->close_skip_survey);
    }

    public function test_por_defecto_el_cierre_sigue_enviando_la_encuesta(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.close', $ticket), ['reason' => 'resolved'])
            ->assertOk();

        $this->assertFalse($ticket->refresh()->close_skip_survey);
    }

    // ─── aplazamiento ────────────────────────────────────────────────────────

    public function test_aplazar_puede_pausar_el_reloj_del_sla(): void
    {
        // Sin esto, un ticket aplazado tres días volvía marcado como vencido
        // sin que nadie hubiera podido trabajarlo.
        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->addHours(4)]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.snooze', $ticket), [
                'snoozed_until' => now()->addDay()->toDateTimeString(),
                'pause_sla' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.sla_paused', true);

        $this->assertNotNull($ticket->refresh()->sla_paused_at);
    }

    public function test_aplazar_sin_pausar_deja_el_sla_corriendo(): void
    {
        $ticket = $this->makeTicket(['sla_resolution_due_at' => now()->addHours(4)]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.snooze', $ticket), [
                'snoozed_until' => now()->addDay()->toDateTimeString(),
            ])
            ->assertOk();

        $this->assertNull($ticket->refresh()->sla_paused_at);
    }

    public function test_reactivar_devuelve_el_plazo_consumido_mientras_estaba_aplazado(): void
    {
        $vencimiento = now()->addHours(4);
        $ticket = $this->makeTicket([
            'sla_resolution_due_at' => $vencimiento,
            'sla_paused_at' => now()->subMinutes(90),
        ]);

        $this->actingAs($this->manager)
            ->deleteJson(route('manager.helpdesk.tickets.unsnooze', $ticket))
            ->assertOk();

        $ticket->refresh();
        $this->assertNull($ticket->sla_paused_at);
        // El vencimiento se desplaza por el tiempo que estuvo pausado.
        $this->assertGreaterThan($vencimiento->addMinutes(80), $ticket->sla_resolution_due_at);
    }

    // ─── seguidores ──────────────────────────────────────────────────────────

    public function test_un_seguidor_nuevo_recibe_todos_los_avisos_por_defecto(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.watch', $ticket))
            ->assertOk();

        $watcher = TicketWatcher::where('ticket_id', $ticket->id)->first();
        $this->assertTrue($watcher->notify_customer_replies);
        $this->assertTrue($watcher->notify_internal_notes);
    }

    public function test_guarda_las_preferencias_de_aviso_del_seguidor(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.watch', $ticket), [
                'notify_customer_replies' => 1,
                'notify_internal_notes' => 0,
            ])
            ->assertOk();

        $watcher = TicketWatcher::where('ticket_id', $ticket->id)->first();
        $this->assertTrue($watcher->notify_customer_replies);
        $this->assertFalse($watcher->notify_internal_notes);
    }
}
