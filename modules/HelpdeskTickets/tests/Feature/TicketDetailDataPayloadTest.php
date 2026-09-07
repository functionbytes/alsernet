<?php

namespace Modules\HelpdeskTickets\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
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

    /**
     * Bug real (3-sep-2026, TCK-2026-00093): en cuanto el cliente respondía,
     * la pestaña Traza se quedaba vacía. traceFor() se llamaba con $lastMail
     * (el correo más reciente del ticket SEA CUAL SEA su dirección), y un
     * correo ENTRANTE nunca tiene sent_at/delivered_at/EmailLog propios --
     * ese guard de traceFor() devolvía [] aunque el correo saliente anterior
     * sí tuviera toda su traza real.
     */
    public function test_la_traza_sigue_mostrandose_aunque_el_cliente_responda_despues(): void
    {
        $ticket = $this->makeTicket();
        $outbound = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'outbound',
            'from' => 'soporte@example.invalid',
            'to' => 'cliente@example.invalid',
            'subject' => 'Respuesta',
            'status' => 'delivered',
            'message_id' => 'saliente-'.uniqid().'@example.invalid',
            'sent_at' => now()->subMinutes(10),
            'delivered_at' => now()->subMinutes(9),
        ]);
        // created_at forzado (no basta con crearlo primero): sin esto los dos
        // registros pueden quedar con el mismo segundo y el orden de
        // reorder()->latest() en el empate no está garantizado -- el test
        // pasaría "de casualidad" sin probar de verdad el fix.
        $outbound->forceFill(['created_at' => now()->subMinutes(10)])->save();

        // Más reciente que el saliente de arriba -- sin el fix, este pasa a
        // ser $lastMail y la traza se vacía.
        TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'from' => 'cliente@example.invalid',
            'to' => 'soporte@example.invalid',
            'subject' => 'Re: Respuesta',
            'status' => 'received',
            'message_id' => 'entrante-'.uniqid().'@example.invalid',
        ]);

        $payload = $this->payload($ticket);

        $this->assertNotEmpty($payload['trace']);
        $this->assertContains('delivered', array_column($payload['trace'], 'type'));
        $this->assertSame('saliente', array_column($payload['trace_meta']['ids'], 'v', 'k')['dirección']);
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

    // ─── adjuntos del hilo (modal "ve-file-preview") ───────────────────────

    /**
     * threadAttachments() manda ahora también el mime, que el modal de
     * previsualización usa para decidir si mostrar el PDF/imagen de verdad o
     * el aviso de "sin previsualización disponible".
     */
    public function test_los_adjuntos_del_hilo_incluyen_el_mime(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('helpdesk/attachments/checklist.pdf', '%PDF-1.4 contenido de prueba');

        $ticket = $this->makeTicket();
        $item = $ticket->items()->create([
            'type' => 'message',
            'author_id' => $ticket->customer_id,
            'body' => 'Aquí tienes el checklist.',
            'is_internal' => false,
            'attachment_urls' => ['helpdesk/attachments/checklist.pdf'],
        ]);

        $thread = $this->payload($ticket)['thread'];
        $found = collect($thread)->firstWhere('id', $item->id);

        $this->assertNotNull($found);
        $this->assertCount(1, $found['attachments']);
        $this->assertSame('checklist.pdf', $found['attachments'][0]['name']);
        $this->assertSame('application/pdf', $found['attachments'][0]['mime']);
        // 'bytes' (crudo) además de 'size' (ya formateado, "29 B") -- lo usa
        // openFilePreviewModal() en el JS para formatearlo con su propio
        // formatFileSize(), el mismo que ya usa la pestaña Adjuntos.
        $this->assertSame(28, $found['attachments'][0]['bytes']);
    }
}
