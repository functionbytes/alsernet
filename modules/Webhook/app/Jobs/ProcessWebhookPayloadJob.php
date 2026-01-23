<?php

namespace Modules\Webhook\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Supplier\Entities\SupplierExtractionResult;
use Modules\Supplier\Entities\SupplierSource;
use Modules\Supplier\Entities\SupplierSourceWebhook;

/**
 * Process Webhook Payload Job
 *
 * Processes incoming webhook payloads from supplier sources.
 * Validates signatures, maps payload data, creates/updates extraction results,
 * and dispatches AI content generation jobs for new inventaries.
 */
class ProcessWebhookPayloadJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * The webhook configuration instance.
     */
    protected ?SupplierSourceWebhook $webhook = null;

    /**
     * Processing statistics.
     */
    protected array $stats = [
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'errors' => 0,
        'skipped' => 0,
    ];

    /**
     * Create a new job instance.
     *
     * @param  array  $webhookData  The webhook payload data
     * @param  int  $sourceId  The supplier source ID
     * @param  string|null  $signature  HMAC signature for validation
     */
    public function __construct(
        protected array $webhookData,
        protected int $sourceId,
        protected ?string $signature = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Load webhook configuration
            $this->loadWebhookConfiguration();

            if (! $this->webhook) {
                throw new \Exception("No active webhook configuration found for source ID: {$this->sourceId}");
            }

            // Validate webhook signature
            if (! $this->validateSignature()) {
                throw new \Exception('Webhook signature validation failed');
            }

            // Process the webhook payload
            $this->processWebhookPayload();

            // Record webhook reception
            $this->webhook->recordReceived();

            // Log processing stats
            $this->logProcessingStats();
        } catch (\Exception $e) {
            Log::error('Error processing webhook payload', [
                'source_id' => $this->sourceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Load the webhook configuration for the source.
     */
    protected function loadWebhookConfiguration(): void
    {
        $source = SupplierSource::with('supplier')->find($this->sourceId);

        if (! $source) {
            throw new \Exception("Supplier source not found: {$this->sourceId}");
        }

        // Find active webhook configuration for this source
        $this->webhook = SupplierSourceWebhook::query()
            ->where('source_id', $this->sourceId)
            ->enabled()
            ->first();
    }

    /**
     * Validate the webhook signature using HMAC-SHA256.
     */
    protected function validateSignature(): bool
    {
        // If no signature is provided or webhook doesn't require validation
        if (! $this->signature || ! $this->webhook->secret_key) {
            Log::warning('Webhook signature validation skipped', [
                'source_id' => $this->sourceId,
                'has_signature' => ! empty($this->signature),
                'has_secret_key' => ! empty($this->webhook->secret_key),
            ]);

            return true;
        }

        // Validate signature
        $payload = json_encode($this->webhookData);
        $isValid = $this->webhook->validateSignature($this->signature, $payload);

        if (! $isValid) {
            Log::error('Invalid webhook signature', [
                'source_id' => $this->sourceId,
                'provided_signature' => $this->signature,
            ]);
        }

        return $isValid;
    }

    /**
     * Process the webhook payload and create/update extraction results.
     */
    protected function processWebhookPayload(): void
    {
        DB::beginTransaction();

        try {
            // Map payload to internal format
            $mappedData = $this->mapPayloadToInternalFormat();

            // Determine if this is a batch of items or single item
            $items = $this->extractItems($mappedData);

            foreach ($items as $itemData) {
                try {
                    $this->processItem($itemData);
                    $this->stats['processed']++;
                } catch (\Exception $e) {
                    $this->stats['errors']++;
                    Log::warning('Error processing webhook item', [
                        'item_data' => $itemData,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Map webhook payload to internal format using webhook configuration.
     */
    protected function mapPayloadToInternalFormat(): array
    {
        if (! $this->webhook->payload_mapping || empty($this->webhook->payload_mapping)) {
            // No mapping configured, return raw data
            return $this->webhookData;
        }

        return $this->webhook->mapPayload($this->webhookData);
    }

    /**
     * Extract individual items from mapped data.
     * Handles both single items and batches.
     */
    protected function extractItems(array $mappedData): array
    {
        // Check if data contains an items/inventaries array
        if (isset($mappedData['items']) && is_array($mappedData['items'])) {
            return $mappedData['items'];
        }

        if (isset($mappedData['inventaries']) && is_array($mappedData['inventaries'])) {
            return $mappedData['inventaries'];
        }

        // Assume single item if no batch structure found
        return [$mappedData];
    }

    /**
     * Process a single item from the webhook payload.
     */
    protected function processItem(array $itemData): void
    {
        // Extract key identifiers
        $reference = $itemData['reference'] ?? $itemData['product_reference'] ?? null;
        $ean = $itemData['ean'] ?? $itemData['ean13'] ?? null;

        if (! $reference && ! $ean) {
            $this->stats['skipped']++;
            Log::warning('Webhook item missing both reference and EAN, skipping', [
                'item_data' => $itemData,
            ]);

            return;
        }

        // Find existing extraction result
        $existingResult = $this->findExistingResult($reference, $ean);

        if ($existingResult) {
            $this->updateExistingResult($existingResult, $itemData);
        } else {
            $this->createNewResult($itemData, $reference, $ean);
        }
    }

    /**
     * Find existing extraction result by reference or EAN.
     */
    protected function findExistingResult(?string $reference, ?string $ean): ?SupplierExtractionResult
    {
        $query = SupplierExtractionResult::query()
            ->where('source_id', $this->sourceId);

        if ($reference) {
            $query->where('reference', $reference);
        } elseif ($ean) {
            $query->where('ean', $ean);
        }

        return $query->first();
    }

    /**
     * Update existing extraction result.
     */
    protected function updateExistingResult(SupplierExtractionResult $result, array $itemData): void
    {
        // Store previous hash
        $previousHash = $result->hash;

        // Prepare update data
        $updateData = [
            'extracted_data' => $itemData,
            'raw_data' => $this->webhookData,
            'previous_hash' => $previousHash,
        ];

        // Calculate new hash
        $newHash = SupplierExtractionResult::generateContentHash($itemData);
        $updateData['hash'] = $newHash;

        // Determine if content changed
        if ($previousHash !== $newHash) {
            $updateData['status'] = 'updated';
            $this->stats['updated']++;

            Log::info('Webhook updated product', [
                'source_id' => $this->sourceId,
                'reference' => $result->reference,
                'ean' => $result->ean,
            ]);

            // Dispatch AI content generation for updated inventaries
            $this->dispatchAiContentGeneration($result->id);
        }

        // Evaluate extraction quality
        $updateData['extraction_quality'] = $this->evaluateExtractionQuality($itemData);
        $updateData['missing_fields'] = $this->identifyMissingFields($itemData);

        $result->update($updateData);
    }

    /**
     * Create new extraction result.
     */
    protected function createNewResult(array $itemData, ?string $reference, ?string $ean): void
    {
        // Get mapping for quality evaluation
        $mapping = $this->webhook->source->configurations()->active()->first();

        // Generate content hash
        $hash = SupplierExtractionResult::generateContentHash($itemData);

        // Prepare result data
        $resultData = [
            'uid' => Str::ulid(),
            'supplier_id' => $this->webhook->source->supplier_id,
            'source_id' => $this->sourceId,
            'mapping_id' => $mapping?->id,
            'batch_id' => $this->generateBatchId(),
            'batch_date' => now(),
            'reference' => $reference,
            'ean' => $ean,
            'source_url' => $itemData['source_url'] ?? null,
            'extracted_data' => $itemData,
            'raw_data' => $this->webhookData,
            'extraction_quality' => $this->evaluateExtractionQuality($itemData),
            'missing_fields' => $this->identifyMissingFields($itemData),
            'status' => 'new',
            'hash' => $hash,
        ];

        $result = SupplierExtractionResult::create($resultData);

        $this->stats['created']++;

        Log::info('Webhook created new product', [
            'source_id' => $this->sourceId,
            'reference' => $reference,
            'ean' => $ean,
            'result_id' => $result->id,
        ]);

        // Dispatch AI content generation for new inventaries
        $this->dispatchAiContentGeneration($result->id);
    }

    /**
     * Evaluate extraction quality based on completeness of required fields.
     */
    protected function evaluateExtractionQuality(array $extractedData): string
    {
        $requiredFields = [
            'product_name',
            'reference',
            'price',
            'short_description',
        ];

        $optionalFields = [
            'long_description',
            'specifications',
            'features',
            'images',
            'stock',
            'category',
        ];

        $presentRequired = count(array_filter($requiredFields, fn ($field) => ! empty($extractedData[$field])));
        $presentOptional = count(array_filter($optionalFields, fn ($field) => ! empty($extractedData[$field])));

        $requiredPercentage = ($presentRequired / count($requiredFields)) * 100;
        $totalPercentage = (($presentRequired + $presentOptional) / (count($requiredFields) + count($optionalFields))) * 100;

        if ($requiredPercentage < 50) {
            return 'failed';
        }

        if ($totalPercentage >= 90) {
            return 'complete';
        }

        if ($totalPercentage >= 60) {
            return 'partial';
        }

        return 'minimal';
    }

    /**
     * Identify missing required and optional fields.
     */
    protected function identifyMissingFields(array $extractedData): array
    {
        $allExpectedFields = [
            'product_name',
            'reference',
            'ean',
            'price',
            'short_description',
            'long_description',
            'specifications',
            'features',
            'images',
            'stock',
            'category',
            'brand',
            'manufacturer',
        ];

        return array_values(array_filter($allExpectedFields, fn ($field) => empty($extractedData[$field])));
    }

    /**
     * Generate a batch ID for grouping webhook deliveries.
     */
    protected function generateBatchId(): string
    {
        return 'webhook_'.now()->format('Ymd_His').'_'.Str::random(8);
    }

    /**
     * Dispatch AI content generation job for the result.
     */
    protected function dispatchAiContentGeneration(int $resultId): void
    {
        // Check if GenerateAiContentJob exists
        if (! class_exists(\Modules\Supplier\Jobs\GenerateAiContentJob::class)) {
            Log::warning('GenerateAiContentJob not found, skipping AI content generation', [
                'result_id' => $resultId,
            ]);

            return;
        }

        try {
            \Modules\Supplier\Jobs\GenerateAiContentJob::dispatch($resultId)
                ->onQueue('ai-content');

            Log::info('Dispatched AI content generation', [
                'result_id' => $resultId,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch AI content generation', [
                'result_id' => $resultId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Log webhook processing statistics.
     */
    protected function logProcessingStats(): void
    {
        Log::info('Webhook payload processed', [
            'source_id' => $this->sourceId,
            'webhook_id' => $this->webhook->id,
            'stats' => $this->stats,
        ]);
    }
}
