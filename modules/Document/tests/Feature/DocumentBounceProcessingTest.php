<?php

namespace Modules\Document\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Entities\DocumentMail;
use Modules\HelpdeskEmailActivity\Enums\EmailStatus;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * El procesado de rebotes en sí (DsnMessageParser, EmailBounceCorrelatorService,
 * BounceProcessorService, email-logs:process-bounces) se generalizó y se
 * mudó a modules/HelpdeskEmailActivity/tests/Feature/BounceProcessorServiceTest.php
 * — aquí solo queda lo genuinamente específico de Document: que
 * DocumentMail::delivery_status sigue el estado real de EmailLog vía la
 * relación emailLog() por external_id (ver Document\Entities\DocumentMail).
 */
class DocumentBounceProcessingTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk'];

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

        $emailLog->markAsBounced('Undelivered Mail Returned to Sender', isHard: true);

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
