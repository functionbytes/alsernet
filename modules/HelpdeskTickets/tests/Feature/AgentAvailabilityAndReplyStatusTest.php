<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Modules\Helpdesk\Models\AgentSettings;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\Setting;
use Modules\HelpdeskTickets\Events\TicketStatusChanged;
use Modules\HelpdeskTickets\Http\Controllers\Managers\TicketMessagingController;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AgentAvailabilityService;
use Tests\TestCase;

/**
 * Dos comportamientos del panel de tickets pedidos el 7-sep-2026:
 *
 *  1. El estado del ticket cambia solo al responder al cliente (ajuste
 *     tickets.status_on_reply), sin tocar nada en las notas internas.
 *  2. Los selectores de asignación dicen si el agente está realmente operativo:
 *     de los doce agentes de esta instalación, once no han entrado nunca al
 *     panel y el desplegable los ofrecía igual que al resto.
 */
class AgentAvailabilityAndReplyStatusTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $agente;

    private Ticket $ticket;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agente = User::factory()->create(['last_login_at' => now()->subDay()]);
        $this->actingAs($this->agente);

        $this->ticket = Ticket::create([
            'customer_id' => Customer::factory()->create()->id,
            'subject' => 'Ticket para el estado al responder',
            'description' => 'x',
            'priority' => 'normal',
            'status_id' => TicketStatus::where('slug', 'new')->value('id'),
        ]);
    }

    protected function tearDown(): void
    {
        // Setting::set() escribe en BD (que DatabaseTransactions revierte) pero
        // también en la caché, que NO se revierte: sin esto, el ajuste elegido
        // en un test se quedaría vivo para el sistema real hasta que expirase.
        foreach (['tickets.status_on_reply', 'tickets.status_on_reply_slug'] as $clave) {
            Cache::forget('setting.'.$clave);
            Cache::forget($clave);
        }

        // Setting::get memoiza por proceso (no solo en Redis): sin esto, el
        // ajuste elegido en un test se lo llevaría el siguiente de la clase.
        Setting::forgetMemo();

        Cache::forget('helpdesk:catalogs:agents');
        Cache::forget('helpdesktickets:assignable-agents');

        parent::tearDown();
    }

    private function enviar(string $cuerpo, bool $interna): void
    {
        $metodo = new \ReflectionMethod(TicketMessagingController::class, 'createMessageItem');
        $metodo->setAccessible(true);
        $metodo->invoke(app(TicketMessagingController::class), $this->ticket, $cuerpo, $interna, []);
    }

    // ── Estado al responder ───────────────────────────────────

    public function test_responder_al_cliente_cambia_el_estado(): void
    {
        Event::fake([TicketStatusChanged::class]);

        Setting::set('tickets.status_on_reply', true, 'tickets');
        Setting::set('tickets.status_on_reply_slug', 'resolved', 'tickets');

        $this->enviar('Ya está resuelto, un saludo.', false);

        $this->assertSame(
            TicketStatus::where('slug', 'resolved')->value('id'),
            $this->ticket->fresh()->status_id
        );
        $this->assertNotNull($this->ticket->fresh()->resolved_at);
        Event::assertDispatched(TicketStatusChanged::class);
    }

    public function test_una_nota_interna_no_cambia_el_estado(): void
    {
        Setting::set('tickets.status_on_reply', true, 'tickets');

        $antes = $this->ticket->status_id;
        $this->enviar('Aviso para el equipo, no para el cliente.', true);

        // Marcar resuelto por escribirse una nota entre compañeros sería
        // exactamente lo contrario de lo que espera el agente.
        $this->assertSame($antes, $this->ticket->fresh()->status_id);
    }

    public function test_con_el_ajuste_desactivado_el_estado_no_se_toca(): void
    {
        Setting::set('tickets.status_on_reply', false, 'tickets');

        $antes = $this->ticket->status_id;
        $this->enviar('Respuesta al cliente.', false);

        $this->assertSame($antes, $this->ticket->fresh()->status_id);
    }

    public function test_el_estado_destino_es_configurable(): void
    {
        Setting::set('tickets.status_on_reply', true, 'tickets');
        Setting::set('tickets.status_on_reply_slug', 'waiting-customer', 'tickets');

        $this->enviar('¿Nos confirmas el número de pedido?', false);

        $this->assertSame(
            TicketStatus::where('slug', 'waiting-customer')->value('id'),
            $this->ticket->fresh()->status_id
        );
        // resolved_at solo se rellena cuando el destino ES resuelto: si no, los
        // informes contarían como resueltos tickets que están esperando.
        $this->assertNull($this->ticket->fresh()->resolved_at);
    }

    // ── Disponibilidad del agente ─────────────────────────────

    public function test_un_agente_que_nunca_ha_entrado_no_es_asignable(): void
    {
        $nuevo = User::factory()->create(['last_login_at' => null]);

        $estado = app(AgentAvailabilityService::class)->describe(collect([$nuevo]))->first();

        $this->assertFalse($estado['available']);
        $this->assertSame('never_logged', $estado['status']);
    }

    public function test_un_agente_de_vacaciones_no_es_asignable(): void
    {
        AgentSettings::create([
            'user_id' => $this->agente->id,
            'is_available' => true,
            'accepts_conversations' => true,
            'vacation_until' => now()->addWeek(),
        ]);

        $estado = app(AgentAvailabilityService::class)->describe(collect([$this->agente]))->first();

        $this->assertFalse($estado['available']);
        $this->assertSame('vacation', $estado['status']);
    }

    public function test_un_agente_sin_plaza_libre_no_es_asignable(): void
    {
        AgentSettings::create([
            'user_id' => $this->agente->id,
            'is_available' => true,
            'accepts_conversations' => true,
            'max_concurrent_conversations' => 5,
            'current_open_count' => 5,
        ]);

        $estado = app(AgentAvailabilityService::class)->describe(collect([$this->agente]))->first();

        $this->assertFalse($estado['available']);
        $this->assertSame('full', $estado['status']);
        $this->assertStringContainsString('5/5', $estado['status_label']);
    }

    public function test_un_heartbeat_viejo_no_cuenta_como_conectado(): void
    {
        AgentSettings::create([
            'user_id' => $this->agente->id,
            'is_available' => true,
            'accepts_conversations' => true,
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'last_heartbeat_at' => now()->subHour(),
        ]);

        $estado = app(AgentAvailabilityService::class)->describe(collect([$this->agente]))->first();

        // presence_state se queda congelado en 'available' cuando el navegador
        // se cierra de golpe: decir "Conectado" por eso es el estado engañoso
        // que se quiere quitar de los selectores.
        $this->assertSame('offline', $estado['status']);
        $this->assertTrue($estado['available']);
    }

    public function test_un_agente_conectado_sale_como_tal(): void
    {
        AgentSettings::create([
            'user_id' => $this->agente->id,
            'is_available' => true,
            'accepts_conversations' => true,
            'presence_state' => AgentSettings::PRESENCE_AVAILABLE,
            'last_heartbeat_at' => now()->subMinute(),
        ]);

        $estado = app(AgentAvailabilityService::class)->describe(collect([$this->agente]))->first();

        $this->assertSame('online', $estado['status']);
        $this->assertTrue($estado['available']);
    }
}
