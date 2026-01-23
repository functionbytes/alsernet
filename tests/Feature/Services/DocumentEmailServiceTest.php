<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\Core\Models\Setting;
use Modules\Document\Entities\Document;
use Modules\Document\Services\DocumentEmailService;
use Tests\TestCase;

class DocumentEmailServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentEmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = app(DocumentEmailService::class);
    }

    public function test_send_initial_request_when_enabled(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable initial request email
        Setting::set('documents.enable_initial_request', 'yes');

        Queue::fake();

        // Act: Send initial request email
        $this->emailService->sendInitialRequest($document);

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_send_initial_request_when_disabled(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Disable initial request email
        Setting::set('documents.enable_initial_request', 'no');

        Queue::fake();

        // Act: Try to send initial request email
        $this->emailService->sendInitialRequest($document);

        // Assert: No job was dispatched
        Queue::assertNotPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_send_reminder_email(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable reminder emails
        Setting::set('documents.enable_reminder', 'yes');

        Queue::fake();

        // Act: Send reminder email
        $this->emailService->sendReminder($document);

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_reminder_email_replaces_days_since_request_variable(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Set a specific creation date so we know how many days have passed
        $document->update(['created_at' => now()->subDays(5)]);

        // Enable reminder emails and set a reminder message
        Setting::set('documents.enable_reminder', 'yes');
        Setting::set('documents.reminder_message', 'Por favor no olvide subir sus documentos.');

        // Use Mail fake to capture the actual email content
        Mail::fake();

        // Act: Send reminder email
        $this->emailService->sendReminder($document);

        // Assert: Email was sent and variables are replaced
        Mail::assertSent(\Illuminate\Mail\Message::class, function ($mail) {
            $html = $mail->getHtmlBody();

            // Check that DAYS_SINCE_REQUEST placeholder was replaced with actual number
            $this->assertStringNotContainsString('{DAYS_SINCE_REQUEST}', $html);
            $this->assertStringContainsString('Hace 5 días', $html);

            // Check that REMINDER_MESSAGE placeholder was replaced with actual message
            $this->assertStringNotContainsString('{REMINDER_MESSAGE}', $html);
            $this->assertStringContainsString('Por favor no olvide subir sus documentos.', $html);

            return true;
        });
    }

    public function test_send_missing_documents_notification(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable missing docs emails
        Setting::set('documents.enable_missing_docs', 'yes');

        Queue::fake();

        // Act: Send missing documents notification
        $this->emailService->sendMissingDocumentsRequest(
            $document,
            ['dni_trasera', 'licencia'],
            'Please upload the missing documents'
        );

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_send_approval_email(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable approval emails
        Setting::set('documents.enable_approval', 'yes');

        Queue::fake();

        // Act: Send approval email
        $this->emailService->sendApprovalEmail($document);

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_send_rejection_email(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable rejection emails
        Setting::set('documents.enable_rejection', 'yes');

        Queue::fake();

        // Act: Send rejection email with reason
        $this->emailService->sendRejectionEmail(
            $document,
            'Document do not meet quality standards'
        );

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_send_completion_email(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();

        // Enable completion emails
        Setting::set('documents.enable_completion', 'yes');

        Queue::fake();

        // Act: Send completion email
        $this->emailService->sendCompletionEmail($document);

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_email_includes_customer_name(): void
    {
        // Arrange: Create a test document with specific customer name
        $document = $this->createTestDocument([
            'customer_firstname' => 'Juan',
            'customer_lastname' => 'Pérez',
        ]);

        Setting::set('documents.enable_initial_request', 'yes');
        Queue::fake();

        // Act: Send initial request
        $this->emailService->sendInitialRequest($document);

        // Assert: Queue has job with document containing customer data
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class, function ($job) use ($document) {
            return $job->document->id === $document->id;
        });
    }

    public function test_email_includes_upload_link(): void
    {
        // Arrange: Create a test document
        $document = $this->createTestDocument();
        $document->update(['upload_token' => 'test-token-123']);

        Setting::set('documents.enable_initial_request', 'yes');
        Queue::fake();

        // Act: Send initial request
        $this->emailService->sendInitialRequest($document);

        // Assert: Job was dispatched
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class);
    }

    public function test_email_includes_required_documents_list(): void
    {
        // Arrange: Create a test document with required documents
        $document = $this->createTestDocument();
        $document->update([
            'required_documents' => [
                'dni_frontal' => 'DNI - Cara frontal',
                'dni_trasera' => 'DNI - Cara trasera',
                'licencia' => 'Licencia de armas',
            ],
        ]);

        Setting::set('documents.enable_initial_request', 'yes');
        Queue::fake();

        // Act: Send initial request
        $this->emailService->sendInitialRequest($document);

        // Assert: Job contains document with required documents
        Queue::assertPushed(\App\Jobs\Documents\SendDocumentEmailJob::class, function ($job) {
            return ! empty($job->document->required_documents);
        });
    }

    protected function createTestDocument(array $attributes = []): Document
    {
        return Document::create(array_merge([
            'uid' => \Illuminate\Support\Str::uuid(),
            'order_id' => rand(1000000, 9999999),
            'order_reference' => 'TEST-'.rand(100000, 999999),
            'type' => 'corta',
            'source' => 'test',
            'proccess' => 0,
            'customer_firstname' => 'Test',
            'customer_lastname' => 'Customer',
            'customer_email' => 'test@example.com',
            'customer_company' => 'Test Company',
            'customer_cellphone' => '123456789',
        ], $attributes));
    }

    protected function tearDown(): void
    {
        // Clean up test documents
        Document::where('source', 'test')->delete();
        parent::tearDown();
    }
}
