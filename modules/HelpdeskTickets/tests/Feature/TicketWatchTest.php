<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketWatchTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.view', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Rol para pasar el middleware role:super-admin|super-settings de las
        // rutas de manager + permiso de vista que exige la policy watch().
        $this->agent = User::factory()->create();
        $this->agent->assignRole('super-admin');
        $this->agent->givePermissionTo('helpdesk.tickets.view');

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            [
                'name' => 'Open',
                'color' => '#13C672',
                'is_open' => true,
                'is_default' => true,
                'order' => 1,
            ]
        );
    }

    public function test_agent_can_watch_ticket(): void
    {
        $ticket = $this->createTicket();

        $response = $this->actingAs($this->agent)
            ->post(route('manager.helpdesk.tickets.watch', $ticket));

        $response->assertOk();
        $response->assertJson(['watching' => true]);
    }

    public function test_agent_can_unwatch_ticket(): void
    {
        $ticket = $this->createTicket();

        // Watch first
        $this->actingAs($this->agent)
            ->post(route('manager.helpdesk.tickets.watch', $ticket));

        // Then unwatch
        $response = $this->actingAs($this->agent)
            ->delete(route('manager.helpdesk.tickets.unwatch', $ticket));

        $response->assertOk();
        $response->assertJson(['watching' => false]);
    }

    public function test_watch_returns_json_response(): void
    {
        $ticket = $this->createTicket();

        $response = $this->actingAs($this->agent)
            ->post(route('manager.helpdesk.tickets.watch', $ticket));

        $response->assertOk();
        $response->assertJsonStructure(['watching', 'message']);
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Test ticket',
            'description' => 'Test description.',
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
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
