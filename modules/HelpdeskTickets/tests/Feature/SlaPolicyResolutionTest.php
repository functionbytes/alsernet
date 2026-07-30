<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Modules\HelpdeskTickets\Models\Priority;
use Modules\HelpdeskTickets\Models\SlaPolicy;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Services\SlaService;
use Tests\TestCase;

/**
 * Regresión del enrutado SLA por prioridad.
 *
 * El ticket guarda `priority` como slug en inglés (low/medium/high/normal/urgent)
 * mientras la tabla legacy helpdesk_sla_policies enruta por `priority_id` (id de
 * helpdesk_priorities, slug en español). El código anterior comparaba
 * $ticket->priority_id —columna inexistente— que Eloquent convertía en
 * `WHERE priority_id IS NULL`, así que la prioridad se ignoraba y solo podía
 * casar una política global. Ahora el slug se resuelve a su id antes de casar.
 */
class SlaPolicyResolutionTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        // El servicio cachea el mapa slug=>id 5 min; limpiarlo evita arrastres.
        Cache::forget('helpdesktickets:priority_slug_map');
    }

    public function test_english_priority_slug_routes_to_spanish_sla_policy(): void
    {
        $alta = Priority::firstOrCreate(
            ['slug' => 'alta'],
            ['name' => 'Alta', 'level' => 3, 'color' => '#FFC107', 'resolution_time_hours' => 24, 'is_active' => true]
        );

        SlaPolicy::create([
            'name' => 'SLA Alta (test)',
            'priority_id' => $alta->id,
            'category_id' => null,
            'first_response_time_hours' => 8,
            'resolution_time_hours' => 24,
            'is_active' => true,
        ]);

        $ticket = new Ticket(['priority' => 'high']);
        $ticket->category_id = null;

        $policy = app(SlaService::class)->getApplicablePolicy($ticket);

        $this->assertNotNull($policy, "'high' debe resolver a una política SLA, no a NULL.");
        $this->assertSame($alta->id, $policy->priority_id, "'high' debe enrutar a la prioridad 'alta'.");
    }

    public function test_ticket_without_priority_falls_back_to_the_global_policy(): void
    {
        SlaPolicy::create([
            'name' => 'SLA Global (test)',
            'priority_id' => null,
            'category_id' => null,
            'first_response_time_hours' => 24,
            'resolution_time_hours' => 48,
            'is_active' => true,
        ]);

        $ticket = new Ticket(['priority' => null]);
        $ticket->category_id = null;

        $policy = app(SlaService::class)->getApplicablePolicy($ticket);

        $this->assertNotNull($policy, 'Sin prioridad debe casar la política global.');
        $this->assertNull($policy->priority_id, 'La política global no tiene priority_id.');
    }

    public function test_due_date_is_calculated_from_the_priority_policy(): void
    {
        $urgente = Priority::firstOrCreate(
            ['slug' => 'urgente'],
            ['name' => 'Urgente', 'level' => 4, 'color' => '#FD7E14', 'resolution_time_hours' => 8, 'is_active' => true]
        );

        SlaPolicy::create([
            'name' => 'SLA Urgente (test)',
            'priority_id' => $urgente->id,
            'category_id' => null,
            'first_response_time_hours' => 2,
            'resolution_time_hours' => 8,
            'business_hours_only' => false,
            'is_active' => true,
        ]);

        $ticket = new Ticket(['priority' => 'urgent']);
        $ticket->category_id = null;

        $due = app(SlaService::class)->calculateDueDate($ticket);

        // Antes del fix esto era null (la prioridad no casaba ninguna política).
        $this->assertNotNull($due, "Un ticket 'urgent' debe recibir due date desde su política de prioridad.");
        $this->assertTrue($due->isFuture(), 'El due date debe estar en el futuro.');
    }
}
