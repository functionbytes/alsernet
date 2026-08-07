<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Bandeja global "Emails enviados" (todos los tickets), sobre TicketMail.
 *
 * Las rutas manager exigen role:super-admin|super-settings, pero ambos roles
 * pasan por alto TODAS las policies vía Gate::before (ver AuthServiceProvider/
 * DocumentsServiceProvider), lo que haría intestable la autorización por
 * email. Mismo patrón que BulkReplyTest/BulkTicketOperationsTest: se
 * desactiva el middleware de rol y se prueba con usuarios que solo tienen
 * los permisos explícitamente concedidos.
 */
class TicketMailsControllerTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        foreach ([
            'helpdesk.tickets.view',
            'helpdesk.tickets.update',
            'helpdesk.tickets.emails.view',
            'helpdesk.tickets.emails.send',
            'helpdesk.tickets.emails.resend',
            'helpdesk.tickets.emails.delete',
        ] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertUnauthorized();
    }

    public function test_index_renders_the_html_page(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $this->createMail($ticket, ['subject' => 'Ticket de prueba renderizado']);

        $this->actingAs($manager)
            ->get(route('manager.helpdesk.tickets.emails.index'))
            ->assertOk()
            ->assertSee('Emails enviados')
            ->assertSee('Ticket de prueba renderizado');
    }

    public function test_index_lists_only_outbound_mails_by_default(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $outbound = $this->createMail($ticket, ['direction' => 'outbound', 'status' => 'sent', 'subject' => 'Outbound one']);
        $this->createMail($ticket, ['direction' => 'inbound', 'status' => 'received', 'subject' => 'Inbound one']);
        $this->createMail($ticket, ['direction' => 'outbound', 'status' => 'scheduled', 'scheduled_at' => now()->addHour(), 'subject' => 'Scheduled one']);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index'))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($outbound->id));
        $this->assertSame(1, $ids->count());
    }

    public function test_index_view_scheduled_filters_by_status(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();

        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);
        $this->createMail($ticket, ['status' => 'sent']);

        $response = $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.index', ['view' => 'scheduled']))
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertSame([$scheduled->id], $ids->all());
    }

    public function test_store_creates_mail_and_queues_it_immediately(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'cliente@example.com',
                'subject' => 'Nuevo email de prueba',
                'body' => '<p>Hola, este es un email de prueba.</p>',
            ])
            ->assertCreated();

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'to' => 'cliente@example.com',
            'subject' => 'Nuevo email de prueba',
            'status' => 'sent',
        ], 'helpdesk');

        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_store_schedules_instead_of_sending_when_scheduled_at_given(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.send', 'helpdesk.tickets.view']);
        $ticket = $this->createTicket();

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'cliente@example.com',
                'subject' => 'Seguimiento programado',
                'body' => '<p>Este correo se enviará más tarde.</p>',
                'scheduled_at' => now()->addDay()->toDateTimeString(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Seguimiento programado',
            'status' => 'scheduled',
        ], 'helpdesk');

        Queue::assertNothingPushed();
    }

    public function test_store_requires_view_permission_on_the_ticket(): void
    {
        // Sin helpdesk.tickets.view ni ser el assignee del ticket: TicketPolicy::view() deniega.
        $limitedAgent = $this->makeUser(['helpdesk.tickets.emails.send']);
        $ticket = $this->createTicket();

        $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.emails.store'), [
                'ticket_id' => $ticket->id,
                'to' => 'cliente@example.com',
                'subject' => 'No debería enviarse',
                'body' => '<p>...</p>',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'No debería enviarse',
        ], 'helpdesk');
    }

    public function test_resend_creates_a_new_mail_record_and_queues_it(): void
    {
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.resend']);
        $ticket = $this->createTicket();
        $original = $this->createMail($ticket, [
            'subject' => 'Original',
            'body_html' => '<p>Cuerpo original</p>',
            'to' => 'cliente@example.com',
            'status' => 'sent',
        ]);

        $response = $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.resend', $original))
            ->assertOk();

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Original',
            'in_reply_to' => $original->message_id,
            'to' => 'cliente@example.com',
        ], 'helpdesk');

        // El original sigue existiendo tal cual — reenviar crea una fila nueva.
        $this->assertSame(2, TicketMail::where('ticket_id', $ticket->id)->count());
        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_bulk_resend_creates_new_mail_records_and_preserves_the_originals(): void
    {
        // Regresión: bulkResend() pasaba el registro original tal cual a
        // TicketMailDispatcher::send(), que llama markAsSent() sobre lo que
        // recibe — un reenvío masivo de un email "rebotado" lo mutaba en el
        // sitio a "enviado" y borraba el rastro del rebote original. Debe
        // comportarse igual que el reenvío individual: crear una fila nueva.
        Queue::fake();

        $manager = $this->makeUser(['helpdesk.tickets.emails.resend']);
        $ticket = $this->createTicket();
        $bounced = $this->createMail($ticket, [
            'subject' => 'Aviso rebotado',
            'to' => 'cliente@example.com',
            'status' => 'bounced',
        ]);

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$bounced->id],
                'action' => 'resend',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // El original conserva su estado "bounced" intacto...
        $this->assertSame('bounced', $bounced->fresh()->status);

        // ...y hay una fila nueva en estado "sent" para el reenvío.
        $this->assertSame(2, TicketMail::where('ticket_id', $ticket->id)->count());
        $this->assertDatabaseHas('helpdesk_ticket_mails', [
            'ticket_id' => $ticket->id,
            'subject' => 'Aviso rebotado',
            'in_reply_to' => $bounced->message_id,
            'status' => 'sent',
        ], 'helpdesk');

        Queue::assertPushed(SendQueuedMailable::class);
    }

    public function test_bulk_cancel_scheduled_deletes_scheduled_mails(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.delete']);
        $ticket = $this->createTicket();
        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

        $this->actingAs($manager)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$scheduled->id],
                'action' => 'cancel_scheduled',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('helpdesk_ticket_mails', ['id' => $scheduled->id], 'helpdesk');
    }

    public function test_bulk_skips_mails_the_user_cannot_act_on(): void
    {
        // Solo emails.view: TicketMailPolicy::delete() exige emails.delete o
        // .manage — sin ninguno, el cancelado se omite.
        $limitedAgent = $this->makeUser(['helpdesk.tickets.emails.view']);
        $ticket = $this->createTicket();
        $scheduled = $this->createMail($ticket, ['status' => 'scheduled', 'scheduled_at' => now()->addHour()]);

        $response = $this->actingAs($limitedAgent)
            ->postJson(route('manager.helpdesk.tickets.emails.bulk'), [
                'mail_ids' => [$scheduled->id],
                'action' => 'cancel_scheduled',
            ])
            ->assertStatus(403);

        $response->assertJsonPath('skipped_ids.0', $scheduled->id);
        $this->assertDatabaseHas('helpdesk_ticket_mails', ['id' => $scheduled->id, 'deleted_at' => null], 'helpdesk');
    }

    public function test_destroy_requires_delete_permission(): void
    {
        $manager = $this->makeUser(['helpdesk.tickets.emails.delete']);
        $ticket = $this->createTicket();
        $mail = $this->createMail($ticket);

        $this->actingAs($manager)
            ->deleteJson(route('manager.helpdesk.tickets.emails.destroy', $mail))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSoftDeleted('helpdesk_ticket_mails', ['id' => $mail->id], 'helpdesk');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, string>  $permissions
     */
    private function makeUser(array $permissions): User
    {
        $user = User::factory()->create();

        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'subject' => 'Email test ticket',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
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
