<?php

namespace Modules\HelpdeskEmailLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskEmailLog\Contracts\EmailProviderWebhookAdapter;
use Modules\HelpdeskEmailLog\Models\ProviderWebhookEvent;
use Modules\HelpdeskEmailLog\Services\EmailBounceCorrelatorService;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\MailgunWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\MailrelayWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\PostmarkWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhooks\SesSnsWebhookAdapter;
use Modules\HelpdeskEmailLog\Services\ProviderWebhookSettingsRepository;
use Modules\HelpdeskEmailLog\Support\ParsedEmailEvent;

/**
 * Único punto de entrada para CUALQUIER proveedor — no conoce el formato de
 * ningún payload, solo resuelve el adapter correcto, verifica y delega en
 * EmailBounceCorrelatorService (la misma pieza que ya usa el poller IMAP de
 * la Fase 2). Solo el proveedor SELECCIONADO en Settings responde aquí; una
 * URL para cualquier otro devuelve 404, para no exponer lógica de
 * verificación de proveedores no usados.
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

    public function receive(string $provider, Request $request, ProviderWebhookSettingsRepository $settingsRepo, EmailBounceCorrelatorService $correlator): Response|JsonResponse
    {
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
            // Los proveedores reintentan la entrega del webhook si no reciben
            // 2xx a tiempo (o simplemente por su propia política de "al
            // menos una vez") — sin este guard, un mismo bounce/complaint se
            // volvía a correlacionar y podía suprimir/marcar el mismo envío
            // más de una vez.
            if ($event->providerEventId !== null && $event->providerEventId !== ''
                && ! ProviderWebhookEvent::markSeenIfNew($provider, $event->providerEventId)) {
                $skipped++;

                continue;
            }

            if (! $this->shouldProcess($event, $config)) {
                $skipped++;

                continue;
            }

            if ($this->correlate($event, $correlator)) {
                $processed++;
            } else {
                $skipped++;
            }
        }

        return response()->json(['processed' => $processed, 'skipped' => $skipped]);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function shouldProcess(ParsedEmailEvent $event, array $config): bool
    {
        if ($event->isBounce()) {
            return (bool) $config['process_bounces'];
        }

        if ($event->isComplaint()) {
            return (bool) $config['process_complaints'];
        }

        return false;
    }

    /**
     * Correlación por Message-ID primero (alta confianza); si no vino o no
     * hubo match, cae a correlación por destinatario sin acotar por módulo
     * (el webhook de un proveedor cubre TODO lo que ese proveedor envía,
     * cruce de módulos — no tiene el concepto de "buzón por módulo" que sí
     * tienen los BounceMailboxes IMAP).
     */
    private function correlate(ParsedEmailEvent $event, EmailBounceCorrelatorService $correlator): bool
    {
        $isComplaint = $event->isComplaint();

        if ($event->messageId !== null && $event->messageId !== '') {
            if ($correlator->correlateByMessageId($event->messageId, $event->reason, $event->isHard, $isComplaint)) {
                return true;
            }
        }

        if ($event->recipient !== null && $event->recipient !== '') {
            return $correlator->correlateByRecipient($event->recipient, $event->reason, null, $event->isHard, $isComplaint);
        }

        return false;
    }
}
