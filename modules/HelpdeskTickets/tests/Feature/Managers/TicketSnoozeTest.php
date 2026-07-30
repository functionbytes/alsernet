<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Snooze de tickets: posponer/reactivar y el filtrado de colas (los pospuestos
 * salen de la vista activa hasta que vencen).
 */
class TicketSnoozeTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private User $agent;

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::firstOrCreate(
            ['email' => 'snooze@example.com'],
            ['name' => 'Snooze Customer']
        );

        $this->agent = User::factory()->create();
        $this->agent->givePermissionTo('helpdesk.tickets.update');
    }

    public function test_snooze_sets_until_and_by(): void
    {
        $ticket = $this->ticket();
        $until = now()->addDay()->toDateTimeString();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.snooze', $ticket), ['snoozed_until' => $until])
            ->assertOk()
            ->assertJsonPath('success', true);

        $fresh = $ticket->fresh();
        $this->assertNotNull($fresh->snoozed_until);
        $this->assertSame($this->agent->id, $fresh->snoozed_by);
        $this->assertTrue($fresh->isSnoozed());
    }

    public function test_snooze_requires_a_future_date(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->agent)
            ->postJson(route('manager.helpdesk.tickets.snooze', $ticket), ['snoozed_until' => now()->subHour()->toDateTimeString()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['snoozed_until']);
    }

    public function test_unsnooze_reactivates_the_ticket(): void
    {
        $ticket = $this->ticket(['snoozed_until' => now()->addDay(), 'snoozed_by' => $this->agent->id]);

        $this->actingAs($this->agent)
            ->deleteJson(route('manager.helpdesk.tickets.unsnooze', $ticket))
            ->assertOk();

        $this->assertNull($ticket->fresh()->snoozed_until);
    }

    public function test_not_snoozed_scope_excludes_active_snoozes_but_keeps_expired(): void
    {
        $active = $this->ticket(['snoozed_until' => now()->addDay()]);
        $expired = $this->ticket(['snoozed_until' => now()->subDay()]);
        $plain = $this->ticket();

        $ids = Ticket::query()->notSnoozed()->pluck('id');

        $this->assertFalse($ids->contains($active->id));
        $this->assertTrue($ids->contains($expired->id));
        $this->assertTrue($ids->contains($plain->id));

        $snoozedIds = Ticket::query()->snoozed()->pluck('id');
        $this->assertTrue($snoozedIds->contains($active->id));
        $this->assertFalse($snoozedIds->contains($expired->id));
    }

    private function ticket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Snooze ticket',
            'description' => 'x',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }
}
