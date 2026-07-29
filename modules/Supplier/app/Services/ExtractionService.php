<?php

namespace Modules\Supplier\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Events\ExtractionBatchCompleted;
use Modules\Supplier\Events\ExtractionBatchStarted;
use Modules\Supplier\Events\ExtractionResultProcessed;
use Modules\Supplier\Events\OrchestratorWorkflowTriggered;
use Modules\Supplier\Exceptions\ExtractionException;
use Modules\Supplier\Models\Automation\AutomationExecution;
use Modules\Supplier\Models\Automation\AutomationWorkflow;
use Modules\Supplier\Models\Extraction\ExtractionBatch;
use Modules\Supplier\Models\Extraction\ExtractionMapping;
use Modules\Supplier\Models\Extraction\ExtractionResult;
use Modules\Supplier\Models\Source\Source;
use Modules\Supplier\Traits\ValidatesPublicUrl;

/**
 * Extraction Service for Supplier Automation System
 *
 * Handles extraction management, hash-based change detection, field mapping,
 * batch statistics, and integration with orchestrator workflows.
 */
class ExtractionService
{
    use ValidatesPublicUrl;

    /**
     * Extraction quality thresholds.
     */
    private const QUALITY_COMPLETE_THRESHOLD = 95;

    private const QUALITY_PARTIAL_THRESHOLD = 70;

    private const QUALITY_MINIMAL_THRESHOLD = 40;

    /**
     * Status constants for change detection.
     */
    private const STATUS_NEW = 'new';

    private const STATUS_UPDATED = 'updated';

    private const STATUS_UNCHANGED = 'existing';

    /**
     * Start a new extraction batch.
     *
     * @param  Source  $source  The source to extract from
     * @param  string  $type  Batch type: 'incremental', 'full_sync', 'manual', 'daily'
     */
    public function startExtraction(Source $source, string $type = 'incremental'): ExtractionBatch
    {
        try {
            DB::beginTransaction();

            Log::info('Starting extraction batch', [
                'source_id' => $source->id,
                'source_label' => $source->label,
                'batch_type' => $type,
                'supplier_id' => $source->supplier_id,
            ]);

            $batch = ExtractionBatch::create([
                'supplier_id' => $source->supplier_id,
                'source_id' => $source->id,
                'batch_date' => now(),
                'batch_type' => $type,
                'status' => 'pending',
                'total_items' => 0,
                'new_items' => 0,
                'updated_items' => 0,
                'unchanged_items' => 0,
                'failed_items' => 0,
            ]);

            $batch->markAsStarted();

            $source->markAsAccessed();

            event(new ExtractionBatchStarted($batch->uid, (string) $batch->supplier_id, 0));

            DB::commit();

            Log::info('Extraction batch started', [
                'batch_id' => $batch->id,
                'batch_uid' => $batch->uid,
            ]);

            return $batch;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to start extraction batch', [
                'source_id' => $source->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw ExtractionException::invalidExtractionData($e->getMessage());
        }
    }

    /**
     * Process extraction result from raw data.
     *
     * @param  array  $data  Raw extracted data
     * @param  ExtractionBatch  $batch  The batch this result belongs to
     */
    public function processExtractionResult(array $data, ExtractionBatch $batch): ExtractionResult
    {
        try {
            DB::beginTransaction();

            $source = $batch->source;

            if (! $source) {
                throw ExtractionException::invalidExtractionData('Batch has no associated source');
            }

            $extractionMapping = null;
            $isAiMode = ($source->extraction_mode ?? Source::EXTRACTION_MODE_AI) === Source::EXTRACTION_MODE_AI;

            if (! $isAiMode) {
                $mapping = $source->options()
                    ->where('option_key', 'active_mapping_id')
                    ->first();

                if (! $mapping) {
                    throw ExtractionException::mappingNotFound($source->id);
                }

                $mappingId = (int) $mapping->option_value;
                $extractionMapping = ExtractionMapping::find($mappingId);

                if (! $extractionMapping || ! $extractionMapping->is_active) {
                    throw ExtractionException::mappingNotFound($source->id);
                }
            }

            $mappedData = $extractionMapping
                ? $this->applyMapping($data, $extractionMapping)
                : $data;

            $transformedData = $this->applyTransformations($mappedData, $source);

            $reference = $transformedData['reference'] ?? $data['reference'] ?? null;
            $ean = $transformedData['ean'] ?? $data['ean'] ?? null;

            $existingResult = null;
            if ($reference) {
                $existingResult = ExtractionResult::where('supplier_id', $source->supplier_id)
                    ->where('source_id', $source->id)
                    ->where('reference', $reference)
                    ->latest()
                    ->first();
            } elseif ($ean) {
                $existingResult = ExtractionResult::where('supplier_id', $source->supplier_id)
                    ->where('source_id', $source->id)
                    ->where('ean', $ean)
                    ->latest()
                    ->first();
            }

            $changeType = $this->detectChanges($transformedData, $existingResult);

            $hash = $this->generateContentHash($transformedData);

            $missingFields = $extractionMapping
                ? $this->detectMissingFields($transformedData, $extractionMapping)
                : $this->detectMissingFieldsFromData($transformedData);
            $quality = $this->calculateExtractionQuality($transformedData, $missingFields);

            $result = ExtractionResult::create([
                'supplier_id' => $source->supplier_id,
                'source_id' => $source->id,
                'mapping_id' => $extractionMapping?->id,
                'batch_id' => $batch->uid,
                'batch_date' => $batch->batch_date,
                'reference' => $reference,
                'ean' => $ean,
                'source_url' => $data['source_url'] ?? null,
                'source_file' => $data['source_file'] ?? null,
                'extracted_data' => $transformedData,
                'raw_data' => $data,
                'extraction_quality' => $quality,
                'missing_fields' => $missingFields,
                'status' => $changeType,
                'hash' => $hash,
                'previous_hash' => $existingResult?->hash,
            ]);

            event(new ExtractionResultProcessed($result->batch_id ?? '', (string) $result->supplier_id, $result->uid, $changeType));

            DB::commit();

            Log::info('Extraction result processed', [
                'result_id' => $result->id,
                'batch_id' => $batch->id,
                'change_type' => $changeType,
                'quality' => $quality,
                'reference' => $reference,
            ]);

            return $result;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process extraction result', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Complete an extraction batch and update all metrics.
     *
     * @param  ExtractionBatch  $batch  The batch to complete
     */
    public function completeExtraction(ExtractionBatch $batch): void
    {
        try {
            DB::beginTransaction();

            $this->updateBatchMetrics($batch);

            $batch->updateSummary();

            $batch->markAsCompleted();

            event(new ExtractionBatchCompleted($batch->uid, (string) $batch->supplier_id, $batch->total_items ?? 0, $batch->failed_items ?? 0));

            DB::commit();

            Log::info('Extraction batch completed', [
                'batch_id' => $batch->id,
                'batch_uid' => $batch->uid,
                'total_items' => $batch->total_items,
                'new_items' => $batch->new_items,
                'updated_items' => $batch->updated_items,
                'unchanged_items' => $batch->unchanged_items,
                'failed_items' => $batch->failed_items,
                'success_rate' => $batch->getSuccessRate(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to complete extraction batch', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage(),
            ]);

            $batch->markAsFailed();

            throw $e;
        }
    }

    /**
     * Generate content hash from extracted data.
     *
     * Excludes volatile fields like price and stock for stable comparison.
     *
     * @param  array  $data  The extracted data
     * @return string MD5 hash of stable fields
     */
    public function generateContentHash(array $data): string
    {
        $hashableFields = [
            'product_name',
            'short_description',
            'long_description',
            'specifications',
            'features',
            'images',
            'category',
            'brand',
            'manufacturer',
        ];

        $hashData = array_intersect_key($data, array_flip($hashableFields));

        ksort($hashData);

        return md5(json_encode($hashData, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Detect changes between new data and existing result.
     *
     * @param  array  $newData  The new extracted data
     * @param  ExtractionResult|null  $existing  The existing result, if any
     * @return string Change type: 'new', 'updated', or 'unchanged'
     */
    public function detectChanges(array $newData, ?ExtractionResult $existing): string
    {
        if (! $existing) {
            return self::STATUS_NEW;
        }

        $newHash = $this->generateContentHash($newData);

        if ($newHash !== $existing->hash) {
            return self::STATUS_UPDATED;
        }

        return self::STATUS_UNCHANGED;
    }

    /**
     * Apply field mapping to raw data.
     *
     * @param  array  $rawData  The raw extracted data
     * @param  ExtractionMapping  $mapping  The mapping configuration
     * @return array Mapped data
     */
    public function applyMapping(array $rawData, ExtractionMapping $mapping): array
    {
        $mappedData = [];

        foreach ($mapping->field_mappings as $targetField => $mappingConfig) {
            if (is_string($mappingConfig)) {
                $sourceField = $mappingConfig;
                $mappedData[$targetField] = $rawData[$sourceField] ?? null;
            } elseif (is_array($mappingConfig)) {
                $sourceField = $mappingConfig['source'] ?? $mappingConfig['field'] ?? null;
                $defaultValue = $mappingConfig['default'] ?? null;

                if ($sourceField && isset($rawData[$sourceField])) {
                    $value = $rawData[$sourceField];

                    if (isset($mappingConfig['transform'])) {
                        $value = $this->applyFieldTransform($value, $mappingConfig['transform']);
                    }

                    $mappedData[$targetField] = $value;
                } else {
                    $mappedData[$targetField] = $defaultValue;
                }
            }
        }

        return $mappedData;
    }

    /**
     * Apply transformations based on source configuration.
     *
     * @param  array  $data  The data to transform
     * @param  Source  $source  The source with transformation rules
     * @return array Transformed data
     */
    public function applyTransformations(array $data, Source $source): array
    {
        $transformRules = $source->getOption('transform_rules', []);

        if (empty($transformRules)) {
            return $data;
        }

        foreach ($transformRules as $field => $rules) {
            if (! isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            foreach ($rules as $rule => $params) {
                $value = $this->applyTransformRule($value, $rule, $params);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Update batch metrics from results.
     *
     * @param  ExtractionBatch  $batch  The batch to update
     */
    public function updateBatchMetrics(ExtractionBatch $batch): void
    {
        $batch->updateMetrics();

        Log::debug('Updated batch metrics', [
            'batch_id' => $batch->id,
            'total' => $batch->total_items,
            'new' => $batch->new_items,
            'updated' => $batch->updated_items,
            'unchanged' => $batch->unchanged_items,
            'failed' => $batch->failed_items,
        ]);
    }

    /**
     * Generate comprehensive batch summary.
     *
     * @param  ExtractionBatch  $batch  The batch to summarize
     * @return array Summary statistics
     */
    public function generateBatchSummary(ExtractionBatch $batch): array
    {
        return $batch->generateSummary();
    }

    /**
     * Trigger orchestrator workflow for a source.
     *
     * @param  Source  $source  The source to process
     * @param  string  $workflowType  Type of workflow: 'extraction', 'scraping', 'ftp_sync', etc.
     */
    public function triggerOrchestratorWorkflow(Source $source, string $workflowType): ?AutomationExecution
    {
        try {
            $workflow = AutomationWorkflow::where('workflow_type', $workflowType)
                ->where('is_active', true)
                ->first();

            if (! $workflow) {
                throw ExtractionException::workflowNotFound($workflowType);
            }

            $execution = AutomationExecution::createExecution(
                workflowId: $workflow->id,
                supplierId: $source->supplier_id,
                sourceId: $source->id,
                triggerType: 'manual',
                inputPayload: [
                    'source_id' => $source->id,
                    'source_type' => $source->source_type,
                    'source_label' => $source->label,
                    'supplier_id' => $source->supplier_id,
                ],
                executionContext: [
                    'initiated_by' => 'extraction_service',
                    'timestamp' => now()->toIso8601String(),
                ],
                triggeredBy: auth()->id()
            );

            if ($workflow->webhook_url) {
                $this->sendWebhookTrigger($workflow, $execution);
            }

            $execution->markAsQueued();

            $workflow->incrementExecutions();

            event(new OrchestratorWorkflowTriggered($execution->uid ?? '', (string) ($execution->supplier_id ?? ''), $workflowType));

            Log::info('Orchestrator workflow triggered', [
                'execution_id' => $execution->id,
                'workflow_id' => $workflow->id,
                'workflow_type' => $workflowType,
                'source_id' => $source->id,
            ]);

            return $execution;

        } catch (\Exception $e) {
            Log::error('Failed to trigger orchestrator workflow', [
                'source_id' => $source->id,
                'workflow_type' => $workflowType,
                'error' => $e->getMessage(),
            ]);

            throw ExtractionException::orchestratorConnectionFailed($e->getMessage());
        }
    }

    /**
     * Handle callback from orchestrator.
     *
     * @param  array  $payload  The callback payload
     */
    public function handleOrchestratorCallback(array $payload): void
    {
        try {
            DB::beginTransaction();

            $executionId = $payload['execution_id'] ?? $payload['uid'] ?? null;

            if (! $executionId) {
                throw new \InvalidArgumentException('Missing execution_id in callback payload');
            }

            $execution = AutomationExecution::where('uid', $executionId)
                ->orWhere('external_execution_id', $executionId)
                ->first();

            if (! $execution) {
                Log::warning('Execution not found for callback', ['execution_id' => $executionId]);

                return;
            }

            $status = $payload['status'] ?? 'unknown';
            $outputData = $payload['output_data'] ?? $payload['data'] ?? [];
            $errorDetails = $payload['error_details'] ?? $payload['error'] ?? null;

            switch ($status) {
                case 'running':
                    $execution->markAsRunning($payload['external_execution_id'] ?? null);
                    break;

                case 'completed':
                case 'success':
                    $execution->update([
                        'items_processed' => $payload['items_processed'] ?? null,
                        'items_succeeded' => $payload['items_succeeded'] ?? null,
                        'items_failed' => $payload['items_failed'] ?? null,
                    ]);
                    $execution->markAsCompleted($outputData);
                    break;

                case 'failed':
                case 'error':
                    $execution->markAsFailed(is_array($errorDetails) ? $errorDetails : ['error' => $errorDetails]);
                    break;

                case 'timeout':
                    $execution->markAsTimeout(is_array($errorDetails) ? $errorDetails : ['error' => $errorDetails]);
                    break;

                case 'cancelled':
                    $execution->markAsCancelled($errorDetails);
                    break;

                default:
                    Log::warning('Unknown callback status', [
                        'execution_id' => $execution->id,
                        'status' => $status,
                    ]);
            }

            DB::commit();

            Log::info('Orchestrator callback processed', [
                'execution_id' => $execution->id,
                'status' => $status,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to process orchestrator callback', [
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Apply field-level transformation.
     *
     * @param  mixed  $value  The value to transform
     * @param  string  $transform  The transformation type
     * @return mixed Transformed value
     */
    private function applyFieldTransform($value, string $transform)
    {
        return match ($transform) {
            'trim' => is_string($value) ? trim($value) : $value,
            'lowercase' => is_string($value) ? strtolower($value) : $value,
            'uppercase' => is_string($value) ? strtoupper($value) : $value,
            'strip_html' => is_string($value) ? strip_tags($value) : $value,
            'parse_float' => is_numeric($value) ? (float) $value : $value,
            'parse_int' => is_numeric($value) ? (int) $value : $value,
            'json_decode' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    /**
     * Apply transformation rule to a value.
     *
     * @param  mixed  $value  The value to transform
     * @param  string  $rule  The rule name
     * @param  mixed  $params  The rule parameters
     * @return mixed Transformed value
     */
    private function applyTransformRule($value, string $rule, $params)
    {
        return match ($rule) {
            'replace' => is_string($value) ? str_replace($params['search'], $params['replace'], $value) : $value,
            'regex_replace' => is_string($value) ? preg_replace($params['pattern'], $params['replacement'], $value) : $value,
            'append' => $value.$params,
            'prepend' => $params.$value,
            'multiply' => is_numeric($value) ? $value * $params : $value,
            'divide' => is_numeric($value) && $params != 0 ? $value / $params : $value,
            'round' => is_numeric($value) ? round($value, $params) : $value,
            default => $value,
        };
    }

    /**
     * Detect missing required fields.
     *
     * @param  array  $data  The extracted data
     * @param  ExtractionMapping  $mapping  The mapping configuration
     * @return array List of missing field names
     */
    private function detectMissingFields(array $data, ExtractionMapping $mapping): array
    {
        $requiredFields = $mapping->validation_rules['required'] ?? [];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Detect missing fields for AI-extracted data (no mapping required).
     *
     * @param  array  $data  The extracted data
     * @return array Missing field names
     */
    private function detectMissingFieldsFromData(array $data): array
    {
        $requiredFields = ['name', 'price'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (! isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                $missingFields[] = $field;
            }
        }

        return $missingFields;
    }

    /**
     * Calculate extraction quality based on data completeness.
     *
     * @param  array  $data  The extracted data
     * @param  array  $missingFields  List of missing fields
     * @return string Quality level: 'complete', 'partial', 'minimal', 'failed'
     */
    private function calculateExtractionQuality(array $data, array $missingFields): string
    {
        $totalFields = count($data);
        $extractedFields = $totalFields - count($missingFields);

        if ($totalFields === 0) {
            return 'failed';
        }

        $completeness = ($extractedFields / $totalFields) * 100;

        return match (true) {
            $completeness >= self::QUALITY_COMPLETE_THRESHOLD => 'complete',
            $completeness >= self::QUALITY_PARTIAL_THRESHOLD => 'partial',
            $completeness >= self::QUALITY_MINIMAL_THRESHOLD => 'minimal',
            default => 'failed',
        };
    }

    /**
     * Send webhook trigger to orchestrator.
     *
     * @param  AutomationWorkflow  $workflow  The workflow
     * @param  AutomationExecution  $execution  The execution
     */
    private function sendWebhookTrigger(AutomationWorkflow $workflow, AutomationExecution $execution): void
    {
        $callbackUrl = $workflow->callback_url ?? route('api.supplier.orchestrator.callback');

        try {
            $this->assertUrlIsPublic($workflow->webhook_url);
            $this->assertUrlIsPublic($callbackUrl);
        } catch (\InvalidArgumentException $e) {
            Log::error('Refused to send webhook to non-public endpoint', [
                'execution_id' => $execution->id,
                'webhook_url' => $workflow->webhook_url,
                'reason' => $e->getMessage(),
            ]);

            throw new \Exception('Webhook endpoint is not allowed: '.$e->getMessage());
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'X-Execution-ID' => $execution->uid,
                    'X-Workflow-ID' => $workflow->uid,
                    'Content-Type' => 'application/json',
                ])
                ->post($workflow->webhook_url, [
                    'execution_id' => $execution->uid,
                    'workflow_id' => $workflow->uid,
                    'workflow_type' => $workflow->workflow_type,
                    'payload' => $execution->input_payload,
                    'context' => $execution->execution_context,
                    'callback_url' => $callbackUrl,
                ]);

            if ($response->successful()) {
                $execution->update([
                    'external_execution_id' => $response->json('execution_id') ?? $response->json('id'),
                ]);

                Log::info('Webhook sent successfully', [
                    'execution_id' => $execution->id,
                    'webhook_url' => $workflow->webhook_url,
                    'response_status' => $response->status(),
                ]);
            } else {
                Log::error('Webhook returned error', [
                    'execution_id' => $execution->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new \Exception("Webhook failed with status {$response->status()}");
            }

        } catch (\Exception $e) {
            Log::error('Failed to send webhook', [
                'execution_id' => $execution->id,
                'webhook_url' => $workflow->webhook_url,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
