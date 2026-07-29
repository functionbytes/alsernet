<?php

namespace Modules\Supplier\Services;

use Cron\CronExpression;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Automation\AutomationAlert;
use Modules\Supplier\Models\Automation\AutomationChain;
use Modules\Supplier\Models\Automation\AutomationChainExecution;
use Modules\Supplier\Models\Automation\AutomationDeadLetterQueue;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationHealthCheck;
use Modules\Supplier\Models\Automation\AutomationRateLimit;
use Modules\Supplier\Models\Automation\AutomationRetryQueue;
use Modules\Supplier\Models\Automation\AutomationTrigger;
use Modules\Supplier\Models\Automation\AutomationWorkflow;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Traits\ValidatesPublicUrl;

class AutomationOrchestrationService
{
    use ValidatesPublicUrl;

    /**
     * Circuit breaker configuration
     */
    private array $circuitBreakerConfig = [
        'failure_threshold' => 5,
        'timeout_seconds' => 30,
        'reset_timeout_seconds' => 60,
    ];

    /**
     * HTTP client instance
     */
    private Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'http_errors' => false,
        ]);
    }

    /**
     * Execute a workflow
     */
    public function executeWorkflow(AutomationWorkflow $workflow, array $payload = []): AutomationExecution
    {
        try {
            if (! $workflow->isActive()) {
                throw new \Exception("Workflow '{$workflow->name}' is not active");
            }

            $execution = AutomationExecution::createExecution(
                workflowId: $workflow->id,
                supplierId: $payload['supplier_id'] ?? null,
                sourceId: $payload['source_id'] ?? null,
                triggerType: $payload['trigger_type'] ?? 'manual',
                inputPayload: $payload,
                executionContext: $payload['context'] ?? [],
                triggeredBy: $payload['triggered_by'] ?? auth()->id()
            );

            $workflow->incrementExecutions();

            Log::info("Workflow execution created: {$workflow->name}", [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'trigger_type' => $execution->trigger_type,
            ]);

            $this->dispatchWorkflowExecution($execution);

            return $execution;
        } catch (\Exception $e) {
            Log::error("Failed to execute workflow: {$workflow->name}", [
                'workflow_id' => $workflow->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Trigger workflow by name
     */
    public function triggerWorkflowByName(string $name, array $payload = []): AutomationExecution
    {
        $workflow = AutomationWorkflow::where('name', $name)
            ->active()
            ->firstOrFail();

        return $this->executeWorkflow($workflow, $payload);
    }

    /**
     * Cancel an execution
     */
    public function cancelExecution(AutomationExecution $execution): void
    {
        if ($execution->isFinished()) {
            throw new \Exception('Cannot cancel a finished execution');
        }

        $execution->markAsCancelled('Cancelled by user');

        Log::info('Workflow execution cancelled', [
            'execution_id' => $execution->id,
            'workflow_id' => $execution->workflow_id,
        ]);
    }

    /**
     * Send payload to external orchestrator (n8n/Make)
     */
    public function sendToOrchestrator(string $webhookUrl, array $payload, string $secret): array
    {
        try {
            $this->assertUrlIsPublic($webhookUrl);
        } catch (\InvalidArgumentException $e) {
            Log::error('Refused to send payload to non-public orchestrator endpoint', [
                'webhook_url' => $webhookUrl,
                'reason' => $e->getMessage(),
            ]);

            throw new \Exception('Orchestrator endpoint is not allowed: '.$e->getMessage());
        }

        $cacheKey = "circuit_breaker:{$webhookUrl}";

        if ($this->isCircuitBreakerOpen($cacheKey)) {
            throw new \Exception('Circuit breaker is open for this orchestrator endpoint');
        }

        try {
            $signature = $this->generateSignature($payload, $secret);

            $response = $this->httpClient->post($webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'X-Signature' => $signature,
                    'X-Timestamp' => now()->timestamp,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);

            if ($statusCode >= 200 && $statusCode < 300) {
                $this->resetCircuitBreaker($cacheKey);

                Log::info('Successfully sent payload to orchestrator', [
                    'webhook_url' => $webhookUrl,
                    'status_code' => $statusCode,
                ]);

                return [
                    'success' => true,
                    'status_code' => $statusCode,
                    'response' => $body,
                ];
            }

            $this->recordCircuitBreakerFailure($cacheKey);

            Log::warning('Orchestrator returned error status', [
                'webhook_url' => $webhookUrl,
                'status_code' => $statusCode,
                'response' => $body,
            ]);

            return [
                'success' => false,
                'status_code' => $statusCode,
                'response' => $body,
                'error' => 'Orchestrator returned error status',
            ];
        } catch (ConnectException $e) {
            $this->recordCircuitBreakerFailure($cacheKey);

            Log::error('Connection failed to orchestrator', [
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Connection failed to orchestrator: {$e->getMessage()}");
        } catch (RequestException $e) {
            $this->recordCircuitBreakerFailure($cacheKey);

            Log::error('Request failed to orchestrator', [
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception("Request failed to orchestrator: {$e->getMessage()}");
        }
    }

    /**
     * Handle callback from orchestrator
     */
    public function handleCallback(array $payload, string $signature): void
    {
        $executionId = $payload['execution_id'] ?? null;

        if (! $executionId) {
            throw new \Exception('Missing execution_id in callback payload');
        }

        $execution = AutomationExecution::where('uid', $executionId)
            ->orWhere('external_execution_id', $executionId)
            ->firstOrFail();

        $workflow = $execution->workflow;
        if (! $workflow) {
            throw new \Exception("Workflow not found for execution {$executionId}");
        }
        $secret = $workflow->workflow_config['callback_secret'] ?? config('app.key');

        if (! $this->verifySignature(json_encode($payload), $signature, $secret)) {
            Log::warning('Invalid callback signature', [
                'execution_id' => $executionId,
            ]);

            throw new \Exception('Invalid callback signature');
        }

        $status = $payload['status'] ?? null;
        $outputData = $payload['output_data'] ?? null;
        $errorDetails = $payload['error_details'] ?? null;

        switch ($status) {
            case 'completed':
                $execution->markAsCompleted($outputData);
                break;
            case 'failed':
                $execution->markAsFailed($errorDetails);
                $this->scheduleRetry($execution, $errorDetails['error'] ?? 'Unknown error');
                break;
            case 'running':
                $execution->markAsRunning($payload['external_execution_id'] ?? null);
                break;
            default:
                Log::warning('Unknown callback status', [
                    'execution_id' => $executionId,
                    'status' => $status,
                ]);
        }

        Log::info('Callback processed', [
            'execution_id' => $executionId,
            'status' => $status,
        ]);
    }

    /**
     * Verify HMAC-SHA256 signature
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Execute a chain of workflows
     */
    public function executeChain(AutomationChain $chain, array $context = []): AutomationChainExecution
    {
        if (! $chain->is_enabled) {
            throw new \Exception("Chain '{$chain->name}' is not enabled");
        }

        if (! $chain->isValid()) {
            $errors = implode(', ', $chain->validateDefinition());
            throw new \Exception("Chain definition is invalid: {$errors}");
        }

        try {
            DB::beginTransaction();

            $execution = AutomationChainExecution::create([
                'chain_id' => $chain->id,
                'status' => 'pending',
                'execution_context' => $context,
                'completed_stages' => [],
                'failed_stages' => [],
                'stage_results' => [],
                'triggered_by' => $context['triggered_by'] ?? 'manual',
                'triggered_by_user' => $context['triggered_by_user'] ?? auth()->id(),
            ]);

            $execution->start();

            $firstStage = $chain->getFirstStage();

            if ($firstStage) {
                $execution->moveToStage($firstStage['id']);
                $this->executeStage($execution, $firstStage);
            } else {
                $execution->complete();
            }

            DB::commit();

            Log::info('Chain execution started', [
                'chain_id' => $chain->id,
                'execution_id' => $execution->id,
                'first_stage' => $firstStage['id'] ?? null,
            ]);

            return $execution;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to execute chain', [
                'chain_id' => $chain->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute next stage in chain
     */
    public function executeNextStage(AutomationChainExecution $chainExecution): void
    {
        if (! $chainExecution->isRunning()) {
            Log::warning('Cannot execute next stage: execution not running', [
                'execution_id' => $chainExecution->id,
                'status' => $chainExecution->status,
            ]);

            return;
        }

        $chain = $chainExecution->chain;
        $currentStageId = $chainExecution->current_stage;

        $nextStages = $chain->getNextStages($currentStageId);

        if (empty($nextStages)) {
            $chainExecution->complete();

            Log::info('Chain execution completed', [
                'execution_id' => $chainExecution->id,
                'completed_stages' => count($chainExecution->completed_stages),
            ]);

            $this->executeCompletionHandlers($chainExecution);

            return;
        }

        if ($chain->parallel_stages && count($nextStages) > 1) {
            $maxParallel = min($chain->max_parallel, count($nextStages));
            $stagesToExecute = array_slice($nextStages, 0, $maxParallel);

            foreach ($stagesToExecute as $stage) {
                $this->executeStage($chainExecution, $stage);
            }
        } else {
            $this->executeStage($chainExecution, $nextStages[0]);
        }
    }

    /**
     * Handle stage completion
     */
    public function handleStageCompletion(AutomationChainExecution $chainExecution, string $stageId, array $result): void
    {
        try {
            DB::beginTransaction();

            $chainExecution->completeStage($stageId, $result);

            $chain = $chainExecution->chain;
            $stage = $chain->getStage($stageId);

            if ($stage && ($stage['requires_approval'] ?? false)) {
                $chainExecution->requestApproval($stageId);

                Log::info('Stage completed, waiting for approval', [
                    'execution_id' => $chainExecution->id,
                    'stage_id' => $stageId,
                ]);
            } else {
                $this->executeNextStage($chainExecution);
            }

            DB::commit();

            Log::info('Stage completed', [
                'execution_id' => $chainExecution->id,
                'stage_id' => $stageId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            $chainExecution->failStage($stageId, [
                'error' => $e->getMessage(),
            ]);

            $this->handleStageFailure($chainExecution, $stageId, $e->getMessage());

            Log::error('Stage completion handling failed', [
                'execution_id' => $chainExecution->id,
                'stage_id' => $stageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Process a trigger
     */
    public function processTrigger(AutomationTrigger $trigger, array $context = []): ?AutomationExecution
    {
        if (! $trigger->canTrigger($context)) {
            Log::debug('Trigger conditions not met', [
                'trigger_id' => $trigger->id,
                'trigger_name' => $trigger->name,
            ]);

            return null;
        }

        $workflow = $trigger->workflow;

        if (! $workflow) {
            Log::warning('Trigger has no associated workflow', [
                'trigger_id' => $trigger->id,
            ]);

            return null;
        }

        $mergedContext = array_merge(
            $trigger->execution_context ?? [],
            $context,
            ['trigger_id' => $trigger->id]
        );

        $execution = $this->executeWorkflow($workflow, $mergedContext);

        $trigger->increment('total_triggers');
        $trigger->update(['last_triggered_at' => now()]);

        Log::info('Trigger processed', [
            'trigger_id' => $trigger->id,
            'trigger_name' => $trigger->name,
            'execution_id' => $execution->id,
        ]);

        return $execution;
    }

    /**
     * Check and process scheduled triggers
     */
    public function checkScheduledTriggers(): void
    {
        $triggers = AutomationTrigger::enabled()
            ->schedule()
            ->get();

        foreach ($triggers as $trigger) {
            $cronExpression = $trigger->getCronExpression();

            if (! $cronExpression) {
                continue;
            }

            if ($this->shouldRunScheduledTrigger($trigger, $cronExpression)) {
                try {
                    $this->processTrigger($trigger);
                } catch (\Exception $e) {
                    Log::error('Scheduled trigger execution failed', [
                        'trigger_id' => $trigger->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Handle source change trigger
     */
    public function handleSourceChangeTrigger(Source $source, string $changeType): void
    {
        $triggers = AutomationTrigger::enabled()
            ->sourceChange()
            ->get();

        foreach ($triggers as $trigger) {
            $sourceFilter = $trigger->source_filter ?? [];

            if (! empty($sourceFilter['source_ids']) && ! in_array($source->id, $sourceFilter['source_ids'])) {
                continue;
            }

            if (! empty($sourceFilter['change_types']) && ! in_array($changeType, $sourceFilter['change_types'])) {
                continue;
            }

            try {
                $this->processTrigger($trigger, [
                    'source_id' => $source->id,
                    'supplier_id' => $source->supplier_id,
                    'change_type' => $changeType,
                ]);
            } catch (\Exception $e) {
                Log::error('Source change trigger execution failed', [
                    'trigger_id' => $trigger->id,
                    'source_id' => $source->id,
                    'change_type' => $changeType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check rate limit for an entity
     */
    public function checkRateLimit(Model $entity, string $windowType = 'minute'): bool
    {
        $maxRequests = $this->getMaxRequestsForWindow($windowType);

        $rateLimit = AutomationRateLimit::forModel($entity, $windowType, $maxRequests);

        if ($rateLimit->isBlocked()) {
            Log::debug('Rate limit blocked', [
                'entity_type' => get_class($entity),
                'entity_id' => $entity->id,
                'window_type' => $windowType,
            ]);

            return false;
        }

        if ($rateLimit->isWindowExpired()) {
            $rateLimit->resetWindow();
        }

        return $rateLimit->current_count < $maxRequests;
    }

    /**
     * Increment rate limit counter
     */
    public function incrementRateLimit(Model $entity, string $windowType = 'minute'): void
    {
        $maxRequests = $this->getMaxRequestsForWindow($windowType);

        $rateLimit = AutomationRateLimit::forModel($entity, $windowType, $maxRequests);

        if ($rateLimit->isWindowExpired()) {
            $rateLimit->resetWindow();
        }

        $rateLimit->incrementCounter();
    }

    /**
     * Schedule retry for failed execution
     */
    public function scheduleRetry(AutomationExecution $execution, string $error): AutomationRetryQueue
    {
        $workflow = $execution->workflow;
        $maxRetries = $workflow->max_retries;

        $existingRetry = AutomationRetryQueue::where('execution_id', $execution->id)
            ->latest()
            ->first();

        if ($existingRetry && $existingRetry->isExhausted()) {
            Log::warning('Execution retries exhausted, moving to dead letter queue', [
                'execution_id' => $execution->id,
            ]);

            $this->moveToDeadLetterQueue($existingRetry);

            throw new \Exception('Max retry attempts exhausted');
        }

        if ($existingRetry) {
            $retry = $existingRetry->scheduleNextRetry($error);

            if (! $retry) {
                $this->moveToDeadLetterQueue($existingRetry);

                throw new \Exception('Max retry attempts exhausted');
            }
        } else {
            $retry = AutomationRetryQueue::createForExecution(
                executionId: $execution->id,
                error: $error,
                strategy: 'exponential',
                maxAttempts: $maxRetries
            );
        }

        Log::info('Retry scheduled', [
            'execution_id' => $execution->id,
            'retry_id' => $retry->id,
            'attempt_number' => $retry->attempt_number,
            'retry_at' => $retry->retry_at->toDateTimeString(),
        ]);

        return $retry;
    }

    /**
     * Process retry queue
     */
    public function processRetryQueue(): void
    {
        $retries = AutomationRetryQueue::readyToRetry()
            ->orderBy('retry_at', 'asc')
            ->limit(10)
            ->get();

        foreach ($retries as $retry) {
            try {
                $retry->markAsRetrying();

                $execution = $retry->execution;

                Log::info('Processing retry', [
                    'retry_id' => $retry->id,
                    'execution_id' => $execution->id,
                    'attempt_number' => $retry->attempt_number,
                ]);

                $this->dispatchWorkflowExecution($execution);

                $retry->markAsSucceeded();
            } catch (\Exception $e) {
                Log::error('Retry processing failed', [
                    'retry_id' => $retry->id,
                    'error' => $e->getMessage(),
                ]);

                if ($retry->isExhausted()) {
                    $this->moveToDeadLetterQueue($retry);
                } else {
                    $retry->scheduleNextRetry($e->getMessage());
                }
            }
        }
    }

    /**
     * Move failed retry to dead letter queue
     */
    public function moveToDeadLetterQueue(AutomationRetryQueue $retry): AutomationDeadLetterQueue
    {
        $retry->markAsExhausted();

        $execution = $retry->execution;

        $deadLetter = AutomationDeadLetterQueue::createFromExecution(
            executionId: $execution->id,
            failureReason: 'max_retries',
            errorDetails: [
                'last_error' => $retry->last_error,
                'total_attempts' => $retry->attempt_number,
            ],
            originalPayload: $execution->input_payload,
            requiredAction: 'retry'
        );

        $this->createAlert(
            type: 'execution_failed',
            severity: 'error',
            title: 'Execution moved to dead letter queue',
            message: "Execution {$execution->uid} failed after {$retry->attempt_number} retries"
        );

        Log::warning('Execution moved to dead letter queue', [
            'execution_id' => $execution->id,
            'retry_id' => $retry->id,
            'dead_letter_id' => $deadLetter->id,
        ]);

        return $deadLetter;
    }

    /**
     * Check system health
     */
    public function checkSystemHealth(): array
    {
        $health = [
            'overall_status' => 'healthy',
            'checks' => [],
        ];

        $databaseStatus = $this->checkDatabaseHealth();
        $health['checks']['database'] = $databaseStatus;

        $queueStatus = $this->checkQueueHealth();
        $health['checks']['queue'] = $queueStatus;

        $retryQueueStatus = $this->checkRetryQueueHealth();
        $health['checks']['retry_queue'] = $retryQueueStatus;

        $deadLetterStatus = $this->checkDeadLetterQueueHealth();
        $health['checks']['dead_letter_queue'] = $deadLetterStatus;

        $activeExecutionsStatus = $this->checkActiveExecutionsHealth();
        $health['checks']['active_executions'] = $activeExecutionsStatus;

        $unhealthyChecks = collect($health['checks'])
            ->filter(fn ($check) => $check['status'] !== 'healthy')
            ->count();

        if ($unhealthyChecks > 0) {
            $health['overall_status'] = $unhealthyChecks >= 3 ? 'unhealthy' : 'degraded';
        }

        $this->recordHealthCheck(
            checkType: 'system',
            targetId: 'overall',
            success: $health['overall_status'] === 'healthy',
            error: $health['overall_status'] !== 'healthy' ? "{$unhealthyChecks} checks failed" : null
        );

        return $health;
    }

    /**
     * Record health check
     */
    public function recordHealthCheck(string $checkType, int|string $targetId, bool $success, ?string $error = null): void
    {
        AutomationHealthCheck::record(
            checkType: $checkType,
            targetId: (string) $targetId,
            status: $success ? 'healthy' : 'unhealthy',
            responseTimeMs: null,
            errorMessage: $error,
            metadata: null
        );
    }

    /**
     * Create alert
     */
    public function createAlert(string $type, string $severity, string $title, string $message): AutomationAlert
    {
        return AutomationAlert::createThrottled(
            alertType: $type,
            severity: $severity,
            title: $title,
            message: $message,
            context: null,
            relatedModel: null,
            throttleMinutes: 30
        );
    }

    /**
     * Dispatch workflow execution to orchestrator
     */
    private function dispatchWorkflowExecution(AutomationExecution $execution): void
    {
        $workflow = $execution->workflow;

        if (! $workflow->webhook_url) {
            Log::warning('Workflow has no webhook URL configured', [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
            ]);

            return;
        }

        $execution->markAsQueued();

        $payload = array_merge(
            $workflow->default_variables ?? [],
            $execution->input_payload ?? [],
            [
                'execution_id' => $execution->uid,
                'workflow_id' => $workflow->external_workflow_id ?? $workflow->uid,
                'callback_url' => $workflow->callback_url,
            ]
        );

        try {
            $secret = $workflow->workflow_config['webhook_secret'] ?? config('app.key');

            $result = $this->sendToOrchestrator($workflow->webhook_url, $payload, $secret);

            if ($result['success']) {
                $execution->markAsRunning($result['response']['execution_id'] ?? null);
            } else {
                $execution->markAsFailed([
                    'error' => 'Orchestrator rejected execution',
                    'details' => $result,
                ]);
            }
        } catch (\Exception $e) {
            $execution->markAsFailed([
                'error' => $e->getMessage(),
            ]);

            Log::error('Failed to dispatch workflow execution', [
                'workflow_id' => $workflow->id,
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute a single stage
     */
    private function executeStage(AutomationChainExecution $chainExecution, array $stage): void
    {
        $workflowId = $stage['workflow_id'] ?? null;

        if (! $workflowId) {
            throw new \Exception("Stage {$stage['id']} has no workflow_id");
        }

        $workflow = AutomationWorkflow::find($workflowId);

        if (! $workflow) {
            throw new \Exception("Workflow {$workflowId} not found for stage {$stage['id']}");
        }

        $context = array_merge(
            $chainExecution->execution_context ?? [],
            $stage['variables'] ?? [],
            [
                'chain_execution_id' => $chainExecution->id,
                'stage_id' => $stage['id'],
            ]
        );

        $execution = $this->executeWorkflow($workflow, $context);

        $chainExecution->moveToStage($stage['id']);

        Log::info('Stage execution started', [
            'chain_execution_id' => $chainExecution->id,
            'stage_id' => $stage['id'],
            'workflow_id' => $workflow->id,
            'execution_id' => $execution->id,
        ]);
    }

    /**
     * Handle stage failure
     */
    private function handleStageFailure(AutomationChainExecution $chainExecution, string $stageId, string $error): void
    {
        $chain = $chainExecution->chain;
        $failStrategy = $chain->fail_strategy;

        switch ($failStrategy) {
            case 'stop':
                $chainExecution->fail();

                Log::info('Chain execution failed (stop strategy)', [
                    'execution_id' => $chainExecution->id,
                    'failed_stage' => $stageId,
                ]);
                break;

            case 'continue':
                $this->executeNextStage($chainExecution);

                Log::info('Chain execution continuing after stage failure (continue strategy)', [
                    'execution_id' => $chainExecution->id,
                    'failed_stage' => $stageId,
                ]);
                break;

            case 'skip':
                $chainExecution->completeStage($stageId, ['skipped' => true, 'error' => $error]);
                $this->executeNextStage($chainExecution);

                Log::info('Chain execution skipping failed stage (skip strategy)', [
                    'execution_id' => $chainExecution->id,
                    'skipped_stage' => $stageId,
                ]);
                break;

            default:
                $chainExecution->fail();

                Log::warning('Unknown fail strategy, stopping chain', [
                    'execution_id' => $chainExecution->id,
                    'fail_strategy' => $failStrategy,
                ]);
        }

        $errorHandler = $chain->getStageErrorHandler();

        if ($errorHandler) {
            $this->executeErrorHandler($chainExecution, $errorHandler, $stageId, $error);
        }
    }

    /**
     * Execute error handler
     */
    private function executeErrorHandler(AutomationChainExecution $chainExecution, array $handler, string $stageId, string $error): void
    {
        $workflowId = $handler['workflow_id'] ?? null;

        if (! $workflowId) {
            return;
        }

        try {
            $workflow = AutomationWorkflow::find($workflowId);

            if (! $workflow) {
                Log::warning('Error handler workflow not found', [
                    'workflow_id' => $workflowId,
                ]);

                return;
            }

            $this->executeWorkflow($workflow, [
                'chain_execution_id' => $chainExecution->id,
                'failed_stage_id' => $stageId,
                'error' => $error,
            ]);

            Log::info('Error handler executed', [
                'chain_execution_id' => $chainExecution->id,
                'failed_stage' => $stageId,
                'handler_workflow_id' => $workflowId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error handler execution failed', [
                'chain_execution_id' => $chainExecution->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Execute completion handlers
     */
    private function executeCompletionHandlers(AutomationChainExecution $chainExecution): void
    {
        $chain = $chainExecution->chain;
        $successHandler = $chain->getSuccessHandler();

        if (! $successHandler) {
            return;
        }

        $workflowId = $successHandler['workflow_id'] ?? null;

        if (! $workflowId) {
            return;
        }

        try {
            $workflow = AutomationWorkflow::find($workflowId);

            if (! $workflow) {
                Log::warning('Completion handler workflow not found', [
                    'workflow_id' => $workflowId,
                ]);

                return;
            }

            $this->executeWorkflow($workflow, [
                'chain_execution_id' => $chainExecution->id,
                'stage_results' => $chainExecution->stage_results,
            ]);

            Log::info('Completion handler executed', [
                'chain_execution_id' => $chainExecution->id,
                'handler_workflow_id' => $workflowId,
            ]);
        } catch (\Exception $e) {
            Log::error('Completion handler execution failed', [
                'chain_execution_id' => $chainExecution->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Generate HMAC-SHA256 signature
     */
    private function generateSignature(array $payload, string $secret): string
    {
        return hash_hmac('sha256', json_encode($payload), $secret);
    }

    /**
     * Check if circuit breaker is open
     */
    private function isCircuitBreakerOpen(string $cacheKey): bool
    {
        $state = Cache::get($cacheKey, ['failures' => 0, 'state' => 'closed']);

        if ($state['state'] === 'open') {
            if (now()->timestamp - ($state['opened_at'] ?? 0) > $this->circuitBreakerConfig['reset_timeout_seconds']) {
                Cache::put($cacheKey, ['failures' => 0, 'state' => 'half-open'], 300);

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Record circuit breaker failure
     */
    private function recordCircuitBreakerFailure(string $cacheKey): void
    {
        $state = Cache::get($cacheKey, ['failures' => 0, 'state' => 'closed']);
        $failures = ($state['failures'] ?? 0) + 1;

        if ($failures >= $this->circuitBreakerConfig['failure_threshold']) {
            Cache::put($cacheKey, [
                'failures' => $failures,
                'state' => 'open',
                'opened_at' => now()->timestamp,
            ], 300);

            Log::warning('Circuit breaker opened', [
                'cache_key' => $cacheKey,
                'failures' => $failures,
            ]);
        } else {
            Cache::put($cacheKey, ['failures' => $failures, 'state' => 'closed'], 300);
        }
    }

    /**
     * Reset circuit breaker
     */
    private function resetCircuitBreaker(string $cacheKey): void
    {
        Cache::put($cacheKey, ['failures' => 0, 'state' => 'closed'], 300);
    }

    /**
     * Get max requests for window type
     */
    private function getMaxRequestsForWindow(string $windowType): int
    {
        return match ($windowType) {
            'minute' => 60,
            'hour' => 1000,
            'day' => 10000,
            default => 60,
        };
    }

    /**
     * Check if scheduled trigger should run
     */
    private function shouldRunScheduledTrigger(AutomationTrigger $trigger, string $cronExpression): bool
    {
        $cacheKey = "trigger_last_run:{$trigger->id}";
        $lastRun = Cache::get($cacheKey);

        if ($lastRun && now()->diffInMinutes($lastRun) < 1) {
            return false;
        }

        try {
            $cron = new CronExpression($cronExpression);

            if ($cron->isDue()) {
                Cache::put($cacheKey, now(), 3600);

                return true;
            }
        } catch (\Exception $e) {
            Log::error('Invalid cron expression', [
                'trigger_id' => $trigger->id,
                'cron_expression' => $cronExpression,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue health
     */
    private function checkQueueHealth(): array
    {
        $pendingExecutions = AutomationExecution::pending()->count();
        $queuedExecutions = AutomationExecution::queued()->count();

        if ($queuedExecutions > 100) {
            return [
                'status' => 'degraded',
                'message' => 'High number of queued executions',
                'queued' => $queuedExecutions,
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Queue is healthy',
            'pending' => $pendingExecutions,
            'queued' => $queuedExecutions,
        ];
    }

    /**
     * Check retry queue health
     */
    private function checkRetryQueueHealth(): array
    {
        $pendingRetries = AutomationRetryQueue::pending()->count();
        $readyToRetry = AutomationRetryQueue::readyToRetry()->count();

        if ($pendingRetries > 50) {
            return [
                'status' => 'degraded',
                'message' => 'High number of pending retries',
                'pending' => $pendingRetries,
                'ready' => $readyToRetry,
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Retry queue is healthy',
            'pending' => $pendingRetries,
            'ready' => $readyToRetry,
        ];
    }

    /**
     * Check dead letter queue health
     */
    private function checkDeadLetterQueueHealth(): array
    {
        $unresolvedItems = AutomationDeadLetterQueue::unresolved()->count();
        $needsIntervention = AutomationDeadLetterQueue::needsIntervention()->count();

        if ($unresolvedItems > 20) {
            return [
                'status' => 'unhealthy',
                'message' => 'High number of unresolved dead letter items',
                'unresolved' => $unresolvedItems,
                'needs_intervention' => $needsIntervention,
            ];
        }

        if ($needsIntervention > 5) {
            return [
                'status' => 'degraded',
                'message' => 'Items requiring manual intervention',
                'needs_intervention' => $needsIntervention,
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Dead letter queue is healthy',
            'unresolved' => $unresolvedItems,
        ];
    }

    /**
     * Check active executions health
     */
    private function checkActiveExecutionsHealth(): array
    {
        $runningExecutions = AutomationExecution::running()->count();
        $stuckExecutions = AutomationExecution::running()
            ->where('started_at', '<', now()->subHours(2))
            ->count();

        if ($stuckExecutions > 0) {
            return [
                'status' => 'degraded',
                'message' => 'Stuck executions detected',
                'running' => $runningExecutions,
                'stuck' => $stuckExecutions,
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Active executions are healthy',
            'running' => $runningExecutions,
        ];
    }
}
