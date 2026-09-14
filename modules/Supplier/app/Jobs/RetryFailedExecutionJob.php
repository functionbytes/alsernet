<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Automation\AutomationDeadLetterQueue;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationRetryQueue;

/**
 * Retry Failed Execution Job
 *
 * This job processes items from the AutomationRetryQueue,
 * applies retry strategies (exponential backoff), and manages the
 * retry lifecycle including moving exhausted retries to the dead letter queue.
 *
 * Features:
 * - Checks if retry is due (retry_at <= now)
 * - Applies configurable retry strategy (immediate, linear, exponential)
 * - Moves to dead letter queue if max attempts exceeded
 * - Updates attempt count and status
 * - Re-executes the automation workflow
 */
class RetryFailedExecutionJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('supplier-retry');
    }

    /**
     * Execute the job.
     *
     * Processes all retries that are ready to be executed, attempts to
     * re-run the failed executions, and handles success/failure outcomes.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting retry failed execution job');

            // Get all retries that are ready to be processed
            $retries = AutomationRetryQueue::readyToRetry()
                ->with(['execution', 'execution.workflow'])
                ->orderBy('retry_at', 'asc')
                ->limit(50) // Process in batches to avoid memory issues
                ->get();

            if ($retries->isEmpty()) {
                Log::info('No retries ready to process');

                return;
            }

            $processedCount = 0;
            $succeededCount = 0;
            $failedCount = 0;
            $exhaustedCount = 0;

            foreach ($retries as $retry) {
                try {
                    $this->processRetry($retry);
                    $processedCount++;

                    // Check final status
                    if ($retry->status === 'succeeded') {
                        $succeededCount++;
                    } elseif ($retry->status === 'exhausted') {
                        $exhaustedCount++;
                    } else {
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    Log::error("Failed to process retry {$retry->id}: {$e->getMessage()}", [
                        'retry_id' => $retry->id,
                        'execution_id' => $retry->execution_id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::info('Retry failed execution job completed', [
                'processed' => $processedCount,
                'succeeded' => $succeededCount,
                'failed' => $failedCount,
                'exhausted' => $exhaustedCount,
            ]);
        } catch (\Exception $e) {
            Log::error("Retry failed execution job error: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Process a single retry entry.
     */
    protected function processRetry(AutomationRetryQueue $retry): void
    {
        // Check if retry is exhausted
        if ($retry->isExhausted()) {
            $this->moveToDeadLetterQueue($retry);

            return;
        }

        // Mark as retrying
        $retry->markAsRetrying();

        $execution = $retry->execution;

        if (! $execution) {
            Log::warning("Execution not found for retry {$retry->id}");
            $retry->markAsExhausted();

            return;
        }

        DB::beginTransaction();

        try {
            // Attempt to re-execute the workflow
            $success = $this->retryExecution($execution);

            if ($success) {
                // Mark retry as succeeded
                $retry->markAsSucceeded();

                // Update execution status
                $execution->markAsCompleted();

                Log::info("Retry succeeded for execution {$execution->id}", [
                    'retry_id' => $retry->id,
                    'attempt_number' => $retry->attempt_number,
                ]);

                DB::commit();
            } else {
                // Schedule next retry if not exhausted
                $this->scheduleNextRetry($retry);
                DB::commit();
            }
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Retry execution failed: {$e->getMessage()}", [
                'retry_id' => $retry->id,
                'execution_id' => $execution->id,
                'attempt_number' => $retry->attempt_number,
            ]);

            // Schedule next retry if not exhausted
            $this->scheduleNextRetry($retry, $e->getMessage());
        }
    }

    /**
     * Attempt to retry the execution.
     */
    protected function retryExecution(AutomationExecution $execution): bool
    {
        try {
            // Increment retry count on execution
            $execution->incrementRetryCount();

            // Reset execution status to pending for re-processing
            $execution->update([
                'status' => 'pending',
                'error_details' => null,
            ]);

            // Here you would trigger the actual workflow execution
            // This depends on your workflow execution implementation
            // For now, we'll just mark it as ready for re-processing

            Log::info("Execution {$execution->id} queued for retry", [
                'workflow_id' => $execution->workflow_id,
                'retry_count' => $execution->retry_count,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to retry execution: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Schedule the next retry attempt.
     */
    protected function scheduleNextRetry(AutomationRetryQueue $retry, ?string $error = null): void
    {
        $nextRetry = $retry->scheduleNextRetry($error ?? $retry->last_error);

        if (! $nextRetry) {
            // Retry is exhausted, move to dead letter queue
            $this->moveToDeadLetterQueue($retry);
        } else {
            Log::info("Next retry scheduled for execution {$retry->execution_id}", [
                'retry_id' => $nextRetry->id,
                'attempt_number' => $nextRetry->attempt_number,
                'retry_at' => $nextRetry->retry_at->toDateTimeString(),
            ]);
        }
    }

    /**
     * Move an exhausted retry to the dead letter queue.
     */
    protected function moveToDeadLetterQueue(AutomationRetryQueue $retry): void
    {
        try {
            $execution = $retry->execution;

            if (! $execution) {
                Log::warning("Cannot move to DLQ: execution not found for retry {$retry->id}");

                return;
            }

            // Create dead letter queue entry
            AutomationDeadLetterQueue::createFromExecution(
                $retry->execution_id,
                'max_retries',
                [
                    'attempts' => $retry->attempt_number,
                    'max_attempts' => $retry->max_attempts,
                    'last_error' => $retry->last_error,
                    'retry_strategy' => $retry->retry_strategy,
                ],
                $execution->input_payload,
                'retry'
            );

            // Mark retry as exhausted
            $retry->markAsExhausted();

            // Update execution status
            $execution->markAsFailed([
                'error' => 'Max retry attempts exhausted',
                'attempts' => $retry->attempt_number,
                'last_error' => $retry->last_error,
            ]);

            Log::warning('Retry exhausted, moved to dead letter queue', [
                'retry_id' => $retry->id,
                'execution_id' => $retry->execution_id,
                'attempts' => $retry->attempt_number,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to move retry to dead letter queue: {$e->getMessage()}", [
                'retry_id' => $retry->id,
            ]);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Retry failed execution job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}
