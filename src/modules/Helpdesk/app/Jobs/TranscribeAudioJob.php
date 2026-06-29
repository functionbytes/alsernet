<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\ConversationMessageCreated;
use Modules\Helpdesk\Models\ConversationItem;
use Modules\Helpdesk\Services\AI\AudioTranscriptionService;

class TranscribeAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public array $backoff = [30, 60];

    public function __construct(
        public readonly int $itemId,
        public readonly string $absolutePath,
    ) {
        $this->onQueue('helpdesk-webhooks');
    }

    public function handle(AudioTranscriptionService $service): void
    {
        $item = ConversationItem::query()->find($this->itemId);

        if (! $item) {
            return;
        }

        $transcription = $service->transcribe($this->absolutePath);

        if (empty($transcription)) {
            return;
        }

        $metadata = is_array($item->metadata) ? $item->metadata : [];
        $metadata['transcription'] = $transcription;
        $item->metadata = $metadata;
        $item->save();

        // Re-broadcast so the open thread shows the transcription
        broadcast(new ConversationMessageCreated($item, false));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('TranscribeAudioJob failed', [
            'item_id' => $this->itemId,
            'path' => $this->absolutePath,
            'error' => $exception->getMessage(),
        ]);
    }
}
