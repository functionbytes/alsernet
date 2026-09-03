<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketFollowup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Notifications\TicketFollowupDueNotification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Secuencia de seguimiento.
 *
 * Antes un followup era un aviso aislado ("recuérdame este ticket el día X").
 * Al esperar documentación de un cliente hace falta una cadena de
 * recordatorios que se corte sola en cuanto conteste: si no, el agente recibe
 * avisos de algo ya resuelto y acaba ignorándolos todos.
 */
class TicketFollowupSequenceTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $role->givePermissionTo('helpdesk.tickets.update');

        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'followup-seq-open'],
            ['name' => 'Abierto (followup test)']
        );
    }

    private function makeTicket(): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Seguimiento',
            'email' => 'seguimiento-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create([
            'subject' => 'Ticket de seguimiento',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);
    }

    // ─── programar ───────────────────────────────────────────────────────────

    public function test_programa_una_secuencia_de_varios_pasos(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), [
                'steps' => [
                    ['scheduled_at' => now()->addDays(3)->toDateTimeString(), 'note' => 'Primer aviso'],
                    ['scheduled_at' => now()->addDays(7)->toDateTimeString(), 'note' => 'Segundo aviso'],
                ],
            ])
            ->assertCreated();

        $this->assertSame(2, $ticket->followups()->count());
    }

    public function test_numera_los_pasos_en_orden_cronologico(): void
    {
        // El número que ve el agente ("paso 2 de 3") no debe depender de en
        // qué orden se tecleó cada fecha.
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), [
                'steps' => [
                    ['scheduled_at' => now()->addDays(9)->toDateTimeString()],
                    ['scheduled_at' => now()->addDays(2)->toDateTimeString()],
                ],
            ])
            ->assertCreated();

        $primero = $ticket->followups()->where('step', 1)->first();
        $segundo = $ticket->followups()->where('step', 2)->first();

        $this->assertTrue($primero->scheduled_at->lt($segundo->scheduled_at));
    }

    public function test_sigue_aceptando_un_recordatorio_suelto(): void
    {
        // Compatibilidad con lo que ya llamaba a este endpoint.
        $ticket = $this->makeTicket();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), [
                'scheduled_at' => now()->addDay()->toDateTimeString(),
                'note' => 'Recordatorio suelto',
            ])
            ->assertCreated();

        $this->assertSame(1, $ticket->followups()->count());
        $this->assertSame(1, $ticket->followups()->first()->step);
    }

    public function test_rechaza_una_secuencia_desmedida(): void
    {
        $ticket = $this->makeTicket();

        $pasos = collect(range(1, 8))
            ->map(fn ($i) => ['scheduled_at' => now()->addDays($i)->toDateTimeString()])
            ->all();

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.tickets.followups.store', $ticket), ['steps' => $pasos])
            ->assertStatus(422);
    }

    // ─── parada automática ───────────────────────────────────────────────────

    public function test_el_paso_se_cancela_si_el_cliente_respondio_antes(): void
    {
        Notification::fake();

        $ticket = $this->makeTicket();
        $followup = TicketFollowup::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->manager->id,
            'scheduled_at' => now()->subMinute(),
            'step' => 1,
            'cancel_if_customer_replies' => true,
            'is_sent' => false,
        ]);

        // Mensaje entrante del cliente: sin user_id y no interno.
        $ticket->items()->create([
            'type' => 'message',
            'body' => 'Ya os he mandado el documento.',
            'is_internal' => false,
        ]);

        $this->artisan('ticket:send-followups')->assertExitCode(0);

        $followup->refresh();
        $this->assertNotNull($followup->cancelled_at);
        $this->assertFalse($followup->is_sent);
        Notification::assertNothingSent();
    }

    public function test_el_paso_se_envia_si_el_cliente_no_ha_respondido(): void
    {
        Notification::fake();

        $ticket = $this->makeTicket();
        $followup = TicketFollowup::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->manager->id,
            'scheduled_at' => now()->subMinute(),
            'step' => 1,
            'cancel_if_customer_replies' => true,
            'is_sent' => false,
        ]);

        $this->artisan('ticket:send-followups')->assertExitCode(0);

        $followup->refresh();
        $this->assertTrue($followup->is_sent);
        $this->assertNull($followup->cancelled_at);
        Notification::assertSentTo($this->manager, TicketFollowupDueNotification::class);
    }

    public function test_sin_la_condicion_activa_el_paso_se_envia_igualmente(): void
    {
        Notification::fake();

        $ticket = $this->makeTicket();
        TicketFollowup::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->manager->id,
            'scheduled_at' => now()->subMinute(),
            'step' => 1,
            'cancel_if_customer_replies' => false,
            'is_sent' => false,
        ]);

        $ticket->items()->create(['type' => 'message', 'body' => 'Respondo', 'is_internal' => false]);

        $this->artisan('ticket:send-followups')->assertExitCode(0);

        Notification::assertSentTo($this->manager, TicketFollowupDueNotification::class);
    }

    // ─── cancelar ────────────────────────────────────────────────────────────

    public function test_cancela_la_secuencia_entera(): void
    {
        $ticket = $this->makeTicket();

        foreach ([2, 5, 9] as $i => $dias) {
            TicketFollowup::create([
                'ticket_id' => $ticket->id,
                'user_id' => $this->manager->id,
                'scheduled_at' => now()->addDays($dias),
                'step' => $i + 1,
                'is_sent' => false,
            ]);
        }

        $this->actingAs($this->manager)
            ->deleteJson(route('manager.helpdesk.tickets.followups.destroy-all', $ticket))
            ->assertOk();

        $this->assertSame(0, $ticket->followups()->pending()->count());
    }

    public function test_cancelar_la_secuencia_no_toca_los_pasos_ya_avisados(): void
    {
        $ticket = $this->makeTicket();

        $enviado = TicketFollowup::create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->manager->id,
            'scheduled_at' => now()->subDay(),
            'step' => 1,
            'is_sent' => true,
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($this->manager)
            ->deleteJson(route('manager.helpdesk.tickets.followups.destroy-all', $ticket))
            ->assertOk();

        $this->assertNull($enviado->refresh()->cancelled_at);
    }
}
