<?php

namespace Modules\Helpdesk\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Campaigns\Broadcast;
use Modules\Helpdesk\Models\Campaigns\BroadcastRecipient;

/**
 * Dispatcher de una campaña (broadcast): trocea los destinatarios pendientes en
 * SendBroadcastChunkJob (lotes de CHUNK_SIZE) para que campañas grandes no
 * revienten el timeout de un único job serial y para tener reintento parcial por
 * lote. El envío real y la finalización viven en el chunk job.
 */
class SendBroadcastJob implements ShouldQueue
{
    use Queueable;

    private const CHUNK_SIZE = 100;

    public int $tries = 1;

    public int $timeout = 60;

    public int $backoff = 30;

    public function __construct(
        private readonly Broadcast $broadcast
    ) {
        $this->onQueue('broadcasts');
    }

    public function handle(): void
    {
        $this->broadcast->markAsSending();

        $recipientIds = BroadcastRecipient::query()
            ->where('broadcast_id', $this->broadcast->id)
            ->where('status', 'pending')
            ->pluck('id');

        if ($recipientIds->isEmpty()) {
            $this->broadcast->update([
                'status' => 'sent',
                'sent_at' => now(),
                'recipients_count' => 0,
                'delivered_count' => 0,
                'failed_count' => 0,
            ]);

            return;
        }

        foreach ($recipientIds->chunk(self::CHUNK_SIZE) as $chunk) {
            SendBroadcastChunkJob::dispatch($this->broadcast->id, $chunk->values()->all());
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendBroadcastJob: job failed entirely', [
            'broadcast_id' => $this->broadcast->id,
            'error' => $exception->getMessage(),
        ]);

        $this->broadcast->markAsFailed();
    }
}
