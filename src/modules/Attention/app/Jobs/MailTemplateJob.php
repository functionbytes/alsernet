<?php

namespace Modules\Attention\Jobs;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Attention\Models\Attention;
use Modules\Attention\Services\AttentionEmailTemplateService;

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

    private float $startTime;

    public function __construct(
        private Attention $attention,
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
                'confirmation' => AttentionEmailTemplateService::sendConfirmation($this->attention, $this->adminId),
                'assigned' => AttentionEmailTemplateService::sendAssignedNotification($this->attention, $this->adminId),
                'resolution' => AttentionEmailTemplateService::sendResolutionNotification($this->attention, $this->adminId),
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
        Log::channel('email-jobs')->info('Attention email job started', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'attention_uid' => $this->attention->uid,
            'attention_id' => $this->attention->id,
            'email_type' => $this->emailType,
            'recipient' => $this->attention->customer_email,
            'timestamp' => now()->toIso8601String(),
            'attempt' => $this->attempts(),
        ]);
    }

    private function logJobError(\Throwable $e): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);

        Log::channel('email-jobs')->error('Attention email job error', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'attention_uid' => $this->attention->uid,
            'attention_id' => $this->attention->id,
            'email_type' => $this->emailType,
            'recipient' => $this->attention->customer_email,
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

        Log::channel('email-jobs')->info('Attention email job completed successfully', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'attention_uid' => $this->attention->uid,
            'attention_id' => $this->attention->id,
            'email_type' => $this->emailType,
            'recipient' => $this->attention->customer_email,
            'duration_ms' => $duration,
            'attempt' => $this->attempts(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function logFailure(string $errorMessage): void
    {
        $duration = round((microtime(true) - $this->startTime) * 1000, 2);

        Log::channel('email-jobs')->error('Attention email job failed after all retries', [
            'job_id' => $this->job?->getJobId() ?? 'unknown',
            'attention_uid' => $this->attention->uid,
            'attention_id' => $this->attention->id,
            'email_type' => $this->emailType,
            'recipient' => $this->attention->customer_email,
            'error_message' => $errorMessage,
            'duration_ms' => $duration,
            'max_tries' => $this->tries,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
