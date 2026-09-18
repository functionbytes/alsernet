<?php

namespace Modules\Document\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
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
}
