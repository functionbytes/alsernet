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

class TranslateAiContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public int $backoff = 15;

    public function __construct(
        public string $contentUid,
        public string $targetLang,
    ) {
        $this->onQueue(config('supplier.queues.ai_generation', 'ai-generation'));
    }

    public function handle(ContentGenerationService $service): void
    {
        $content = AiContent::where('uid', $this->contentUid)->first();

        if (! $content) {
            Log::warning('TranslateAiContentJob: content not found', ['uid' => $this->contentUid]);

            return;
        }

        $result = $service->translateContent($content, $this->targetLang);

        if (! $result['success']) {
            Log::error('TranslateAiContentJob failed', [
                'uid' => $this->contentUid,
                'lang' => $this->targetLang,
                'error' => $result['message'] ?? 'unknown',
            ]);

            throw new \RuntimeException($result['message'] ?? 'Translation failed');
        }
    }
}
