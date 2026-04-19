<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Services\CampaignRendererService;
use Modules\Mailrelay\Services\ProviderManager;

/**
 * Orquesta el envío de una campaign dividiéndola en batches.
 *
 * En lugar de usar sleep() entre batches (lo que bloquea el worker),
 * despacha SendCampaignBatchJob con delay incremental por batch.
 * Esto libera el worker inmediatamente y respeta los rate limits del provider.
 *
 * AVISO: queue.connections.redis.retry_after está configurado en 90 s.
 * SendCampaignBatchJob tiene timeout = 300 s. Si un batch tarda más de 90 s,
 * Redis lo considerará perdido y lo re-encolará aunque aún esté corriendo.
 * Aumenta retry_after a al menos 360 s en config/queue.php para ese worker.
 */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** Timeout generoso: solo renderiza HTML y despacha jobs (no hace I/O pesado). */
    public int $timeout = 120;

    /** @var array<int> */
    public array $backoff = [120, 300, 600];

    public function __construct(
        protected Campaign $campaign,
        protected array $recipients
    ) {}

    public function handle(
        ProviderManager $providerManager,
        CampaignRendererService $rendererService
    ): void {
        Log::info('SendCampaignJob: orquestando envío de campaign', [
            'campaign_id' => $this->campaign->id,
            'recipients_count' => count($this->recipients),
            'attempt' => $this->attempts(),
        ]);

        $provider = $providerManager->byId($this->campaign->mail_provider_id);
        $rateLimits = $provider->getRateLimits();

        $batchSize = $rateLimits['batch_size'] ?? 100;
        $delaySeconds = $rateLimits['delay_seconds'] ?? 1;

        $baseHtml = $rendererService->render($this->campaign);
        $batches = array_chunk($this->recipients, $batchSize);
        $totalBatches = count($batches);

        $this->campaign->markAsSending();

        foreach ($batches as $batchIndex => $batch) {
            $offset = $batchIndex * $delaySeconds;

            SendCampaignBatchJob::dispatch(
                $this->campaign,
                $batch,
                $batchIndex,
                $baseHtml,
            )->delay(now()->addSeconds($offset));
        }

        Log::info('SendCampaignJob: batches despachados con delay', [
            'campaign_id' => $this->campaign->id,
            'total_batches' => $totalBatches,
            'delay_per_batch_seconds' => $delaySeconds,
            'estimated_duration_seconds' => ($totalBatches - 1) * $delaySeconds,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        $this->campaign->markAsFailed();

        Log::error('SendCampaignJob: orquestación falló definitivamente', [
            'campaign_id' => $this->campaign->id,
            'recipients_count' => count($this->recipients),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
