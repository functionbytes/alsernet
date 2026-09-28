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
 * Sin eager load de user/category (y el ticket compartido vía setRelation) en el hilo (línea
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

        // Se compara el mismo panel con un hilo de 1 y de 15 mensajes: un N+1
        // sobre el hilo sumaría >= 14 queries al segundo. Antes el test solo
        // tenía un techo absoluto que había que ir subiendo (25 → 28 → 30)
        // cada vez que el resumen de cliente 360 ganaba una consulta fija
        // (CustomerSummaryService / ContactAggregatorService), aunque no
        // escalara con el hilo. Una petición previa calienta las cachés de
        // permisos/settings: la primera petición en frío hace ~30 queries
        // más que las siguientes y esa diferencia tapaba el N+1.
        $this->countQueriesForThreadOf(1, $manager);

        $shortThread = $this->countQueriesForThreadOf(1, $manager);
        $longThread = $this->countQueriesForThreadOf(15, $manager);

        $this->assertLessThanOrEqual(
            $shortThread,
            $longThread,
            "El panel de detalle de email no debe hacer una query por mensaje del hilo ({$shortThread} con 1 mensaje, {$longThread} con 15)."
        );

        // Techo del coste fijo con cachés calientes (13 medidas el
        // 28-sep-2026, casi todas del resumen de cliente 360). En frío (Redis
        // vacío) el mismo panel bajó de 42 a 37 queries el mismo día al
        // deduplicar en CustomerInsightsService::aggregates() los cuatro
        // agregados de salud del cliente (avg CSAT, cerradas, última
        // conversación, sentimiento negativo) que healthScore()/
        // healthFactors()/lifetimeMetrics() recalculaban cada uno por su
        // cuenta dentro del mismo resumen — pero esa dedup solo se nota en
        // frío: ContactAggregatorService::resumen() ya envuelve todo el
        // bloque en Cache::remember(60s), así que en caliente esas 4 consultas
        // ni siquiera llegan a ejecutarse (0 en ambos casos, antes y después).
        // Margen de 2 sobre las 13 medidas, no un techo redondeado a ojo.
        $this->assertLessThanOrEqual(15, $shortThread);
    }

    private function countQueriesForThreadOf(int $size, User $manager): int
    {
        $ticket = Ticket::create([
            'subject' => 'Hilo de prueba',
            'description' => 'Test description.',
            'customer_id' => $this->customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);

        $mails = collect(range(1, $size))->map(fn (int $i) => TicketMail::create([
            'ticket_id' => $ticket->id,
            'user_id' => $manager->id,
            'direction' => 'outbound',
            'from' => 'soporte@alvarez.mx',
            'to' => 'cliente@example.com',
            'subject' => "Mensaje {$i}",
            'body_html' => '<p>Test</p>',
            'body_text' => 'Test',
            'status' => 'sent',
            'message_id' => '<'.uniqid().'@alvarez.mx>',
        ]));

        // Incluye la conexión por defecto: App\Models\User (relación user
        // del hilo, vía BelongsToHelpdeskUser) no vive en mariadb/helpdesk, y
        // sin contarla un N+1 sobre el agente de cada mensaje pasaba invisible.
        $connections = array_unique(['mariadb', 'helpdesk', config('database.default')]);

        foreach ($connections as $connection) {
            DB::connection($connection)->flushQueryLog();
            DB::connection($connection)->enableQueryLog();
        }

        $this->actingAs($manager)
            ->getJson(route('manager.helpdesk.tickets.emails.data', $mails->last()))
            ->assertOk();

        $queryCount = 0;
        foreach ($connections as $connection) {
            $queryCount += count(DB::connection($connection)->getQueryLog());
            DB::connection($connection)->disableQueryLog();
        }

        return $queryCount;
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
