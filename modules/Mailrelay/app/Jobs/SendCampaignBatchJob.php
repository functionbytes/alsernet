<?php

namespace Modules\Mailrelay\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Services\CampaignRendererService;
use Modules\Mailrelay\Services\ProviderManager;

/**
 * Envía un único batch de destinatarios para una campaign.
 *
 * Diseñado para ser despachado con delay por SendCampaignJob,
 * reemplazando el sleep() bloqueante del loop original.
 */
class SendCampaignBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * 5 minutos por batch. Asegúrate de que queue.connections.redis.retry_after
     * sea mayor a este valor (actualmente es 90 s — ver nota en SendCampaignJob).
     */
    public int $timeout = 300;

    /** @var array<int> */
    public array $backoff = [60, 120, 300];

    /**
     * @param  array<int, array<string, mixed>>  $recipients  Destinatarios del batch
     * @param  int  $batchIndex  Índice del batch (para logging)
     * @param  string  $baseHtml  HTML base ya renderizado
     */
    public function __construct(
        private readonly Campaign $campaign,
        private readonly array $recipients,
        private readonly int $batchIndex,
        private readonly string $baseHtml,
    ) {
        $this->onQueue('mailrelay-campaigns');
    }

    public function handle(
        ProviderManager $providerManager,
        CampaignRendererService $rendererService
    ): void {
        Log::info('SendCampaignBatchJob: iniciando batch', [
            'campaign_id' => $this->campaign->id,
            'batch_index' => $this->batchIndex,
            'batch_size' => count($this->recipients),
            'attempt' => $this->attempts(),
        ]);

        $provider = $providerManager->byId($this->campaign->mail_provider_id);

        $preparedRecipients = array_map(
            fn (array $recipient): array => [
                'email' => $recipient['email'],
                'name' => $recipient['name'] ?? '',
                'html' => $rendererService->renderForSubscriber($this->campaign, $recipient),
                'variables' => $recipient['variables'] ?? [],
            ],
            $this->recipients
        );

        $result = $provider->sendBulk(
            $preparedRecipients,
            $this->campaign->subject,
            $this->baseHtml,
            [
                'track_opens' => $this->campaign->track_opens,
                'track_clicks' => $this->campaign->track_clicks,
            ]
        );

        if ($result['success']) {
            Log::info('SendCampaignBatchJob: batch enviado', [
                'campaign_id' => $this->campaign->id,
                'batch_index' => $this->batchIndex,
                'sent_count' => $result['sent_count'],
            ]);

            return;
        }

        $error = $result['error'] ?? 'Unknown error';

        Log::error('SendCampaignBatchJob: batch falló', [
            'campaign_id' => $this->campaign->id,
            'batch_index' => $this->batchIndex,
            'error' => $error,
        ]);

        throw new \RuntimeException("Batch {$this->batchIndex} failed: {$error}");
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendCampaignBatchJob: falló definitivamente tras reintentos', [
            'campaign_id' => $this->campaign->id,
            'batch_index' => $this->batchIndex,
            'batch_size' => count($this->recipients),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
