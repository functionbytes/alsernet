<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Models\Setting;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Mail\OpsAlertMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\MailReputationService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Modal 22 "Reputación y autenticación": los checkboxes "avisar a
 * managers"/"suprimir automáticamente" del footer, evaluados por
 * ticket:check-reputation (programado cada hora) contra la tasa de rebote
 * real de MailReputationService.
 *
 * SharesHelpdeskPdo (no DatabaseTransactions a secas): estos tests escriben
 * TicketMail (conexión 'helpdesk') y Setting (conexión 'mysql', settings
 * REALES si no se transacciona aparte -- ver comentario del propio trait,
 * que documenta el mismo incidente que ya corrompió un canal de correo real
 * en este entorno).
 */
class TicketMailReputationTest extends TestCase
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

        foreach (['helpdesk.tickets.view', 'helpdesk.tickets.update', 'helpdesk.tickets.emails.send'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        Permission::firstOrCreate(['name' => 'manage_helpdesk', 'guard_name' => 'web']);

        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();

        // Ambos apagados por defecto ("do no harm") -- se fijan explícitos
        // en vez de confiar en que no exista la fila, porque otro test de
        // este mismo archivo pudo dejarlos encendidos antes del rollback.
        Setting::set('tickets.reputation_notify_managers', false);
        Setting::set('tickets.reputation_auto_suppress', false);
        Setting::set('tickets.reputation_breached', false);
        Setting::set('tickets.reputation_suppressed', false);
    }

    // ─── comando ─────────────────────────────────────────────────────────────

    public function test_el_comando_no_hace_nada_con_ambos_interruptores_apagados(): void
    {
        Mail::fake();
        $this->mockBounceRate(50);

        $this->artisan('ticket:check-reputation')->assertSuccessful();

        $this->assertFalse($this->boolSetting('tickets.reputation_breached'));
        $this->assertFalse($this->boolSetting('tickets.reputation_suppressed'));
        Mail::assertNothingQueued();
    }

    public function test_avisa_a_managers_al_cruzar_el_umbral(): void
    {
        Mail::fake();
        Setting::set('tickets.reputation_notify_managers', true);
        $manager = User::factory()->create(['email' => 'manager-reputacion@example.invalid']);
        $manager->givePermissionTo('manage_helpdesk');

        $this->mockBounceRate(50); // muy por encima del umbral por defecto (5%)

        $this->artisan('ticket:check-reputation')->assertSuccessful();

        $this->assertTrue($this->boolSetting('tickets.reputation_breached'));
        Mail::assertQueued(OpsAlertMail::class, fn (OpsAlertMail $mail) => $mail->hasTo($manager->email));
    }

    public function test_no_reenvia_el_aviso_si_ya_estaba_avisado_debounce(): void
    {
        // No se asume ningún nº exacto de destinatarios: este entorno
        // comparte BD con managers reales que ya tienen manage_helpdesk. Lo
        // que importa es que la SEGUNDA pasada, con la tasa aún rota, no
        // encole ningún aviso adicional.
        Mail::fake();
        Setting::set('tickets.reputation_notify_managers', true);
        $manager = User::factory()->create();
        $manager->givePermissionTo('manage_helpdesk');
        $this->mockBounceRate(50);

        $this->artisan('ticket:check-reputation')->assertSuccessful();
        $afterFirstRun = Mail::queued(OpsAlertMail::class)->count();
        $this->assertGreaterThan(0, $afterFirstRun);

        $this->artisan('ticket:check-reputation')->assertSuccessful();

        $this->assertSame($afterFirstRun, Mail::queued(OpsAlertMail::class)->count());
    }

    public function test_suprime_automaticamente_el_envio_al_cruzar_el_umbral(): void
    {
        Mail::fake();
        Setting::set('tickets.reputation_auto_suppress', true);
        $this->mockBounceRate(50);

        $this->artisan('ticket:check-reputation')->assertSuccessful();

        $this->assertTrue($this->boolSetting('tickets.reputation_suppressed'));
    }

    public function test_libera_la_supresion_al_recuperarse_la_tasa(): void
    {
        Setting::set('tickets.reputation_auto_suppress', true);
        Setting::set('tickets.reputation_breached', true);
        Setting::set('tickets.reputation_suppressed', true);

        $this->mockBounceRate(0); // sin rebotes: la tasa se recupera

        $this->artisan('ticket:check-reputation')->assertSuccessful();

        $this->assertFalse($this->boolSetting('tickets.reputation_breached'));
        $this->assertFalse($this->boolSetting('tickets.reputation_suppressed'));
    }

    // ─── guardar desde el modal ────────────────────────────────────────────

    public function test_guarda_los_dos_interruptores_desde_el_modal(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.update');

        $this->actingAs($manager)
            ->patchJson(route('manager.helpdesk.tickets.reputation.update'), [
                'notify_managers' => true,
                'auto_suppress' => true,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($this->boolSetting('tickets.reputation_notify_managers'));
        $this->assertTrue($this->boolSetting('tickets.reputation_auto_suppress'));
    }

    public function test_apagar_la_auto_supresion_libera_el_envio_de_inmediato(): void
    {
        Setting::set('tickets.reputation_auto_suppress', true);
        Setting::set('tickets.reputation_suppressed', true);
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.update');

        $this->actingAs($manager)
            ->patchJson(route('manager.helpdesk.tickets.reputation.update'), [
                'notify_managers' => false,
                'auto_suppress' => false,
            ])
            ->assertOk();

        $this->assertFalse($this->boolSetting('tickets.reputation_suppressed'));
    }

    public function test_guardar_exige_permiso_de_gestion(): void
    {
        $agent = User::factory()->create();

        $this->actingAs($agent)
            ->patchJson(route('manager.helpdesk.tickets.reputation.update'), [
                'notify_managers' => true,
                'auto_suppress' => true,
            ])
            ->assertForbidden();
    }

    // ─── efecto real sobre el envío ──────────────────────────────────────────

    public function test_el_envio_se_bloquea_mientras_esta_suprimido(): void
    {
        Queue::fake();
        Setting::set('tickets.reputation_suppressed', true);

        $manager = User::factory()->create();
        $manager->givePermissionTo(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => $ticket->customer->email,
                'subject' => 'No debería salir',
                'body' => '<p>Contenido.</p>',
            ])
            ->assertStatus(422);

        $response->assertJson(['success' => false]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'No debería salir',
            'status' => 'failed',
        ], 'helpdesk');

        Queue::assertNothingPushed();
    }

    public function test_el_envio_sigue_funcionando_si_no_esta_suprimido(): void
    {
        Queue::fake();

        $manager = User::factory()->create();
        $manager->givePermissionTo(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => $ticket->customer->email,
                'subject' => 'Sí debería salir',
                'body' => '<p>Contenido.</p>',
            ])
            ->assertCreated();

        Queue::assertPushed(SendQueuedMailable::class);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function boolSetting(string $key): bool
    {
        return filter_var(Setting::get($key, false), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * ticket:check-reputation solo lee rates.bounce_rate del reporte — se
     * mockea en vez de generar TicketMail reales porque MailReputationService
     * calcula sobre TODO helpdesk_ticket_mails de los últimos 30 días (por
     * diseño: es la reputación global del dominio, no de un ticket), y este
     * entorno comparte la BD con actividad real de otros tests/uso — crear
     * "2 de cada 4 rebotados" no garantiza cruzar el umbral si ya hay
     * cientos de envíos reales de fondo.
     */
    private function mockBounceRate(float $pct): void
    {
        $this->mock(MailReputationService::class, function ($mock) use ($pct) {
            $mock->shouldReceive('report')->andReturn([
                'domain' => 'alvarez.mx',
                'from' => 'soporte@alvarez.mx',
                'auth' => null,
                'rates' => ['window_days' => 30, 'sent' => 100, 'bounced' => (int) $pct, 'bounce_rate' => $pct, 'suppressed' => 0],
                'settings' => ['notify_managers' => false, 'auto_suppress' => false, 'suppressed' => false],
            ]);
        });
    }

    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Ticket de reputación',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    private function createMail(Ticket $ticket, array $overrides = []): TicketMail
    {
        return TicketMail::create(array_merge([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@alvarez.mx',
            'to' => 'cliente@example.com',
            'subject' => 'Test mail',
            'body_html' => '<p>Test</p>',
            'body_text' => 'Test',
            'status' => 'sent',
            'message_id' => '<'.uniqid().'@alvarez.mx>',
        ], $overrides));
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
