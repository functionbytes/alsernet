<?php

namespace Modules\Mailrelay\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mailrelay\Entities\Campaign;
use Modules\Mailrelay\Enums\CampaignStatus;
use Modules\Mailrelay\Jobs\SendCampaignJob;

/**
 * Servicio principal para gestión de campaigns
 * Orquesta el envío de campaigns usando providers y templates
 */
class CampaignService
{
    protected ProviderManager $providerManager;

    protected CampaignRendererService $rendererService;

    public function __construct(
        ProviderManager $providerManager,
        CampaignRendererService $rendererService
    ) {
        $this->providerManager = $providerManager;
        $this->rendererService = $rendererService;
    }

    /**
     * Crear nueva campaign
     *
     * @param  array  $data  Datos de la campaign
     */
    public function create(array $data): Campaign
    {
        return DB::transaction(function () use ($data) {
            // Crear campaign
            $campaign = Campaign::create([
                'name' => $data['name'],
                'subject' => $data['subject'],
                'html_content' => $data['html_content'] ?? null,
                'text_content' => $data['text_content'] ?? null,
                'mailer_template_id' => $data['mailer_template_id'] ?? null,
                'lang_id' => $data['lang_id'] ?? 1,
                'mail_provider_id' => $data['mail_provider_id'] ?? null,
                'template_variables' => $data['template_variables'] ?? [],
                'track_opens' => $data['track_opens'] ?? true,
                'track_clicks' => $data['track_clicks'] ?? true,
                'status' => CampaignStatus::DRAFT,
                'metadata' => $data['metadata'] ?? [],
            ]);

            Log::info('Campaign creada', [
                'campaign_id' => $campaign->id,
                'name' => $campaign->name,
            ]);

            return $campaign;
        });
    }

    /**
     * Actualizar campaign
     */
    public function update(Campaign $campaign, array $data): Campaign
    {
        // Solo permitir editar si está en draft o failed
        if (! in_array($campaign->status, [CampaignStatus::DRAFT, CampaignStatus::FAILED])) {
            throw new \Exception('Solo se pueden editar campaigns en estado DRAFT o FAILED');
        }

        return DB::transaction(function () use ($campaign, $data) {
            $campaign->update([
                'name' => $data['name'] ?? $campaign->name,
                'subject' => $data['subject'] ?? $campaign->subject,
                'html_content' => $data['html_content'] ?? $campaign->html_content,
                'text_content' => $data['text_content'] ?? $campaign->text_content,
                'mailer_template_id' => $data['mailer_template_id'] ?? $campaign->mailer_template_id,
                'lang_id' => $data['lang_id'] ?? $campaign->lang_id,
                'mail_provider_id' => $data['mail_provider_id'] ?? $campaign->mail_provider_id,
                'template_variables' => $data['template_variables'] ?? $campaign->template_variables,
                'track_opens' => $data['track_opens'] ?? $campaign->track_opens,
                'track_clicks' => $data['track_clicks'] ?? $campaign->track_clicks,
                'metadata' => array_merge($campaign->metadata ?? [], $data['metadata'] ?? []),
            ]);

            Log::info('Campaign actualizada', [
                'campaign_id' => $campaign->id,
                'name' => $campaign->name,
            ]);

            return $campaign->fresh();
        });
    }

    /**
     * Programar envío de campaign
     */
    public function schedule(Campaign $campaign, \DateTimeInterface $scheduledAt): Campaign
    {
        // Validar que el campaign está listo
        $validation = $this->rendererService->validate($campaign);
        if (! $validation['valid']) {
            throw new \Exception('Campaign no válida: '.implode(', ', $validation['errors']));
        }

        DB::transaction(function () use ($campaign, $scheduledAt) {
            $campaign->schedule($scheduledAt);

            Log::info('Campaign programada', [
                'campaign_id' => $campaign->id,
                'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            ]);
        });

        return $campaign->fresh();
    }

    /**
     * Enviar campaign inmediatamente
     *
     * @param  array  $recipients  Lista de destinatarios
     * @return array Resultado del envío
     */
    public function send(Campaign $campaign, array $recipients): array
    {
        // Validar que el campaign está listo
        $validation = $this->rendererService->validate($campaign);
        if (! $validation['valid']) {
            throw new \Exception('Campaign no válida: '.implode(', ', $validation['errors']));
        }

        if (empty($recipients)) {
            throw new \Exception('Debe proporcionar al menos un destinatario');
        }

        return DB::transaction(function () use ($campaign, $recipients) {
            // Marcar campaign como enviando
            $campaign->markAsSending();

            try {
                // Obtener provider
                $provider = $this->providerManager->byId($campaign->mail_provider_id);

                // Renderizar HTML base
                $baseHtml = $this->rendererService->render($campaign);

                // Preparar destinatarios para el provider
                $preparedRecipients = array_map(function ($recipient) use ($campaign) {
                    // Renderizar HTML personalizado para cada destinatario
                    $personalizedHtml = $this->rendererService->renderForSubscriber($campaign, $recipient);

                    return [
                        'email' => $recipient['email'],
                        'name' => $recipient['name'] ?? '',
                        'html' => $personalizedHtml,
                        'variables' => $recipient['variables'] ?? [],
                    ];
                }, $recipients);

                // Enviar via provider (bulk)
                $result = $provider->sendBulk(
                    $preparedRecipients,
                    $campaign->subject,
                    $baseHtml,
                    [
                        'track_opens' => $campaign->track_opens,
                        'track_clicks' => $campaign->track_clicks,
                    ]
                );

                if ($result['success']) {
                    // Actualizar campaign con resultados
                    $campaign->update([
                        'provider_campaign_id' => $result['campaign_id'],
                        'recipient_count' => $result['sent_count'],
                    ]);

                    $campaign->markAsSent();

                    Log::info('Campaign enviada exitosamente', [
                        'campaign_id' => $campaign->id,
                        'provider' => $provider->getName(),
                        'recipients_sent' => $result['sent_count'],
                    ]);

                    return [
                        'success' => true,
                        'campaign_id' => $campaign->id,
                        'sent_count' => $result['sent_count'],
                        'provider' => $provider->getName(),
                        'message' => "Campaign enviada a {$result['sent_count']} destinatarios",
                    ];
                }

                // Si falló el envío
                $campaign->markAsFailed();

                Log::error('Error enviando campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'campaign_id' => $campaign->id,
                    'error' => $result['error'] ?? 'Error desconocido',
                ];
            } catch (\Exception $e) {
                $campaign->markAsFailed();

                Log::error('Excepción enviando campaign', [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e;
            }
        });
    }

    /**
     * Enviar campaign en segundo plano (queue)
     */
    public function sendAsync(Campaign $campaign, array $recipients): void
    {
        // Validar
        $validation = $this->rendererService->validate($campaign);
        if (! $validation['valid']) {
            throw new \Exception('Campaign no válida: '.implode(', ', $validation['errors']));
        }

        // Marcar como enviando
        $campaign->markAsSending();

        // Dispatch job a queue
        SendCampaignJob::dispatch($campaign, $recipients)
            ->onQueue('campaigns');

        Log::info('Campaign encolada para envío', [
            'campaign_id' => $campaign->id,
            'recipients_count' => count($recipients),
        ]);
    }

    /**
     * Enviar email de prueba
     */
    public function sendTest(Campaign $campaign, string $testEmail): array
    {
        try {
            // Renderizar preview
            $html = $this->rendererService->renderPreview($campaign, $testEmail);

            // Obtener provider
            $provider = $this->providerManager->byId($campaign->mail_provider_id);

            // Enviar email de prueba
            $result = $provider->send(
                $testEmail,
                '[TEST] '.$campaign->subject,
                $html,
                [
                    'track_opens' => false,
                    'track_clicks' => false,
                ]
            );

            if ($result['success']) {
                Log::info('Email de prueba enviado', [
                    'campaign_id' => $campaign->id,
                    'test_email' => $testEmail,
                ]);

                return [
                    'success' => true,
                    'message' => "Email de prueba enviado a {$testEmail}",
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? 'Error desconocido',
            ];
        } catch (\Exception $e) {
            Log::error('Error enviando email de prueba', [
                'campaign_id' => $campaign->id,
                'test_email' => $testEmail,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener preview HTML de la campaign
     *
     * @param  string  $subscriberEmail  Email de ejemplo
     */
    public function getPreview(Campaign $campaign, string $subscriberEmail = 'preview@example.com'): string
    {
        return $this->rendererService->renderPreview($campaign, $subscriberEmail);
    }

    /**
     * Obtener estadísticas de la campaign desde el provider
     */
    public function getStats(Campaign $campaign): array
    {
        if (! $campaign->provider_campaign_id) {
            return [
                'error' => 'Campaign no ha sido enviada aún',
            ];
        }

        try {
            $provider = $this->providerManager->byId($campaign->mail_provider_id);

            return $provider->getStats($campaign->provider_campaign_id);
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Duplicar campaign
     */
    public function duplicate(Campaign $campaign, ?string $newName = null): Campaign
    {
        return DB::transaction(function () use ($campaign, $newName) {
            $newCampaign = $campaign->replicate();
            $newCampaign->name = $newName ?? $campaign->name.' (Copia)';
            $newCampaign->status = CampaignStatus::DRAFT;
            $newCampaign->sent_at = null;
            $newCampaign->scheduled_at = null;
            $newCampaign->provider_campaign_id = null;
            $newCampaign->recipient_count = 0;
            $newCampaign->save();

            Log::info('Campaign duplicada', [
                'original_id' => $campaign->id,
                'new_id' => $newCampaign->id,
            ]);

            return $newCampaign;
        });
    }

    /**
     * Eliminar campaign
     */
    public function delete(Campaign $campaign): bool
    {
        // Solo permitir eliminar si está en draft o failed
        if (! in_array($campaign->status, [CampaignStatus::DRAFT, CampaignStatus::FAILED])) {
            throw new \Exception('Solo se pueden eliminar campaigns en estado DRAFT o FAILED');
        }

        Log::info('Campaign eliminada', [
            'campaign_id' => $campaign->id,
            'name' => $campaign->name,
        ]);

        return $campaign->delete();
    }
}
