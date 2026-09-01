<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailLog\Models\ProviderWebhookEvent;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookEventProcessor;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\MailgunWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\MailrelayWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\PostmarkWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\SesSnsWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;
use Modules\HelpdeskEmailLog\Support\WebhookPayloadRedactor;

/**
 * Único punto de entrada para CUALQUIER proveedor — no conoce el formato de
 * ningún payload, solo resuelve el adapter correcto, verifica y delega en
 * ProviderWebhookEventProcessor (qué correlador usar según el tipo de
 * evento). Solo el proveedor SELECCIONADO en Settings responde aquí; una URL
 * para cualquier otro devuelve 404, para no exponer lógica de verificación
 * de proveedores no usados.
 *
 * Cada evento verificado se persiste en email_provider_events ANTES de
 * intentar correlacionar (ver ProviderWebhookEvent::recordIfNew()): payload
 * crudo (redactado/acotado, ver WebhookPayloadRedactor) + a qué EmailLog
 * correlacionó (o null). Es lo que permite depurar por qué un evento no se
 * reflejó bien y reprocesarlo después de corregir el problema (ver
 * Settings\WebhookEventsController::reprocess()).
 *
 * AVISO IMPORTANTE (ver settings/index.blade.php): elegir un proveedor aquí
 * solo determina cómo se INTERPRETAN los webhooks entrantes — nunca cambia
 * por dónde sale el correo real. Mientras `config('mail.default')` siga
 * siendo smtp genérico, ningún proveedor de estos va a mandar ningún webhook
 * porque nunca procesa correo nuestro.
 */
class EmailProviderWebhookController extends Controller
{
    /**
     * @return array<string, EmailProviderWebhookAdapter>
     */
    private function adapters(): array
    {
        return [
            'mailrelay' => new MailrelayWebhookAdapter,
            'ses' => new SesSnsWebhookAdapter,
            'postmark' => new PostmarkWebhookAdapter,
            'mailgun' => new MailgunWebhookAdapter,
        ];
    }

    public function receive(
        string $provider,
        Request $request,
        ProviderWebhookSettingsRepository $settingsRepo,
        ProviderWebhookEventProcessor $processor,
    ): Response|JsonResponse {
        $adapters = $this->adapters();

        if (! isset($adapters[$provider])) {
            abort(404);
        }

        $config = $settingsRepo->get();

        // Solo el proveedor activo en Settings procesa webhooks — evita que
        // una URL de un proveedor que nunca se llegó a activar quede
        // aceptando payloads sin control.
        if ($config['provider'] !== $provider) {
            abort(404);
        }

        $adapter = $adapters[$provider];

        if (! $adapter->verify($request, (string) $config['secret'])) {
            Log::warning('helpdeskemaillog: webhook de proveedor con firma/token inválido', ['provider' => $provider]);

            return response()->json(['error' => 'invalid signature'], 401);
        }

        $controlResponse = $adapter->handleControlMessage($request);

        if ($controlResponse !== null) {
            return $controlResponse;
        }

        $events = $adapter->parse($request);
        $processed = 0;
        $skipped = 0;

        foreach ($events as $event) {
            // Registra el evento ANTES de intentar correlacionar: si ya se
            // había visto (el proveedor reintentó la entrega porque no
            // recibió 2xx a tiempo), no vuelve a insertar ni a correlacionar
            // — solo cuenta el reintento (duplicate_count) y se salta. Si es
            // la primera vez, queda un registro con el payload que se
            // completa más abajo con email_log_id/processed_at.
            $eventRow = ProviderWebhookEvent::recordIfNew($provider, $event->providerEventId, $event->type, $this->buildStoredPayload($event));

            if ($eventRow === null) {
                $skipped++;

                continue;
            }

            if (! $processor->shouldProcess($event, $config)) {
                $skipped++;

                continue;
            }

            $matchedLog = $processor->correlate($event);

            $eventRow->update(['email_log_id' => $matchedLog?->id, 'processed_at' => now()]);

            if ($matchedLog !== null) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        return response()->json(['processed' => $processed, 'skipped' => $skipped]);
    }

    /**
     * Payload guardado en email_provider_events.payload — el crudo tal como
     * llegó (ver ParsedEmailEvent::$rawPayload, ya acotado a este evento
     * concreto, nunca al request completo) junto a lo que el adapter ya
     * extrajo. La mitad "parsed" es lo que permite reprocesar el evento sin
     * volver a pedírselo al proveedor (ver
     * Settings\WebhookEventsController::reprocess()); la mitad "raw" es lo
     * que permite ver qué mandó el proveedor de verdad cuando el bug está en
     * el propio parse(). Redactado/acotado en tamaño antes de devolverse
     * (ver WebhookPayloadRedactor).
     *
     * @return array<string, mixed>
     */
    private function buildStoredPayload(ParsedEmailEvent $event): array
    {
        return WebhookPayloadRedactor::redact([
            'raw' => $event->rawPayload,
            'parsed' => [
                'message_id' => $event->messageId,
                'recipient' => $event->recipient,
                'is_hard' => $event->isHard,
                'reason' => $event->reason,
                'ip' => $event->ip,
                'user_agent' => $event->userAgent,
            ],
        ]);
    }
}
