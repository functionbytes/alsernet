<?php

namespace Modules\HelpdeskTickets\Tests\Feature\Managers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Database\Seeders\HelpdeskTicketsPermissionsSeeder;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketGroup;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Modules\HelpdeskTickets\Tests\Concerns\SharesHelpdeskPdo;
use Tests\Concerns\SeedsHelpdeskRoles;
use Tests\TestCase;

/**
 * TicketAttachmentDownloadController::download() — autorización NEGATIVA.
 *
 * El propio controlador usa authorize('view', $ticket) precisamente para que
 * cualquier endurecimiento futuro de TicketPolicy (por equipo, por
 * asignación) alcance también a la descarga de adjuntos. Este test comprueba
 * justo eso: un agente sin permiso de equipo, sin ser el asignado y sin
 * helpdesk.tickets.manage no puede descargar el adjunto de un ticket ajeno,
 * aunque teclee la URL exacta.
 */
class TicketAttachmentDownloadControllerTest extends TestCase
{
    use SeedsHelpdeskRoles;
    use SharesHelpdeskPdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedHelpdeskRoles();
        $this->seed(HelpdeskTicketsPermissionsSeeder::class);
    }

    public function test_agent_without_team_access_cannot_download_attachment_of_foreign_ticket(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('helpdesk/attachments/confidencial.pdf', '%PDF-1.4 contenido confidencial');

        $status = TicketStatus::firstOrCreate(
            ['slug' => 'open'],
            ['name' => 'Open', 'color' => '#13C672', 'is_open' => true, 'is_default' => true, 'order' => 1]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'attachment-download-customer@example.com'],
            ['name' => 'Cliente de prueba']
        );

        // Ticket con equipo asignado, sin agente asignado: el agente del test
        // no pertenece a ese equipo, así que TicketPolicy::inScope() lo
        // deniega salvo que tenga helpdesk.tickets.manage.
        $foreignGroup = TicketGroup::create([
            'name' => 'Equipo ajeno '.uniqid(),
            'assignment_mode' => 'manual',
            'is_default' => false,
            'is_active' => true,
        ]);

        $ticket = Ticket::create([
            'subject' => 'Ticket con adjunto confidencial',
            'description' => 'Descripción de prueba.',
            'customer_id' => $customer->id,
            'status_id' => $status->id,
            'priority' => 'normal',
            'source' => 'web',
            'group_id' => $foreignGroup->id,
            'assignee_id' => null,
        ]);

        $item = $ticket->items()->create([
            'author_id' => $customer->id,
            'type' => 'message',
            'body' => 'Aquí tienes el documento.',
            'is_internal' => false,
            'attachment_urls' => ['helpdesk/attachments/confidencial.pdf'],
        ]);

        $agent = User::factory()->create();
        $agent->assignRole('helpdesk-agent');
        $agent->givePermissionTo('helpdesk.tickets.view');

        $this->actingAs($agent)
            ->get(route('manager.helpdesk.tickets.attachments.download', [$ticket, $item, 0]))
            ->assertForbidden();
    }
}
