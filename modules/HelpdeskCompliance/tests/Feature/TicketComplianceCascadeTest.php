<?php

namespace Modules\HelpdeskCompliance\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskCompliance\Jobs\ProcessComplianceCascadeJob;
use Modules\HelpdeskCompliance\Models\ComplianceRequest;
use Modules\HelpdeskTickets\Models\Ticket;
use Modules\HelpdeskTickets\Models\TicketAttachment;
use Modules\HelpdeskTickets\Models\TicketComment;
use Modules\HelpdeskTickets\Models\TicketItem;
use Modules\HelpdeskTickets\Models\TicketMail;
use Modules\HelpdeskTickets\Models\TicketMessage;
use Modules\HelpdeskTickets\Models\TicketNote;
use Modules\HelpdeskTickets\Models\TicketSideConversation;
use Modules\HelpdeskTickets\Models\TicketSideConversationMessage;
use Tests\TestCase;

/**
 * SEC-05 items 4/5/9/10: la cascada de tickets solo redactaba
 * Ticket.description/custom_fields en modo soft, dejando legible el resto
 * del hilo (subject, emails, mensajes, notas, comentarios, side
 * conversations) y sus adjuntos — y en modo hard, forceDelete() dejaba
 * huérfanos en disco los ficheros de esos adjuntos porque el ON DELETE
 * CASCADE de MariaDB no dispara hooks de Eloquent.
 *
 * Cada test consulta las tablas hijas directamente (fresh()/assertDatabase*)
 * para comprobar que, tras la cascada, no queda PII consultable ni en modo
 * soft ni en modo hard, y que los ficheros físicos desaparecen del disco
 * fake en modo hard.
 */
class TicketComplianceCascadeTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function pendingRequestId(int $customerId, bool $hard): int
    {
        return ComplianceRequest::create([
            'customer_id' => $customerId,
            'type' => $hard ? ComplianceRequest::TYPE_DELETE_HARD : ComplianceRequest::TYPE_DELETE_SOFT,
            'status' => 'pending',
        ])->id;
    }

    /**
     * Crea un ticket con PII en cada tabla hija cubierta por el handler y
     * devuelve los ids relevantes para las aserciones posteriores.
     *
     * @return array{ticket: Ticket, mail: TicketMail, item: TicketItem, message: TicketMessage, attachment: TicketAttachment, note: TicketNote, comment: TicketComment, side: TicketSideConversation, sideMessage: TicketSideConversationMessage, mailAttachmentPath: string, itemAttachmentPath: string, attachmentPath: string}
     */
    private function makeTicketWithPii(Customer $customer): array
    {
        $disk = config('helpdesk.attachments.disk', 'public');

        $ticket = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'subject' => 'Reclamación de Juan Pérez sobre su DNI 12345678Z',
            'description' => 'Datos personales sensibles',
            'ticket_number' => 'TCK-TEST-'.uniqid(),
        ]);

        Storage::disk($disk)->put('helpdesk/tickets/mail-attachment.pdf', 'contenido pdf mail');
        $mail = TicketMail::create([
            'ticket_id' => $ticket->id,
            'direction' => 'inbound',
            'from' => 'juan.perez@example.test',
            'to' => 'soporte@example.test',
            'subject' => 'Mi reclamación personal',
            'body_html' => '<p>Mi DNI es 12345678Z</p>',
            'body_text' => 'Mi DNI es 12345678Z',
            'attachments' => [
                ['name' => 'mail-attachment.pdf', 'path' => 'helpdesk/tickets/mail-attachment.pdf', 'disk' => $disk, 'size' => 19],
            ],
            'status' => 'received',
        ]);

        Storage::disk($disk)->put('helpdesk/tickets/item-attachment.pdf', 'contenido pdf item');
        $item = $ticket->items()->create([
            'type' => 'message',
            'body' => 'Mi teléfono es 600111222',
            'attachment_urls' => ['helpdesk/tickets/item-attachment.pdf'],
            'is_internal' => false,
        ]);

        $message = TicketMessage::factory()->create([
            'ticket_id' => $ticket->id,
            'message' => 'Adjunto mi documento personal',
        ]);
        Storage::disk($disk)->put('helpdesk/attachments/message-attachment.pdf', 'contenido pdf message');
        // TicketAttachment no usa HasFactory (solo HasUid): se crea directo.
        $attachment = TicketAttachment::create([
            'uid' => (string) Str::uuid(),
            'ticket_message_id' => $message->id,
            'filename' => 'message-attachment.pdf',
            'original_filename' => 'message-attachment.pdf',
            'mime_type' => 'application/pdf',
            'size' => 21,
            'path' => 'helpdesk/attachments/message-attachment.pdf',
        ]);

        // TicketNote y TicketComment tampoco usan HasFactory: se crean directo.
        $note = TicketNote::create([
            'ticket_id' => $ticket->id,
            'user_id' => 1,
            'title' => 'Nota sobre el cliente',
            'body' => 'Vive en Calle Falsa 123',
        ]);

        $comment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => 1,
            'body' => 'Comentario con datos personales',
            'html_body' => '<p>Comentario con datos personales</p>',
            'attachment_urls' => null,
        ]);

        $side = TicketSideConversation::create([
            'ticket_id' => $ticket->id,
            'subject' => 'Consulta interna sobre el cliente',
            'participant_type' => 'external_email',
            'participant_email' => 'proveedor-externo@example.test',
            'status' => 'open',
            'created_by' => 1,
        ]);
        $sideMessage = TicketSideConversationMessage::create([
            'side_conversation_id' => $side->id,
            'from_email' => 'proveedor-externo@example.test',
            'direction' => 'inbound',
            'body' => 'Aquí van los datos personales del cliente',
        ]);

        return compact('ticket', 'mail', 'item', 'message', 'attachment', 'note', 'comment', 'side', 'sideMessage')
            + [
                'mailAttachmentPath' => 'helpdesk/tickets/mail-attachment.pdf',
                'itemAttachmentPath' => 'helpdesk/tickets/item-attachment.pdf',
                'attachmentPath' => 'helpdesk/attachments/message-attachment.pdf',
            ];
    }

    // ─── modo soft: redacta, no queda PII consultable ─────────────────────────

    public function test_soft_mode_redacts_pii_across_every_child_table_and_deletes_attachment_files(): void
    {
        $disk = config('helpdesk.attachments.disk', 'public');
        $customer = Customer::factory()->create();
        $data = $this->makeTicketWithPii($customer);

        $this->assertTrue(Storage::disk($disk)->exists($data['mailAttachmentPath']));
        $this->assertTrue(Storage::disk($disk)->exists($data['itemAttachmentPath']));
        $this->assertTrue(Storage::disk($disk)->exists($data['attachmentPath']));

        $requestId = $this->pendingRequestId($customer->id, false);
        (new ProcessComplianceCascadeJob($requestId, $customer->id, false, [], ['deleted' => 0, 'anonymized' => 1], null))->handle();

        $ticket = $data['ticket']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $ticket->subject);
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $ticket->description);
        $this->assertNotNull($ticket->deleted_at);

        $mail = $data['mail']->fresh();
        $this->assertNull($mail->body_html);
        $this->assertNull($mail->body_text);
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $mail->subject);
        $this->assertSame([], $mail->attachments);

        $item = $data['item']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $item->body);
        $this->assertNull($item->html_body);
        $this->assertSame([], $item->attachment_urls);

        $message = $data['message']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $message->message);
        $this->assertNull($message->message_html);

        $note = $data['note']->fresh();
        $this->assertNull($note->title);
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $note->body);

        $comment = $data['comment']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $comment->body);
        $this->assertNull($comment->html_body);
        $this->assertNull($comment->attachment_urls);

        $side = $data['side']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $side->subject);
        $this->assertNull($side->participant_email);

        $sideMessage = $data['sideMessage']->fresh();
        $this->assertSame(config('helpdeskcompliance.redacted_text'), $sideMessage->body);

        // El adjunto físico es PII igual que el de texto: debe desaparecer del
        // disco aunque las filas se conserven redactadas (no borradas).
        $this->assertFalse(Storage::disk($disk)->exists($data['mailAttachmentPath']));
        $this->assertFalse(Storage::disk($disk)->exists($data['itemAttachmentPath']));
        $this->assertFalse(Storage::disk($disk)->exists($data['attachmentPath']));

        // Las filas hijas (no el ticket) NO se borran en soft: se conservan
        // redactadas para estadísticas, igual que ConversationItem en el core.
        $this->assertDatabaseHas('helpdesk_ticket_mails', ['id' => $mail->id], 'helpdesk');
        $this->assertDatabaseHas('helpdesk_ticket_notes', ['id' => $note->id], 'helpdesk');
    }

    // ─── modo hard: no quedan filas ni ficheros huérfanos ─────────────────────

    public function test_hard_mode_deletes_every_child_row_and_their_attachment_files(): void
    {
        $disk = config('helpdesk.attachments.disk', 'public');
        $customer = Customer::factory()->create();
        $data = $this->makeTicketWithPii($customer);

        $requestId = $this->pendingRequestId($customer->id, true);
        (new ProcessComplianceCascadeJob($requestId, $customer->id, true, [], ['deleted' => 1, 'anonymized' => 0], null))->handle();

        $this->assertDatabaseMissing('helpdesk_tickets', ['id' => $data['ticket']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_mails', ['id' => $data['mail']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_items', ['id' => $data['item']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_messages', ['id' => $data['message']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_attachments', ['id' => $data['attachment']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_notes', ['id' => $data['note']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_comments', ['id' => $data['comment']->id], 'helpdesk');
        // Sin FK/cascade en BD para side conversations: si el handler no las
        // borrara explícitamente, estas dos filas sobrevivirían al ticket.
        $this->assertDatabaseMissing('helpdesk_ticket_side_conversations', ['id' => $data['side']->id], 'helpdesk');
        $this->assertDatabaseMissing('helpdesk_ticket_side_conversation_messages', ['id' => $data['sideMessage']->id], 'helpdesk');

        // El fichero físico no puede sobrevivir a la fila que lo referenciaba
        // (huérfano en disco para siempre) — hay que borrarlo ANTES del
        // forceDelete(), que es justo lo que exige este test.
        $this->assertFalse(Storage::disk($disk)->exists($data['mailAttachmentPath']));
        $this->assertFalse(Storage::disk($disk)->exists($data['itemAttachmentPath']));
        $this->assertFalse(Storage::disk($disk)->exists($data['attachmentPath']));
    }
}
