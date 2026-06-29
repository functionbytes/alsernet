<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Exceptions\EmailValidationException;
use Modules\Mailrelay\Services\EmailValidation\EmailValidatorService;

/**
 * ValidateEmailJob
 *
 * Queue job for asynchronous email validation processing.
 * Implements ShouldQueue for background processing on the 'high' priority queue.
 *
 * Features:
 * - Asynchronous email validation with configurable validators
 * - Error handling with failed() callback
 * - Support for validation options (level, validators, caching)
 * - Result storage in database for future retrieval
 *
 * Reference: FASE 7 - Section 7.1
 */
class ValidateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The email address to validate
     */
    public string $email;

    /**
     * Array of validator names to use
     */
    public array $validators;

    /**
     * Additional validation options
     */
    public array $options;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job
     */
    public int $backoff = 60;

    /**
     * The number of seconds the job can run before timing out
     */
    public int $timeout = 120;

    /**
     * Create a new job instance
     *
     * @param  string  $email  Email address to validate
     * @param  array  $validators  List of validator names to use
     * @param  array  $options  Additional validation options
     */
    public function __construct(string $email, array $validators = [], array $options = [])
    {
        $this->email = strtolower(trim($email));
        $this->validators = $validators;
        $this->options = $options;

        // Set queue to 'high' priority
        $this->onQueue('high');
    }

    /**
     * Execute the job using EmailValidatorService
     *
     * @throws EmailValidationException
     */
    public function handle(EmailValidatorService $emailValidatorService): void
    {
        Log::info('ValidateEmailJob: Starting validation', [
            'email' => $this->email,
            'validators' => $this->validators,
            'attempt' => $this->attempts(),
        ]);

        try {
            // Prepare validation options
            $validationOptions = array_merge($this->options, [
                'store_result' => true, // Always store results for queued validations
            ]);

            // Add validators if specified
            if (! empty($this->validators)) {
                $validationOptions['validators'] = $this->validators;
            }

            // Perform validation
            $result = $emailValidatorService->validate($this->email, $validationOptions);

            Log::info('ValidateEmailJob: Validation completed successfully', [
                'email' => $this->email,
                'status' => $result['status'],
                'score' => $result['score'],
                'cost' => $result['cost'] ?? 0,
            ]);

        } catch (EmailValidationException $e) {
            Log::error('ValidateEmailJob: Validation exception occurred', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            // Rethrow to trigger retry logic
            throw $e;
        } catch (\Exception $e) {
            Log::error('ValidateEmailJob: Unexpected error during validation', [
                'email' => $this->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);

            // Rethrow to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure
     *
     * Called when the job has exhausted all retry attempts or encounters
     * a non-recoverable error. Logs the failure for monitoring and debugging.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ValidateEmailJob: Job failed after all retry attempts', [
            'email' => $this->email,
            'validators' => $this->validators,
            'error' => $exception->getMessage(),
            'exception_class' => get_class($exception),
            'attempts' => $this->attempts(),
        ]);

        // Additional failure handling could be added here:
        // - Send notification to administrators
        // - Update database to mark email as failed validation
        // - Add to dead letter queue for manual review
        // - Trigger alternative validation workflow
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return [
            'email-validation',
            'email:'.$this->email,
            'validators:'.count($this->validators),
        ];
    }
}
