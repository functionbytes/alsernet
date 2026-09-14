<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketEmailBlacklist;
use Modules\HelpdeskTickets\Models\TicketQuarantine;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * TicketQuarantineController::release()/confirm() — la mitad que hace
 * defendible al clasificador de spam (ver docblock del controlador): sin
 * estos dos caminos, "retener" y "descartar" un correo real son lo mismo
 * para el cliente.
 */
class TicketQuarantineControllerTest extends TestCase
{
    // NO SharesHelpdeskPdo aquí a propósito: release() envuelve la creación
    // del ticket en DB::connection('helpdesk')->transaction(), que llama a
    // PDO->beginTransaction() sin comprobar antes si el PDO ya está en una
    // transacción (a diferencia de Ticket::generateTicketNumber(), que sí lo
    // comprueba — ver su comentario). Con el PDO de 'helpdesk' compartido con
    // el de 'mariadb' (ya en transacción por DatabaseTransactions), esa
    // segunda llamada revienta con "There is already an active transaction".
    // Transaccionando 'helpdesk' aparte (PDO propio) se evita el choque; no
    // hay FKs cruzadas hacia 'mariadb' en este flujo que necesiten el PDO
    // compartido.
    use DatabaseTransactions;

    use SeedsHelpdeskRoles;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->manager = User::factory()->create();
        $this->manager->assignRole('super-settings');
        $this->manager->givePermissionTo('helpdesk.tickets.settings');

        TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_release_creates_the_ticket_that_should_have_been_created(): void
    {
        $quarantine = $this->createQuarantine([
            'from_email' => 'cliente-real@example.com',
            'from_name' => 'Cliente Real',
            'subject' => 'No puedo acceder a mi cuenta',
            'body_text' => 'Llevo dos días sin poder entrar.',
        ]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.quarantine.release', $quarantine))
            ->assertRedirect();

        $quarantine->refresh();

        $this->assertSame(TicketQuarantine::STATUS_RELEASED, $quarantine->status);
        $this->assertNotNull($quarantine->released_ticket_id);
        $this->assertSame($this->manager->id, $quarantine->reviewed_by);

        $ticket = Ticket::find($quarantine->released_ticket_id);
        $this->assertNotNull($ticket, 'release() debe crear el ticket que se quedó fuera.');
        $this->assertSame('No puedo acceder a mi cuenta', $ticket->subject);
        $this->assertSame('email', $ticket->source);
        $this->assertSame('cliente-real@example.com', $ticket->customer->email);
    }

    public function test_release_a_un_correo_ya_revisado_no_hace_nada(): void
    {
        $quarantine = $this->createQuarantine(['status' => TicketQuarantine::STATUS_CONFIRMED]);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.quarantine.release', $quarantine))
            ->assertRedirect();

        $this->assertNull($quarantine->fresh()->released_ticket_id);
    }

    public function test_confirm_descarta_el_correo_y_bloquea_al_remitente(): void
    {
        $quarantine = $this->createQuarantine(['from_email' => 'spam@example.com']);

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.quarantine.confirm', $quarantine))
            ->assertRedirect();

        $quarantine->refresh();

        $this->assertSame(TicketQuarantine::STATUS_CONFIRMED, $quarantine->status);
        $this->assertSame($this->manager->id, $quarantine->reviewed_by);

        $this->assertDatabaseHas('helpdesk_ticket_email_blacklist', [
            'type' => 'email',
            'value' => 'spam@example.com',
            'is_active' => 1,
        ], 'helpdesk');
    }

    public function test_confirm_no_crea_ticket_alguno(): void
    {
        $quarantine = $this->createQuarantine();

        $this->actingAs($this->manager)
            ->post(route('manager.helpdesk.settings.quarantine.confirm', $quarantine))
            ->assertRedirect();

        $this->assertSame(0, Ticket::where('customer_id', function ($query) use ($quarantine) {
            $query->select('id')->from('helpdesk_customers')->where('email', $quarantine->from_email);
        })->count());
    }

    public function test_release_requires_the_settings_permission(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('helpdesk-agent');
        $quarantine = $this->createQuarantine();

        $this->actingAs($agent)
            ->post(route('manager.helpdesk.settings.quarantine.release', $quarantine))
            ->assertForbidden();

        $this->assertSame(TicketQuarantine::STATUS_PENDING, $quarantine->fresh()->status);
    }

    public function test_confirm_requires_the_settings_permission(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('helpdesk-agent');
        $quarantine = $this->createQuarantine();

        $this->actingAs($agent)
            ->post(route('manager.helpdesk.settings.quarantine.confirm', $quarantine))
            ->assertForbidden();

        $this->assertSame(TicketQuarantine::STATUS_PENDING, $quarantine->fresh()->status);
        $this->assertNull(TicketEmailBlacklist::where('value', $quarantine->from_email)->first());
    }

    public function test_release_requires_authentication(): void
    {
        $quarantine = $this->createQuarantine();

        $this->post(route('manager.helpdesk.settings.quarantine.release', $quarantine))
            ->assertRedirect('/login');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function createQuarantine(array $overrides = []): TicketQuarantine
    {
        return TicketQuarantine::create(array_merge([
            'from_email' => 'quarantine-'.uniqid().'@example.com',
            'from_name' => 'Remitente de prueba',
            'subject' => 'Asunto de prueba',
            'body_text' => 'Cuerpo de prueba.',
            'spam_score' => 0.9,
            'reason' => 'palabra clave sospechosa',
            'status' => TicketQuarantine::STATUS_PENDING,
        ], $overrides));
    }
}
