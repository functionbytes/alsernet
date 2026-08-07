<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;
use Modules\Document\Services\DocumentBounceProcessorService;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Tests\TestCase;

/**
 * Tests para la detección de rebotes DSN (documents:process-bounces /
 * DocumentBounceProcessorService). No hay servidor IMAP real disponible en este
 * entorno de test (Mailpit no expone IMAP), así que se prueban directamente las
 * dos piezas que sí son responsabilidad de este código: la extracción del
 * Message-ID original desde el cuerpo crudo del DSN (vía reflection, es privado
 * a propósito — no es API pública del servicio) y la transición de estado real en
 * EmailLog/DocumentMail una vez correlacionado. El polling IMAP en sí es una
 * llamada directa a webklex/php-imap sin lógica propia que valga la pena mockear.
 */
class DocumentBounceProcessingTest extends TestCase
{
    use DatabaseTransactions;

    private function findOriginalMessageId(string $rawBody, string $ownMessageId): ?string
    {
        $service = new DocumentBounceProcessorService;
        $method = new \ReflectionMethod($service, 'findOriginalMessageId');
        $method->setAccessible(true);

        return $method->invoke($service, $rawBody, $ownMessageId);
    }

    public function test_extracts_original_message_id_from_dsn_body_skipping_the_bounce_own_id(): void
    {
        $dsn = <<<'EOT'
Message-ID: <bounce-notification-12345@mailer-daemon.a-alvarez.com>
From: Mail Delivery System <MAILER-DAEMON@a-alvarez.com>
Subject: Undelivered Mail Returned to Sender

--- The header of the original message follows ---

Message-ID: <a271cc29-5d3f-4e85-9bc4-3381e5f2e9d5@webadmin.test>
To: cliente@dominio-inexistente.example
EOT;

        $result = $this->findOriginalMessageId($dsn, 'bounce-notification-12345@mailer-daemon.a-alvarez.com');

        $this->assertSame('a271cc29-5d3f-4e85-9bc4-3381e5f2e9d5@webadmin.test', $result);
    }

    public function test_returns_null_when_no_message_id_other_than_the_bounces_own_is_present(): void
    {
        $dsn = "Message-ID: <bounce-only@mailer-daemon.a-alvarez.com>\nSubject: Undelivered Mail\n\nSin cabeceras del mensaje original.";

        $this->assertNull($this->findOriginalMessageId($dsn, 'bounce-only@mailer-daemon.a-alvarez.com'));
    }

    public function test_returns_null_when_dsn_body_has_no_message_id_at_all(): void
    {
        $this->assertNull($this->findOriginalMessageId('Cuerpo sin cabeceras de correo.', ''));
    }

    public function test_email_log_mark_as_bounced_transitions_status_and_sets_timestamp(): void
    {
        $log = EmailLog::create([
            'module' => 'Document',
            'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Recordatorio',
            'status' => EmailStatus::Sent,
            'message_id' => 'msg-1@webadmin.test',
            'sent_at' => now(),
        ]);

        $log->markAsBounced('Undelivered Mail Returned to Sender');
        $log->refresh();

        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertNotNull($log->bounced_at);
        $this->assertSame('Undelivered Mail Returned to Sender', $log->error_message);
    }

    public function test_bounce_propagates_to_document_mail_delivery_status_via_the_existing_correlation(): void
    {
        $document = Document::create(['order_id' => random_int(100000, 999999)]);
        $documentMail = DocumentMail::logEmail($document, 'reminder', 'Recordatorio', '<p>Body</p>');
        $documentMail->markAsSent();

        $emailLog = EmailLog::create([
            'module' => 'Document',
            'entity_type' => Document::class,
            'entity_id' => $document->id,
            'external_id' => (string) $documentMail->uid,
            'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['cliente@example.com'],
            'subject' => 'Recordatorio',
            'status' => EmailStatus::Sent,
            'message_id' => 'msg-2@webadmin.test',
            'sent_at' => now(),
        ]);

        $this->assertSame('sent', $documentMail->refresh()->delivery_status);

        $emailLog->markAsBounced('Undelivered Mail Returned to Sender');

        $this->assertSame('bounced', $documentMail->refresh()->delivery_status);
    }

    public function test_command_is_a_no_op_when_bounce_imap_is_not_enabled(): void
    {
        Setting::set('documents.bounce_imap_enabled', 'no');

        $this->artisan('documents:process-bounces')
            ->assertExitCode(0);
    }
}
