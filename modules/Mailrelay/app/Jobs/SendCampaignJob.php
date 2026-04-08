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
 * Job para enviar campaigns en segundo plano
 * Procesa el envío de campaigns de forma asíncrona
 */
class SendCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Numero maximo de reintentos
     */
    public int $tries = 3;

    /**
     * Timeout del job (30 minutos para campaigns grandes)
     */
    public int $timeout = 1800;

    /**
     * Backoff entre reintentos (2min, 5min, 10min)
     *
     * @var array<int>
     */
    public array $backoff = [120, 300, 600];

    /**
     * Campaign a enviar
     */
    protected Campaign $campaign;

    /**
     * Lista de destinatarios
     */
    protected array $recipients;

    /**
     * Constructor
     */
    public function __construct(Campaign $campaign, array $recipients)
    {
        $this->campaign = $campaign;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     */
    public function handle(
        ProviderManager $providerManager,
        CampaignRendererService $rendererService
    ): void {
        Log::info('Iniciando envío de campaign en background', [
            'campaign_id' => $this->campaign->id,
            'recipients_count' => count($this->recipients),
            'attempt' => $this->attempts(),
        ]);

        try {
            // Obtener provider
            $provider = $providerManager->byId($this->campaign->mail_provider_id);

            // Renderizar HTML base
            $baseHtml = $rendererService->render($this->campaign);

            // Dividir en batches para evitar timeouts
            $batchSize = $provider->getRateLimits()['batch_size'] ?? 100;
            $batches = array_chunk($this->recipients, $batchSize);

            $totalSent = 0;
            $errors = [];

            foreach ($batches as $batchIndex => $batch) {
                Log::info("Procesando batch {$batchIndex}", [
                    'campaign_id' => $this->campaign->id,
                    'batch_size' => count($batch),
                ]);

                // Preparar destinatarios
                $preparedRecipients = array_map(function ($recipient) use ($rendererService) {
                    return [
                        'email' => $recipient['email'],
                        'name' => $recipient['name'] ?? '',
                        'html' => $rendererService->renderForSubscriber($this->campaign, $recipient),
                        'variables' => $recipient['variables'] ?? [],
                    ];
                }, $batch);

                // Enviar batch
                $result = $provider->sendBulk(
                    $preparedRecipients,
                    $this->campaign->subject,
                    $baseHtml,
                    [
                        'track_opens' => $this->campaign->track_opens,
                        'track_clicks' => $this->campaign->track_clicks,
                    ]
                );

                if ($result['success']) {
                    $totalSent += $result['sent_count'];
                } else {
                    $errors[] = $result['error'] ?? 'Unknown error in batch';
                }

                // Respetar rate limits entre batches
                if ($batchIndex < count($batches) - 1) {
                    $delay = $provider->getRateLimits()['delay_seconds'] ?? 1;
                    sleep($delay);
                }
            }

            // Actualizar campaign con resultados
            $this->campaign->update([
                'recipient_count' => $totalSent,
            ]);

            if ($totalSent > 0) {
                $this->campaign->markAsSent();

                Log::info('Campaign enviada exitosamente en background', [
                    'campaign_id' => $this->campaign->id,
                    'total_sent' => $totalSent,
                    'errors_count' => count($errors),
                ]);
            } else {
                $this->campaign->markAsFailed();

                Log::error('Campaign falló completamente', [
                    'campaign_id' => $this->campaign->id,
                    'errors' => $errors,
                ]);
            }
        } catch (\Exception $e) {
            // No llamar markAsFailed() aqui — el metodo failed() lo maneja tras agotar reintentos.
            // Si aun quedan intentos, re-lanzar para que Laravel reintente.
            Log::error('Error en SendCampaignJob (reintentando)', [
                'campaign_id' => $this->campaign->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->campaign->markAsFailed();

        Log::error('SendCampaignJob falló definitivamente', [
            'campaign_id' => $this->campaign->id,
            'recipients_count' => count($this->recipients),
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
        ]);
    }
}
