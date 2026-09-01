<?php

namespace Modules\HelpdeskEmailLog\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Notifications\BounceProcessingFailedNotification;
use Modules\HelpdeskEmailLog\Services\BounceMailboxesRepository;
use Modules\HelpdeskEmailLog\Services\EmailBounceCorrelatorService;
use Modules\HelpdeskEmailLog\Support\DsnMessageParser;
use Tests\TestCase;

/**
 * Tests para la detección/correlación de rebotes DSN
 * (email-logs:process-bounces / BounceProcessorService / DsnMessageParser /
 * EmailBounceCorrelatorService) — puerto generalizado de lo que antes vivía
 * en Modules\Document\Tests\Feature\DocumentBounceProcessingTest, ahora
 * contra las clases que viven aquí (todo público, sin reflection: a
 * diferencia del DocumentBounceProcessorService original, DsnMessageParser y
 * EmailBounceCorrelatorService están pensados para probarse directamente).
 *
 * No hay servidor IMAP real disponible en este entorno de test (Mailpit no
 * expone IMAP), así que el polling en sí (BounceProcessorService::process())
 * no se prueba aquí — es una llamada directa a webklex/php-imap sin lógica
 * propia que valga la pena mockear. Se prueban las dos piezas con lógica
 * real: el parseo del DSN y la correlación/transición de estado.
 */
class BounceProcessorServiceTest extends TestCase
{
    use DatabaseTransactions;

    // 'mysql' es imprescindible aquí: BounceMailboxesRepository escribe vía
    // Modules\Core\Models\Setting, que usa la conexión default de la app
    // (mysql en este entorno, no mariadb/helpdesk) — sin declararla, cada
    // Setting::set()/setEncrypted() de estos tests escribe una fila REAL sin
    // rollback (gotcha ya documentado: ver
    // TicketEmailChannelsRepositoryTest::$connectionsToTransact).
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    public function test_extracts_original_message_id_from_dsn_body_skipping_the_bounces_own_id(): void
    {
        $dsn = <<<'EOT'
Message-ID: <bounce-notification-12345@mailer-daemon.a-alvarez.com>
From: Mail Delivery System <MAILER-DAEMON@a-alvarez.com>
Subject: Undelivered Mail Returned to Sender

--- The header of the original message follows ---

Message-ID: <a271cc29-5d3f-4e85-9bc4-3381e5f2e9d5@webadmin.test>
To: cliente@dominio-inexistente.example
EOT;

        $result = DsnMessageParser::findOriginalMessageId($dsn, 'bounce-notification-12345@mailer-daemon.a-alvarez.com');

        $this->assertSame('a271cc29-5d3f-4e85-9bc4-3381e5f2e9d5@webadmin.test', $result);
    }

    public function test_returns_null_when_no_message_id_other_than_the_bounces_own_is_present(): void
    {
        $dsn = "Message-ID: <bounce-only@mailer-daemon.a-alvarez.com>\nSubject: Undelivered Mail\n\nSin cabeceras del mensaje original.";

        $this->assertNull(DsnMessageParser::findOriginalMessageId($dsn, 'bounce-only@mailer-daemon.a-alvarez.com'));
    }

    public function test_returns_null_when_dsn_body_has_no_message_id_at_all(): void
    {
        $this->assertNull(DsnMessageParser::findOriginalMessageId('Cuerpo sin cabeceras de correo.', ''));
    }

    public function test_extracts_failed_recipient_from_final_recipient_field(): void
    {
        $dsn = "Final-Recipient: rfc822; Cliente@Dominio-Roto.example\nAction: failed";

        $this->assertSame('cliente@dominio-roto.example', DsnMessageParser::findFailedRecipient($dsn));
    }

    public function test_extracts_failed_recipient_from_x_failed_recipients_header(): void
    {
        $dsn = "X-Failed-Recipients: otro@ejemplo.com\n";

        $this->assertSame('otro@ejemplo.com', DsnMessageParser::findFailedRecipient($dsn));
    }

    public function test_returns_null_when_dsn_has_no_recognizable_recipient_field(): void
    {
        $this->assertNull(DsnMessageParser::findFailedRecipient('Cuerpo sin campos de destinatario reconocibles.'));
    }

    public function test_status_5xx_is_classified_as_hard_bounce_and_4xx_as_soft(): void
    {
        $this->assertTrue(DsnMessageParser::isHardBounce('Diagnostic-Code: smtp; 550 5.1.1\nStatus: 5.1.1'));
        $this->assertFalse(DsnMessageParser::isHardBounce('Diagnostic-Code: smtp; 452\nStatus: 4.2.2'));
        // Sin campo Status reconocible ni frase típica: no se asume permanente.
        $this->assertFalse(DsnMessageParser::isHardBounce('Cuerpo de rebote sin campos DSN estándar.'));
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

        $correlator = app(EmailBounceCorrelatorService::class);
        $recipient = DsnMessageParser::findFailedRecipient("Final-Recipient: rfc822; cliente@dominio-roto.example\nAction: failed");

        $result = $correlator->correlateByRecipient($recipient, 'Undelivered Mail Returned to Sender', ['Document'], isHard: true);

        $this->assertTrue($result);
        $this->assertSame(EmailStatus::Bounced, $emailLog->refresh()->status);
        $this->assertStringContainsString('correlación por destinatario', $emailLog->error_message);
        $this->assertSame('hard', $emailLog->bounceType());
    }

    public function test_recipient_fallback_does_nothing_when_no_candidate_matches(): void
    {
        $correlator = app(EmailBounceCorrelatorService::class);

        $result = $correlator->correlateByRecipient('nadie-envio-a-este@ejemplo.example', 'Undelivered Mail Returned to Sender', null, isHard: true);

        $this->assertFalse($result);
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

        $correlator = app(EmailBounceCorrelatorService::class);
        $result = $correlator->correlateByRecipient($recipient, 'Undelivered Mail Returned to Sender', null, isHard: true);

        $this->assertFalse($result);
        $this->assertSame(EmailStatus::Sent, $first->refresh()->status);
        $this->assertSame(EmailStatus::Sent, $second->refresh()->status);
    }

    public function test_module_scope_restricts_the_recipient_fallback(): void
    {
        $recipient = 'cliente-otro-modulo@example.test';

        $ticketsLog = EmailLog::create([
            'module' => 'HelpdeskTickets', 'from_address' => 'soporte@example.test',
            'to_addresses' => [$recipient], 'subject' => 'Ticket',
            'status' => EmailStatus::Sent, 'message_id' => 'ticket-msg@webadmin.test', 'sent_at' => now(),
        ]);

        $correlator = app(EmailBounceCorrelatorService::class);

        // Acotado a 'Document': no debe encontrar el candidato de HelpdeskTickets.
        $this->assertFalse($correlator->correlateByRecipient($recipient, 'Bounce', ['Document'], isHard: true));
        $this->assertSame(EmailStatus::Sent, $ticketsLog->refresh()->status);

        // Sin acotar (module_scope null): sí lo encuentra.
        $this->assertTrue($correlator->correlateByRecipient($recipient, 'Bounce', null, isHard: true));
        $this->assertSame(EmailStatus::Bounced, $ticketsLog->refresh()->status);
    }

    public function test_complaint_marks_as_complained_instead_of_bounced(): void
    {
        $log = EmailLog::create([
            'module' => 'Document', 'from_address' => 'web@a-alvarez.com',
            'to_addresses' => ['cliente@example.com'], 'subject' => 'Recordatorio',
            'status' => EmailStatus::Sent, 'message_id' => 'msg-complaint@webadmin.test', 'sent_at' => now(),
        ]);

        $correlator = app(EmailBounceCorrelatorService::class);
        $result = $correlator->correlateByMessageId('msg-complaint@webadmin.test', 'Feedback loop report', isHard: false, isComplaint: true);

        $this->assertTrue($result);
        $this->assertSame(EmailStatus::Complained, $log->refresh()->status);
        $this->assertNotNull($log->complained_at);
    }

    public function test_email_log_mark_as_bounced_transitions_status_and_sets_timestamp_and_type(): void
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

        $log->markAsBounced('Undelivered Mail Returned to Sender', isHard: true);
        $log->refresh();

        $this->assertSame(EmailStatus::Bounced, $log->status);
        $this->assertNotNull($log->bounced_at);
        $this->assertSame('Undelivered Mail Returned to Sender', $log->error_message);
        $this->assertSame('hard', $log->bounceType());
    }

    public function test_command_is_a_no_op_without_any_enabled_mailbox(): void
    {
        $this->artisan('email-logs:process-bounces')->assertExitCode(0);
    }

    public function test_notifies_admins_only_once_the_failure_threshold_is_reached_for_a_mailbox(): void
    {
        Notification::fake();

        $mailboxes = app(BounceMailboxesRepository::class);
        $mailbox = $mailboxes->create([
            'label' => 'Buzón de prueba',
            'host' => '', // sin host: BounceProcessorService falla la conexión sin llegar a IMAP real.
            'username' => 'x', 'password' => 'x', 'folder' => 'INBOX',
            'module_scope' => [], 'enabled' => true,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        // Fallos 1 y 2: por debajo del umbral (3), sin notificar todavía.
        $this->artisan('email-logs:process-bounces');
        $this->artisan('email-logs:process-bounces');

        Notification::assertNothingSent();

        // Fallo 3: cruza el umbral, notifica.
        $this->artisan('email-logs:process-bounces');

        Notification::assertSentTo($admin, BounceProcessingFailedNotification::class, function ($notification) {
            return $notification->consecutiveFailures === 3;
        });

        // Fallo 4: sigue roto, pero ya se avisó una vez — no se repite el spam.
        $this->artisan('email-logs:process-bounces');

        Notification::assertSentToTimes($admin, BounceProcessingFailedNotification::class, 1);

        $refreshed = $mailboxes->find($mailbox['id']);
        $this->assertSame(4, $refreshed['consecutive_failures']);
    }

    public function test_a_successful_mailbox_resets_its_failure_counter(): void
    {
        $mailboxes = app(BounceMailboxesRepository::class);
        $mailbox = $mailboxes->create(['label' => 'x', 'host' => 'x', 'username' => 'x', 'password' => 'x', 'enabled' => true]);

        $mailboxes->recordHealth($mailbox['id'], success: false, error: 'boom');
        $mailboxes->recordHealth($mailbox['id'], success: false, error: 'boom');
        $this->assertSame(2, $mailboxes->find($mailbox['id'])['consecutive_failures']);

        $mailboxes->recordHealth($mailbox['id'], success: true);
        $this->assertSame(0, $mailboxes->find($mailbox['id'])['consecutive_failures']);
    }
}
