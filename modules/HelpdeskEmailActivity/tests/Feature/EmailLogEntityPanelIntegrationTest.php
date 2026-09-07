<?php

namespace Modules\HelpdeskEmailActivity\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskEmailActivity\Database\Seeders\HelpdeskEmailActivityPermissionsSeeder;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Tests\TestCase;

/**
 * Cubre el CAMINO COMPLETO del punto de extensión EntityPanelRegistry — no
 * cada lado por separado (eso ya lo hacen EntityPanelRegistryTest en este
 * módulo y TicketEmailLogPanelRendererTest en HelpdeskTickets), sino que el
 * registro real, poblado por HelpdeskTicketsServiceProvider::boot() durante
 * el arranque normal de la app de test, efectivamente conecta con
 * EmailLogController::show() y con el HTML real vía una petición HTTP real
 * — la pieza que ningún test unitario por separado prueba.
 *
 * Se pide con cabecera AJAX a propósito: el HTML del panel de entidad
 * todavía solo lo pinta emails/partials/detail-panel.blade.php (el fragmento
 * AJAX) — emails/index.blade.php (página completa) está pendiente de que el
 * agente de frontend lo cablee. Ver EmailLogController::renderWorkspace().
 */
class EmailLogEntityPanelIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    // Ticket/Customer/TicketStatus viven en 'helpdesk'; EmailLog vive en la
    // conexión default ('mysql' en este entorno) — mismo gotcha ya
    // documentado en TicketEmailLogPanelRendererTest::$connectionsToTransact.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(HelpdeskEmailActivityPermissionsSeeder::class);
    }

    private function viewer(): User
    {
        return tap(User::factory()->create())->givePermissionTo('helpdeskemailactivity.view');
    }

    public function test_email_log_detail_shows_the_related_tickets_panel_injected_by_helpdesktickets(): void
    {
        $customer = Customer::factory()->create();
        $status = TicketStatus::factory()->create(['name' => 'Abierto-integracion']);

        $ticket = Ticket::create([
            'subject' => 'Ticket con el email',
            'description' => 'Descripción.',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $otherTicket = Ticket::create([
            'subject' => 'Otro ticket del mismo cliente',
            'description' => 'Descripción.',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $log = EmailLog::factory()->create([
            'entity_type' => Ticket::class,
            'entity_id' => $ticket->id,
        ]);

        $response = $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.show', $log->uid), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Tickets relacionados de este mismo cliente')
            ->assertSee($otherTicket->ticket_number);

        // El ticket propio no debe aparecer entre los "relacionados" — pero sí
        // en la tarjeta "Entidad relacionada" de más arriba, que desde
        // TicketEmailLogPanelRenderer::summary() muestra su asunto. Por eso la
        // aserción se acota a lo que viene DESPUÉS del título del panel, en vez
        // de exigir que el asunto no aparezca en toda la página.
        $relatedPanel = Str::after($response->getContent(), 'Tickets relacionados de este mismo cliente');

        $this->assertStringNotContainsString(
            'Ticket con el email',
            $relatedPanel,
            'El ticket dueño del email no debe listarse entre los tickets relacionados.',
        );
    }

    public function test_email_log_detail_omits_the_panel_when_the_entity_is_not_a_ticket(): void
    {
        // module=Newsletter/Auth (o cualquier entity_type distinto de Ticket)
        // no debe mostrar NADA de tickets — el panel es condicional al
        // renderer que de verdad haga match, no un bloque siempre presente.
        $log = EmailLog::factory()->create([
            'entity_type' => 'Modules\\Helpdesk\\Models\\Customer',
            'entity_id' => Customer::factory()->create()->id,
        ]);

        $this->actingAs($this->viewer())
            ->get(route('helpdeskemailactivity.show', $log->uid), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertDontSee('Tickets relacionados de este mismo cliente');
    }
}
