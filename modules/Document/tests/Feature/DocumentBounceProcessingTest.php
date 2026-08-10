<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;
use Modules\Document\Notifications\BounceProcessingFailedNotification;
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

    private function findFailedRecipient(string $rawBody): ?string
    {
        $service = new DocumentBounceProcessorService;
        $method = new \ReflectionMethod($service, 'findFailedRecipient');
        $method->setAccessible(true);

        return $method->invoke($service, $rawBody);
    }

    private function processMessageByRecipient(string $rawBody, string $subject): bool
    {
        $service = new DocumentBounceProcessorService;
        $method = new \ReflectionMethod($service, 'processMessageByRecipient');
        $method->setAccessible(true);

        return $method->invoke($service, $rawBody, $subject);
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

    public function test_extracts_failed_recipient_from_final_recipient_field(): void
    {
        $dsn = "Final-Recipient: rfc822; Cliente@Dominio-Roto.example\nAction: failed";

        $this->assertSame('cliente@dominio-roto.example', $this->findFailedRecipient($dsn));
    }

    public function test_extracts_failed_recipient_from_x_failed_recipients_header(): void
    {
        $dsn = "X-Failed-Recipients: otro@ejemplo.com\n";

        $this->assertSame('otro@ejemplo.com', $this->findFailedRecipient($dsn));
    }

    public function test_returns_null_when_dsn_has_no_recognizable_recipient_field(): void
    {
        $this->assertNull($this->findFailedRecipient('Cuerpo sin campos de destinatario reconocibles.'));
    }

    public function test_recipient_fallback_marks_bounced_when_exactly_one_candidate_matches(): void
    {
        $emailLog = EmailLog::create([
            'module' => 'Document',
            'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['cliente@dominio-roto.example'],
            'subject' => 'Recordatorio',
            'status' => EmailStatus::Sent,
            'message_id' => 'sin-dsn-embebido@webadmin.test',
            'sent_at' => now(),
        ]);

        $dsn = "Final-Recipient: rfc822; cliente@dominio-roto.example\nAction: failed";

        $result = $this->processMessageByRecipient($dsn, 'Undelivered Mail Returned to Sender');

        $this->assertTrue($result);
        $this->assertSame(EmailStatus::Bounced, $emailLog->refresh()->status);
        $this->assertStringContainsString('correlación por destinatario', $emailLog->error_message);
    }

    public function test_recipient_fallback_does_nothing_when_no_candidate_matches(): void
    {
        $dsn = "Final-Recipient: rfc822; nadie-envio-a-este@ejemplo.example\nAction: failed";

        $this->assertFalse($this->processMessageByRecipient($dsn, 'Undelivered Mail Returned to Sender'));
    }

    public function test_recipient_fallback_refuses_to_guess_when_multiple_candidates_are_ambiguous(): void
    {
        $recipient = 'cliente-ambiguo@dominio-roto.example';

        $first = EmailLog::create([
            'module' => 'Document', 'from_address' => 'web@a-alvarez.com',
            'to_addresses' => [$recipient], 'subject' => 'Recordatorio 1',
            'status' => EmailStatus::Sent, 'message_id' => 'msg-a@webadmin.test', 'sent_at' => now(),
        ]);

        $second = EmailLog::create([
            'module' => 'Document', 'from_address' => 'web@a-alvarez.com',
            'to_addresses' => [$recipient], 'subject' => 'Recordatorio 2',
            'status' => EmailStatus::Sent, 'message_id' => 'msg-b@webadmin.test', 'sent_at' => now(),
        ]);

        $dsn = "Final-Recipient: rfc822; {$recipient}\nAction: failed";

        $result = $this->processMessageByRecipient($dsn, 'Undelivered Mail Returned to Sender');

        $this->assertFalse($result);
        $this->assertSame(EmailStatus::Sent, $first->refresh()->status);
        $this->assertSame(EmailStatus::Sent, $second->refresh()->status);
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

    public function test_command_fails_without_connecting_when_host_is_not_configured(): void
    {
        Setting::set('documents.bounce_imap_enabled', 'yes');
        Setting::set('documents.bounce_imap_host', '');
        Setting::set('documents.bounce_imap_consecutive_failures', '0');

        $this->artisan('documents:process-bounces')
            ->assertExitCode(1);

        $this->assertSame('1', Setting::get('documents.bounce_imap_consecutive_failures'));
    }

    public function test_notifies_admins_only_once_the_failure_threshold_is_reached(): void
    {
        Notification::fake();

        Setting::set('documents.bounce_imap_enabled', 'yes');
        Setting::set('documents.bounce_imap_host', '');
        Setting::set('documents.bounce_imap_consecutive_failures', '0');

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        // Fallos 1 y 2: por debajo del umbral (3), sin notificar todavía.
        $this->artisan('documents:process-bounces');
        $this->artisan('documents:process-bounces');

        Notification::assertNothingSent();

        // Fallo 3: cruza el umbral, notifica.
        $this->artisan('documents:process-bounces');

        Notification::assertSentTo($admin, BounceProcessingFailedNotification::class, function ($notification) {
            return $notification->consecutiveFailures === 3;
        });

        // Fallo 4: sigue roto, pero ya se avisó una vez — no se repite el spam.
        $this->artisan('documents:process-bounces');

        Notification::assertSentToTimes($admin, BounceProcessingFailedNotification::class, 1);
    }

    public function test_disabled_no_op_does_not_touch_the_failure_counter(): void
    {
        // Un no-op (bounce_imap_enabled=no) no debe tocar el contador de fallos:
        // si se reactiva más tarde con el contador aún alto de un fallo previo,
        // el próximo fallo real debe seguir contando desde ahí, no reiniciarse
        // silenciosamente a 0 solo porque estuvo desactivado un rato.
        Setting::set('documents.bounce_imap_consecutive_failures', '5');
        Setting::set('documents.bounce_imap_enabled', 'no');

        $this->artisan('documents:process-bounces')->assertExitCode(0);

        $this->assertSame('5', Setting::get('documents.bounce_imap_consecutive_failures'));
    }
}
