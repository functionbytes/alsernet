<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Settings;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Automation;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AutomationEngine;
use Modules\HelpdeskTickets\Services\CatalogCacheService;
use Tests\TestCase;

/**
 * Los grupos de Ajustes tienen que vivir en la misma tabla que los tickets
 * referencian.
 *
 * Durante mucho tiempo la pantalla escribía en `helpdesk_ticket_groups` mientras
 * `helpdesk_tickets.group_id` tiene una FK contra `helpdesk_groups`. El síntoma
 * era que un grupo creado desde Ajustes no se podía asignar a ningún ticket, y
 * un automatismo con la acción `assign_group` moría con
 * "SQLSTATE[23000] 1452 Cannot add or update a child row".
 */
class TicketGroupTableUnificationTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }
    }

    public function test_groups_are_stored_in_the_table_tickets_reference(): void
    {
        $this->assertSame('helpdesk_groups', (new TicketGroup)->getTable());
    }

    public function test_a_group_created_from_settings_can_be_assigned_to_a_ticket(): void
    {
        $group = $this->makeGroup();
        $ticket = $this->makeTicket();

        // Esto es lo que reventaba con violación de FK.
        $ticket->update(['group_id' => $group->id]);

        $this->assertSame($group->id, $ticket->fresh()->group_id);
        $this->assertSame($group->name, $ticket->fresh()->group?->name);
    }

    public function test_the_assign_group_automation_action_works(): void
    {
        $group = $this->makeGroup();
        $ticket = $this->makeTicket();

        Automation::create([
            'name' => 'assign-group-'.uniqid(),
            'trigger_event' => 'ticket.created',
            'is_active' => true,
            'conditions' => [],
            'actions' => [['type' => 'assign_group', 'value' => $group->id]],
            'order' => 0,
        ]);

        app(AutomationEngine::class)->handle('ticket.created', $ticket);

        $this->assertSame($group->id, $ticket->fresh()->group_id);
    }

    public function test_a_group_created_from_settings_shows_up_in_the_ticket_picker(): void
    {
        $group = $this->makeGroup();
        CatalogCacheService::invalidate();

        $this->assertTrue(
            CatalogCacheService::groups()->contains('id', $group->id),
            'El grupo debe poder elegirse en el CRUD de tickets.',
        );
    }

    public function test_is_default_maps_to_the_default_column(): void
    {
        // La tabla la llama `default`; la pantalla, `is_default`.
        $group = $this->makeGroup(['is_default' => true]);

        $raw = DB::connection('helpdesk')->table('helpdesk_groups')->where('id', $group->id)->first();

        $this->assertSame(1, (int) $raw->default);
        $this->assertTrue($group->fresh()->is_default);
    }

    public function test_order_maps_to_the_position_column(): void
    {
        $group = $this->makeGroup();

        $raw = DB::connection('helpdesk')->table('helpdesk_groups')->where('id', $group->id)->first();

        $this->assertSame((int) $raw->position, $group->fresh()->order);
    }

    public function test_marking_a_group_as_default_unmarks_the_previous_one(): void
    {
        $first = $this->makeGroup(['is_default' => true]);
        $second = $this->makeGroup(['is_default' => true]);

        $defaults = DB::connection('helpdesk')->table('helpdesk_groups')
            ->where('default', true)->pluck('id')->all();

        $this->assertSame([$second->id], $defaults, 'Solo un grupo puede ser el predeterminado.');
        $this->assertFalse($first->fresh()->is_default);
        $this->assertSame($second->id, TicketGroup::findDefault()?->id);
    }

    /** @param array<string, mixed> $overrides */
    private function makeGroup(array $overrides = []): TicketGroup
    {
        return TicketGroup::create(array_merge([
            'name' => 'Grupo '.uniqid(),
            'assignment_mode' => 'round_robin',
            'is_active' => true,
            'is_default' => false,
        ], $overrides));
    }

    private function makeTicket(): Ticket
    {
        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        return Ticket::create([
            'subject' => 'Ticket de prueba',
            'description' => 'cuerpo',
            'customer_id' => DB::connection('helpdesk')->table('helpdesk_customers')->value('id'),
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'email',
        ]);
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
