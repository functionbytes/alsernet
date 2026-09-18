<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Regresión N+1 del panel lateral de un email (TicketMailDetailDataController::data()):
 * toListRow() lee ticket->customer, category y user por cada mensaje del hilo.
 * Sin with(['ticket.customer', 'user:...', 'category:...']) en el hilo (línea
 * ~33), cada mensaje disparaba 3-4 queries propias — 120-180 en un hilo largo.
 */
class TicketMailDetailQueryCountTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private TicketStatus $status;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->helpdeskConnectionAvailable()) {
            $this->markTestSkipped('Helpdesk database connection is not available.');
        }

        Permission::firstOrCreate(['name' => 'helpdesk.tickets.emails.view', 'guard_name' => 'web']);
        $this->withoutMiddleware(RoleMiddleware::class);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
        $this->customer = Customer::factory()->create();
    }

    public function test_thread_query_count_does_not_grow_with_thread_size(): void
    {
        $manager = User::factory()->create();
        $manager->givePermissionTo('helpdesk.tickets.emails.view');

        $ticket = Ticket::create([
            'subject' => 'Hilo largo de prueba',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $mails = collect(range(1, 15))->map(fn (int $i) => TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@alvarez.mx',
            'to' => 'cliente@example.com',
            'subject' => "Mensaje {$i}",
            'body_html' => '<p>Test</p>',
            'body_text' => 'Test',
            'status' => 'sent',
            'message_id' => '<'.uniqid().'@alvarez.mx>',
        ]));

        $lastMail = $mails->last();

        DB::connection('mariadb')->flushQueryLog();
        DB::connection('mariadb')->enableQueryLog();
        DB::connection('helpdesk')->flushQueryLog();
        DB::connection('helpdesk')->enableQueryLog();

        $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.data', $lastMail))
            ->assertOk();

        $queryCount = count(DB::connection('mariadb')->getQueryLog())
            + count(DB::connection('helpdesk')->getQueryLog());

        DB::connection('mariadb')->disableQueryLog();
        DB::connection('helpdesk')->disableQueryLog();

        // Presupuesto generoso: mail + hilo (1 query con eager load) + trace
        // + actividad + relacionados + auth/permisos. Un N+1 sobre el hilo
        // de 15 mensajes dispararía esto por encima de 40-50. Subido de 25 a
        // 28 (1-sep-2026): traceFor() ahora también cruza clics
        // (EmailLog::with(['opens', 'clicks']), ver EmailLogLookupService) —
        // hasManyThrough eager-load es una query propia, no gratis, pero
        // fija (no escala con el tamaño del hilo, así que sigue sin ser un
        // N+1 real). Subido de 28 a 30 (3-sep-2026): una llamada directa al
        // controlador (sin stack HTTP/middleware) reproduce 24 queries fijas;
        // las ~5 restantes son overhead de auth/permisos de la petición real,
        // no del tamaño del hilo — verificado con hilos de distinto tamaño.
        $this->assertLessThanOrEqual(
            30,
            $queryCount,
            'El panel de detalle de email no debe hacer una query por mensaje del hilo.'
        );
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
