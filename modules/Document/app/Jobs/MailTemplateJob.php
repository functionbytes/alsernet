<?php

namespace Modules\Document\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Document\Entities\Document;
use Modules\Document\Services\DocumentActionService;
use Modules\Document\Services\DocumentEmailTemplateService;

class MailTemplateJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    public function __construct(
        private Document $document,
        private string $emailType,
        private array $emailData = [],
        private ?int $adminId = null,
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $result = match ($this->emailType) {
            'request' => DocumentEmailTemplateService::sendInitialRequest($this->document, $this->adminId),
            'reminder' => DocumentEmailTemplateService::sendReminder($this->document, $this->adminId),
            'upload' => DocumentEmailTemplateService::sendUploadConfirmation($this->document, $this->adminId),
            'approval' => DocumentEmailTemplateService::sendApprovalEmail($this->document, $this->adminId),
            'rejection' => DocumentEmailTemplateService::sendRejectionEmail(
                $this->document,
                $this->emailData['reason'] ?? null,
                $this->emailData['rejected_docs'] ?? [],
                $this->adminId
            ),
            'missing' => DocumentEmailTemplateService::sendMissingDocuments(
                $this->document,
                $this->emailData['missing_docs'] ?? [],
                $this->emailData['notes'] ?? null,
                $this->adminId
            ),
            'custom' => DocumentEmailTemplateService::sendCustomEmail(
                $this->document,
                $this->emailData['subject'] ?? '',
                $this->emailData['message'] ?? '',
                $this->adminId
            ),
            default => throw new \InvalidArgumentException("Invalid email type: {$this->emailType}"),
        };

        if (! $result) {
            throw new \RuntimeException('Email service returned false');
        }

        $this->logSuccess();
    }

    /**
     * Handle a job failure.
     * Called automatically by Laravel after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logFailure($exception->getMessage());
    }

    private function logSuccess(): void
    {
        try {
            // Logging ahora se maneja en DocumentEmailTemplateService::logEmail()
            // que crea registros en document_mails
            // Y también llamamos a DocumentActionService para crear registros en document_actions
            DocumentActionService::logEmailSent(
                $this->document,
                $this->emailType,
                $this->emailData,
                $this->adminId
            );

            Log::info('Document email sent successfully', [
                'document_uid' => $this->document->uid,
                'email_type' => $this->emailType,
                'recipient' => $this->document->customer_email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log email action', [
                'document_uid' => $this->document->uid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function logFailure(string $errorMessage): void
    {
        try {
            // Logging de fallos en DocumentActionService
            DocumentActionService::logEmailFailed(
                $this->document,
                $this->emailType,
                $errorMessage,
                $this->emailData,
                $this->adminId
            );
        } catch (\Exception $e) {
            Log::error('Failed to log failed email action', [
                'document_uid' => $this->document->uid,
                'error' => $e->getMessage(),
            ]);
        }

        Log::error('Failed to send document email', [
            'document_uid' => $this->document->uid,
            'email_type' => $this->emailType,
            'error' => $errorMessage,
        ]);
    }
}
