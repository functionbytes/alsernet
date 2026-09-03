<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Payload de /panel/helpdesk/tickets/{ticket}/data — lo que alimenta el
 * panel lateral y las pestañas del detalle.
 *
 * Cubre dos huecos que dejaban pantallas en blanco con datos reales
 * delante: el panel de Formulario exigía un origen concreto en vez de
 * mirar si hay campos, y la Traza exigía un EmailLog correlacionado aunque
 * el propio correo del ticket tuviera fecha de entrega.
 */
class TicketDetailDataPayloadTest extends TestCase
{
    use DatabaseTransactions;

    // TicketMail y EmailLog viven en conexiones distintas de Ticket; sin las
    // tres aquí, lo que cree el test no se revierte al terminar.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private User $manager;

    private TicketStatus $status;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'super-settings', 'guard_name' => 'web']);
        $this->manager = User::factory()->create();
        $this->manager->assignRole($role);

        $this->status = TicketStatus::firstOrCreate(
            ['slug' => 'payload-test-open'],
            ['name' => 'Abierto (payload test)']
        );
    }

    private function makeTicket(array $attributes = []): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente Payload',
            'email' => 'payload-'.uniqid().'@example.invalid',
        ]);

        return Ticket::create(array_merge([
            'subject' => 'Ticket de payload',
            'customer_id' => $customer->id,
            'status_id' => $this->status->id,
            'priority' => 'normal',
            'source' => 'form',
        ], $attributes));
    }

    private function payload(Ticket $ticket): array
    {
        return $this->actingAs($this->manager)
            ->getJson(route('manager.helpdesk.tickets.data', $ticket))
            ->assertOk()
            ->json();
    }

    // ─── panel Formulario ────────────────────────────────────────────────────

    public function test_publica_los_campos_del_formulario_aunque_el_origen_sea_form(): void
    {
        // sourceSlug() devuelve 'form' para estos tickets, que no estaba en
        // la lista permitida: el panel decía "no proviene de un formulario"
        // con los campos guardados delante.
        $ticket = $this->makeTicket(['custom_fields' => ['order' => '829575', 'firstname' => 'Gabriel']]);

        $form = $this->payload($ticket)['form'];

        $this->assertNotNull($form);
        $this->assertTrue($form['is_form_origin']);
        $this->assertCount(2, $form['submitted']);
    }

    public function test_publica_los_campos_aunque_el_ticket_llegara_por_correo(): void
    {
        // Un formulario que entra por el buzón queda con source 'email'.
        $ticket = $this->makeTicket(['source' => 'email', 'custom_fields' => ['order' => '4321']]);

        $form = $this->payload($ticket)['form'];

        $this->assertNotNull($form);
        $this->assertFalse($form['is_form_origin']);
    }

    public function test_sin_campos_capturados_no_hay_bloque_de_formulario(): void
    {
        $this->assertNull($this->payload($this->makeTicket())['form']);
    }

    public function test_separa_la_trazabilidad_del_envio_de_los_campos_del_cliente(): void
    {
        $ticket = $this->makeTicket(['custom_fields' => [
            'firstname' => 'Gabriel',
            'ip' => '187.190.22.4',
            'utm_source' => 'email',
        ]]);

        $form = $this->payload($ticket)['form'];

        $this->assertSame(['Firstname'], array_column($form['submitted'], 'label'));
        $this->assertEqualsCanonicalizing(['IP', 'campaña'], array_column($form['trace'], 'label'));
    }

    public function test_el_mensaje_libre_sale_como_cita_y_no_se_repite_entre_los_campos(): void
    {
        $mensaje = 'Mi pedido sigue detenido y necesito saber qué documentos faltan.';
        $ticket = $this->makeTicket(['custom_fields' => ['firstname' => 'Gabriel', 'message' => $mensaje]]);

        $form = $this->payload($ticket)['form'];

        $this->assertSame($mensaje, $form['message']);
        $this->assertNotContains($mensaje, array_column($form['submitted'], 'value'));
    }

    // ─── pestaña Traza ───────────────────────────────────────────────────────

    public function test_hay_traza_aunque_el_correo_no_este_en_el_log_de_emails(): void
    {
        // Solo los mailables con TracksEmailLog dejan EmailLog; el resto
        // dejaba la pestaña Traza vacía teniendo delivered_at guardado.
        $ticket = $this->makeTicket();
        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@example.invalid',
            'to' => 'cliente@example.invalid',
            'subject' => 'Respuesta',
            'status' => 'delivered',
            'message_id' => 'sin-log-'.uniqid().'@example.invalid',
            'sent_at' => now()->subMinutes(5),
            'delivered_at' => now()->subMinutes(4),
        ]);

        $payload = $this->payload($ticket);

        $this->assertNotEmpty($payload['trace']);
        $this->assertContains('delivered', array_column($payload['trace'], 'type'));
    }

    public function test_la_traza_publica_identificadores_del_correo(): void
    {
        $ticket = $this->makeTicket();
        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@example.invalid',
            'to' => 'cliente@example.invalid',
            'subject' => 'Respuesta',
            'status' => 'sent',
            'sent_at' => now()->subMinute(),
        ]);

        $ids = $this->payload($ticket)['trace_meta']['ids'];
        $byKey = array_column($ids, 'v', 'k');

        $this->assertSame((string) $mail->id, $byKey['mail_id']);
        $this->assertSame((string) $ticket->id, $byKey['ticket_id']);
        $this->assertSame('saliente', $byKey['dirección']);
    }

    public function test_sin_ningun_correo_no_hay_traza_ni_metadatos(): void
    {
        $payload = $this->payload($this->makeTicket());

        $this->assertSame([], $payload['trace']);
        $this->assertNull($payload['trace_meta']);
    }
}
