<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Events\AiContentGenerated;
use Modules\Supplier\Models\Ai\AiAuditLog;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Models\Product\Product;
use Modules\Supplier\Models\Prompt\Prompt;
use Modules\Supplier\Services\ProductChatService;

class GenerateBulkProductContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public int $productId,
        public string $promptUid,
        public string $model,
        public bool $webSearch,
        public ?int $userId = null,
        public ?string $batchId = null,
    ) {}

    public function handle(ProductChatService $chatService): void
    {
        $product = Product::with(['supplier', 'category', 'attributes'])->find($this->productId);
        $prompt = Prompt::where('uid', $this->promptUid)->first();

        if (! $product || ! $prompt) {
            Log::warning('Bulk generation skipped: product or prompt not found', [
                'product_id' => $this->productId,
                'prompt_uid' => $this->promptUid,
            ]);

            return;
        }

        try {
            $variables = $chatService->buildProductVariables($product);
            $userMessage = $prompt->render($variables);
            $systemContext = $chatService->buildProductContext($product);

            $messages = [
                ['role' => 'system', 'content' => $systemContext],
                ['role' => 'user', 'content' => $userMessage],
            ];

            $response = $chatService->chat($messages, $this->model, $this->webSearch);

            $content = AiContent::create([
                'supplier_id' => $product->supplier_id,
                'supplier_product_id' => $product->id,
                'erp_reference' => $product->code,
                'status' => AiContent::STATUS_PENDING_VALIDATION,
                'prompt_id' => $prompt->id,
                'long_description' => $response['content'],
                'source_attributes' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_code' => $product->code,
                    'source' => 'bulk',
                    'batch_id' => $this->batchId,
                ],
                'generation_metadata' => [
                    'source' => 'bulk',
                    'requested_by_user_id' => $this->userId,
                    'model' => $response['model'] ?? $this->model,
                    'cost' => $response['cost'] ?? 0,
                    'tokens' => $response['tokens']['total'] ?? 0,
                    'batch_id' => $this->batchId,
                    'generated_at' => now()->toIso8601String(),
                ],
            ]);

            // Log who manually requested this generation
            if ($this->userId) {
                $content->log('generation_requested', null, AiContent::STATUS_PENDING_VALIDATION, [
                    'source' => 'bulk_manual',
                    'batch_id' => $this->batchId,
                    'prompt_uid' => $this->promptUid,
                    'model' => $this->model,
                ], $this->userId);
            }

            event(new AiContentGenerated($content, $this->batchId));

            AiAuditLog::create([
                'user_id' => $this->userId,
                'action' => 'content.bulk_generate',
                'resource_type' => 'supplier_product',
                'resource_id' => (string) $product->id,
                'model' => $response['model'] ?? $this->model,
                'cost' => $response['cost'] ?? 0,
                'tokens' => $response['tokens']['total'] ?? 0,
                'metadata' => ['batch_id' => $this->batchId, 'prompt_uid' => $this->promptUid],
            ]);
        } catch (\Throwable $e) {
            Log::error('Bulk content generation failed', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a permanent job failure.
     *
     * Marks any partial AiContent left in pending validation for this product
     * as failed so it does not linger in the review queue.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk content generation job failed permanently', [
            'product_id' => $this->productId,
            'prompt_uid' => $this->promptUid,
            'batch_id' => $this->batchId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);

        if (! $this->batchId) {
            return;
        }

        try {
            $partial = AiContent::where('supplier_product_id', $this->productId)
                ->where('status', AiContent::STATUS_PENDING_VALIDATION)
                ->where('generation_metadata->batch_id', $this->batchId)
                ->latest('id')
                ->first();

            $partial?->markAsFailed(
                "Bulk generation job failed after {$this->attempts()} attempts: {$exception->getMessage()}"
            );
        } catch (\Throwable $e) {
            Log::error('Failed to mark partial content as failed on bulk job failure', [
                'product_id' => $this->productId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
