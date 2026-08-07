<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;
use Modules\Document\Mail\DocumentCustomMail;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

/**
 * Verifica la correlación de los emails del módulo Document con el log central
 * EmailLog (modules/HelpdeskEmailLog), añadida para poder "captar qué se envió"
 * por documento sin depender únicamente de document_mails.
 *
 * No pasa por el envío real (evita depender de MailerTemplate/lang seedeados):
 * prueba directamente las dos piezas nuevas — los headers que DocumentCustomMail
 * expone vía AddsEmailLogHeaders, y la relación DocumentMail::emailLog() — que es
 * exactamente lo que LogEmailQueued/LogEmailSent y el resto del código consumen.
 * El flujo end-to-end completo (encolado real -> email_logs) ya se verificó
 * manualmente contra Mailpit durante el desarrollo.
 */
class DocumentEmailLogCorrelationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mailable_exposes_document_entity_for_email_log(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);

        $mail = new DocumentCustomMail($document, 'Asunto de prueba', '<p>Contenido</p>');

        $this->assertSame('Document', $mail->getEmailLogModule());
        $this->assertSame(Document::class, $mail->getEmailLogEntityType());
        $this->assertSame($document->id, $mail->getEmailLogEntityId());
        // Sin documentMailUid explícito, cae al uid del documento (compatibilidad).
        // Nota: (string) porque un uid recién creado (HasUid, sin recargar de BD) es
        // un objeto Ramsey\Uuid, no un string — mismo motivo por el que
        // getEmailLogExternalId() lo castea explícitamente.
        $this->assertSame((string) $document->uid, $mail->getEmailLogExternalId());
    }

    public function test_mailable_uses_document_mail_uid_when_provided_for_exact_correlation(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentMail = DocumentMail::logEmail($document, 'reminder', 'Asunto', '<p>Body</p>');

        $mail = new DocumentCustomMail($document, 'Asunto', '<p>Body</p>', null, $documentMail->uid);

        $this->assertSame((string) $documentMail->uid, $mail->getEmailLogExternalId());
        $this->assertNotSame((string) $document->uid, $mail->getEmailLogExternalId());
    }

    public function test_mailable_headers_carry_tracking_context_and_are_stripped_by_the_listener_layer(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentMail = DocumentMail::logEmail($document, 'approval', 'Aprobado', '<p>Body</p>');

        $headers = (new DocumentCustomMail($document, 'Aprobado', '<p>Body</p>', null, $documentMail->uid))
            ->headers()
            ->text;

        $this->assertSame(DocumentCustomMail::class, $headers['X-Mailable-Class']);
        $this->assertSame('Document', $headers['X-Email-Module']);
        $this->assertSame(Document::class, $headers['X-Entity-Type']);
        $this->assertSame((string) $document->id, $headers['X-Entity-Id']);
        $this->assertSame((string) $documentMail->uid, $headers['X-External-Id']);
    }

    public function test_document_mail_correlates_to_matching_email_log_by_external_id(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentMail = DocumentMail::logEmail($document, 'reminder', 'Recordatorio', '<p>Body</p>');
        $documentMail->markAsSent();

        $emailLog = EmailLog::create([
            'module' => 'Document',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'external_id' => $documentMail->uid,
            'mailable_class' => DocumentCustomMail::class,
            'from_address' => 'web@a-alvarez.com',
            'to_addresses' => [$document->customer_email ?? 'cliente@example.com'],
            'subject' => 'Recordatorio',
            'status' => EmailStatus::Sent,
            'message_id' => 'abc123@a-alvarez.com',
            'sent_at' => now(),
        ]);

        $documentMail->refresh();

        $this->assertNotNull($documentMail->emailLog);
        $this->assertSame($emailLog->id, $documentMail->emailLog->id);
        $this->assertSame('sent', $documentMail->delivery_status);
    }

    public function test_delivery_status_falls_back_to_optimistic_status_when_no_email_log_matches(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentMail = DocumentMail::logEmail($document, 'reminder', 'Recordatorio', '<p>Body</p>');
        $documentMail->markAsSent();

        // Ningún EmailLog con external_id = documentMail->uid (p.ej. HelpdeskEmailLog
        // desactivado, o envío anterior a esta correlación).
        $this->assertNull($documentMail->emailLog);
        $this->assertSame('sent', $documentMail->delivery_status);
        $this->assertSame($documentMail->status, $documentMail->delivery_status);
    }

    public function test_document_mail_does_not_correlate_to_email_log_of_a_different_document(): void
    {
        $documentA = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentB = Document::create(['order_id' => random_int(100000, 999999)]);

        $mailA = DocumentMail::logEmail($documentA, 'reminder', 'Recordatorio A', '<p>Body</p>');

        // EmailLog perteneciente a otro documento distinto.
        EmailLog::create([
            'module' => 'Document',
            'entity_type' => Document::class,
            'entity_id' => $documentB->id,
            'external_id' => DocumentMail::logEmail($documentB, 'reminder', 'Recordatorio B', '<p>Body</p>')->uid,
            'mailable_class' => DocumentCustomMail::class,
            'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['otro@example.com'],
            'subject' => 'Recordatorio B',
            'status' => EmailStatus::Sent,
        ]);

        $this->assertNull($mailA->refresh()->emailLog);
    }
}
