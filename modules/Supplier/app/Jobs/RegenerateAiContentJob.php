<?php

namespace Modules\Supplier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Supplier\Models\Ai\AiContent;
use Modules\Supplier\Services\ContentGenerationService;

class RegenerateAiContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(
        public string $contentUid,
        public ?int $requestedByUserId = null,
    ) {
        $this->onQueue(config('supplier.queues.ai_generation', 'ai-generation'));
    }

    public function handle(ContentGenerationService $service): void
    {
        $content = AiContent::where('uid', $this->contentUid)
            ->with(['supplierProduct', 'prompt'])
            ->first();

        if (! $content) {
            Log::warning('RegenerateAiContentJob: content not found', ['uid' => $this->contentUid]);

            return;
        }

        try {
            $service->regenerateContent($content, null, $this->requestedByUserId);
        } catch (\Throwable $e) {
            Log::error('RegenerateAiContentJob failed', [
                'uid' => $this->contentUid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
