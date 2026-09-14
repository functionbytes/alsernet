<?php

namespace Modules\Helpdesk\Tests\Feature\Reports;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Database\Seeders\PermissionsSeeder;
use Modules\Helpdesk\Http\Controllers\Managers\SlaBreachesReportController;
use Modules\Helpdesk\Http\Requests\Managers\SlaBreachesReportDataRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Contrato del endpoint que alimenta el panel de incumplimientos SLA.
 *
 * La pantalla dibuja UNA tabla plana con el agente por fila y unos KPIs con
 * denominador, cosas que la respuesta anterior no daba: venía agrupada por
 * agente, el nombre solo estaba en la cabecera del grupo y no había total de
 * abiertos. Estos tests fijan las tres piezas para que un refactor no las
 * vuelva a dejar fuera.
 */
class SlaBreachesReportDataTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mysql', 'mariadb', 'helpdesk'];

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsSeeder::class);

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);

        // super-settings pasa el middleware 'can:helpdesk.reports.view' del
        // controlador vía Gate::before (Modules\Auth\Providers\AuthServiceProvider),
        // sin depender de qué usuario exista primero en la BD de test.
        $this->user = User::factory()->create();
        $this->user->assignRole($role);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $request = SlaBreachesReportDataRequest::create('/panel/helpdesk/reports/sla-breaches/data', 'GET');
        $request->setContainer(app())->setRedirector(app('redirect'));

        return app(SlaBreachesReportController::class)->data($request)->getData(true);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function flatten(array $groups): array
    {
        $rows = [];

        foreach ($groups as $group) {
            foreach ($group['tickets'] as $ticket) {
                $rows[] = $ticket;
            }
        }

        return $rows;
    }

    public function test_payload_carries_the_open_ticket_total(): void
    {
        $this->actingAs($this->user);

        $payload = $this->payload();

        $this->assertTrue($payload['available']);
        $this->assertArrayHasKey('openTotal', $payload);
        $this->assertSame(
            Ticket::whereNull('closed_at')->count(),
            $payload['openTotal'],
            'El KPI "N de M abiertos" se queda sin denominador.'
        );
    }

    public function test_every_breached_row_carries_its_own_agent(): void
    {
        $this->actingAs($this->user);

        $rows = $this->flatten($this->payload()['breachedByAgent']);

        if ($rows === []) {
            $this->markTestSkipped('No hay tickets con SLA incumplido en este entorno.');
        }

        foreach ($rows as $row) {
            $this->assertArrayHasKey('agentId', $row);
            $this->assertArrayHasKey('agentName', $row);
            $this->assertNotSame('', $row['agentName']);

            // Sin agente el nombre tiene que ser la etiqueta, no una cadena
            // vacía: la tabla la pinta tal cual.
            if ($row['agentId'] === null) {
                $this->assertSame('Sin asignar', $row['agentName']);
            }
        }
    }

    public function test_upcoming_rows_also_carry_their_agent(): void
    {
        $this->actingAs($this->user);

        $upcoming = $this->payload()['upcoming'];

        if ($upcoming === []) {
            $this->markTestSkipped('No hay vencimientos en las próximas 24 horas en este entorno.');
        }

        foreach ($upcoming as $row) {
            $this->assertArrayHasKey('agentName', $row);
            $this->assertNotSame('', $row['agentName']);
        }
    }

    public function test_agent_name_is_not_duplicated_when_first_and_last_match(): void
    {
        $this->actingAs($this->user);

        $agent = User::create([
            'firstname' => 'Casimira',
            'lastname' => 'Casimira',
            'email' => 'casimira.sla.'.uniqid().'@ejemplo.test',
            'password' => bcrypt(uniqid()),
        ]);

        $ticket = Ticket::create([
            'subject' => 'Ticket de prueba del informe SLA',
            'assignee_id' => $agent->id,
            'priority' => 'high',
            'source' => 'email',
        ]);

        $ticket->forceFill([
            'sla_resolution_due_at' => now()->subHours(3),
            'sla_paused_at' => null,
            'closed_at' => null,
        ])->saveQuietly();

        $names = collect($this->flatten($this->payload()['breachedByAgent']))
            ->pluck('agentName')
            ->all();

        $this->assertContains('Casimira', $names);
        $this->assertNotContains('Casimira Casimira', $names);
    }
}
