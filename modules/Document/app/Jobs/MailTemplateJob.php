<?php

namespace Modules\Document\Jobs;

use Illuminate\Broadcasting\BroadcastException;
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

    private float $startTime = 0.0;

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
        $this->startTime = microtime(true);
        $this->logJobStarted();

        try {
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
                'fusil_license' => DocumentEmailTemplateService::sendFusilLicenseRequest($this->document, $this->adminId),
                'fusil_license_confirmation' => DocumentEmailTemplateService::sendFusilLicenseConfirmation($this->document, $this->adminId),
                default => throw new \InvalidArgumentException("Invalid email type: {$this->emailType}"),
            };

            if (! $result) {
                throw new \RuntimeException('Email service returned false');
            }

            $this->logSuccess();
        } catch (BroadcastException $e) {
            // Handle broadcasting timeouts gracefully - email was sent successfully
            if ($this->isBroadcastTimeout($e)) {
              
                $this->logSuccess();
                return; // Don't throw - job is successful
            }

            // Other broadcasting errors should fail the job
            $this->logJobError($e);
            throw $e;
        } catch (\Throwable $e) {
            $this->logJobError($e);
            throw $e;
        }
    }

    /**
     * Check if a BroadcastException is a timeout error
     */
    private function isBroadcastTimeout(BroadcastException $e): bool
    {
        $message = $e->getMessage();
        return strpos($message, 'cURL error 28') !== false ||
               strpos($message, 'Connection timed out') !== false ||
               strpos($message, 'timeout') !== false;
    }

    /**
     * Handle a job failure.
     * Called automatically by Laravel after all retry attempts are exhausted.
     */
    public function failed(\Throwable $exception): void
    {
        $this->logFailure($exception->getMessage());
    }

    private function logJobStarted(): void
    {
        Log::channel('email-jobs')->info('Email job started', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'document_uid' => $this->document->uid,
            'document_id' => $this->document->id,
            'email_type' => $this->emailType,
            'recipient' => $this->document->customer_email,
            'timestamp' => now()->toIso8601String(),
            'attempt' => $this->attempts(),
        ]);
    }

    private function logJobError(\Throwable $e): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);
        Log::channel('email-jobs')->error('Email job error', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'document_uid' => $this->document->uid,
            'document_id' => $this->document->id,
            'email_type' => $this->emailType,
            'recipient' => $this->document->customer_email,
            'error_type' => class_basename($e),
            'error_message' => $e->getMessage(),
            'duration_ms' => $duration,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'timestamp' => now()->toIso8601String(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null,
        ]);
    }

    private function logSuccess(): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);

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

            Log::channel('email-jobs')->info('Email job completed successfully', [
                'job_id' => $this->job?->getJobId() ?? 'unknown',
                'document_uid' => $this->document->uid,
                'document_id' => $this->document->id,
                'email_type' => $this->emailType,
                'recipient' => $this->document->customer_email,
                'duration_ms' => $duration,
                'attempt' => $this->attempts(),
                'timestamp' => now()->toIso8601String(),
            ]);

           
        } catch (\Exception $e) {
           
        }
    }

    private function logFailure(string $errorMessage): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);

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
            
        }

        Log::channel('email-jobs')->error('Email job failed after all retries', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'document_uid' => $this->document->uid,
            'document_id' => $this->document->id,
            'email_type' => $this->emailType,
            'recipient' => $this->document->customer_email,
            'error_message' => $errorMessage,
            'duration_ms' => $duration,
            'max_tries' => $this->tries,
            'timestamp' => now()->toIso8601String(),
        ]);

       
    }
}
