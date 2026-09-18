<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Mail\PortalMagicLinkMail;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Modal 32 "Portal del cliente": "Enviar acceso al cliente" reusa el mismo
 * enlace mágico de un solo uso que ya manda CustomerPortalController::login()
 * (Customer::generatePortalToken() + plantilla helpdesk_tickets.portal_magic_link).
 */
class TicketPortalAccessTest extends TestCase
{
    use SharesHelpdeskPdo;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.update', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_envia_el_enlace_magico_y_genera_un_token_real(): void
    {
        Mail::fake();

        $customer = Customer::factory()->create(['email' => 'cliente-portal@example.invalid', 'portal_token' => null]);
        $ticket = $this->makeTicket($customer);
        $manager = $this->makeManager();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.portal.send-access', $ticket))
            ->assertOk()
            ->assertJson(['success' => true]);

        $customer->refresh();
        $this->assertNotNull($customer->portal_token);
        $this->assertNotNull($customer->portal_token_expires_at);

        Mail::assertQueued(PortalMagicLinkMail::class, fn (PortalMagicLinkMail $mail) => $mail->hasTo($customer->email) && $mail->customer->is($customer));
    }

    public function test_falla_con_claridad_si_el_cliente_no_tiene_email(): void
    {
        $customer = Customer::factory()->create(['email' => null]);
        $ticket = $this->makeTicket($customer);
        $manager = $this->makeManager();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.portal.send-access', $ticket))
            ->assertStatus(422);
    }

    public function test_exige_permiso_de_gestion(): void
    {
        $customer = Customer::factory()->create(['email' => 'cliente-portal2@example.invalid']);
        $ticket = $this->makeTicket($customer);
        $agent = User::factory()->create(); // sin helpdesk.tickets.update ni ser el asignado

        $this->actingAs($agent)
            ->postJson(route('manager.helpdesk.tickets.portal.send-access', $ticket))
            ->assertForbidden();
    }

    private function makeManager(): User
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.update');

        return $manager;
    }

    private function makeTicket(Customer $customer): Ticket
    {
        return Ticket::create([
            'subject' => 'Ticket portal',
            'description' => 'x',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
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
