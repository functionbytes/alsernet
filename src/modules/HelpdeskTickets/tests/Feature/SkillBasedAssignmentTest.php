<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Helpdesk\Models\Skill;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Services\AssignmentService;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * Skill-based routing para tickets: la estrategia `skills` detecta competencias
 * del asunto+descripción y asigna a un agente que las posee, degradando a carga
 * si no hay match. Reutiliza el SkillsRoutingService compartido del core.
 */
class SkillBasedAssignmentTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    private AssignmentService $service;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);

        $this->service = app(AssignmentService::class);
        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );
    }

    public function test_ticket_is_assigned_to_an_agent_with_the_detected_skill(): void
    {
        $billing = Skill::firstOrCreate(['slug' => 'billing'], ['name' => 'Billing']);

        $skilled = $this->agent('Facturas', 'Experta');
        $this->giveSkill($skilled, $billing, 5);

        $other = $this->agent('Sin', 'Skill');

        // Asunto con palabra clave de billing ("factura").
        $ticket = $this->ticket('Problema con mi factura', 'No me llega la factura del mes');

        $assignment = $this->service->autoAssignBySkills($ticket);

        $this->assertNotNull($assignment);
        $this->assertSame($skilled->id, $ticket->fresh()->assignee_id);
        $this->assertNotSame($other->id, $ticket->fresh()->assignee_id);
    }

    public function test_falls_back_to_workload_when_no_skill_is_detected(): void
    {
        $agent = $this->agent('Agente', 'Disponible');

        // Texto sin palabras clave de ninguna skill.
        $ticket = $this->ticket('Consulta general', 'Buenas, tengo una duda cualquiera.');

        $assignment = $this->service->autoAssignBySkills($ticket);

        $this->assertNotNull($assignment);
        $this->assertSame($agent->id, $ticket->fresh()->assignee_id);
    }

    private function agent(string $first, string $last): User
    {
        $user = User::factory()->create([
            'firstname' => $first,
            'lastname' => $last,
            'available' => true,
        ]);
        $user->assignRole('helpdesk-agent');

        return $user;
    }

    private function giveSkill(User $user, Skill $skill, int $proficiency): void
    {
        DB::connection('helpdesk')->table('helpdesk_user_skills')->insert([
            'user_id' => $user->id,
            'skill_id' => $skill->id,
            'proficiency' => $proficiency,
        ]);
    }

    private function ticket(string $subject, string $description): Ticket
    {
        return Ticket::create([
            'subject' => $subject,
            'description' => $description,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'web',
        ]);
    }
}
