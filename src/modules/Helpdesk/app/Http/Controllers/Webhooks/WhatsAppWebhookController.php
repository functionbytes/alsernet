<?php

namespace Modules\Helpdesk\Http\Controllers\Webhooks;

use Illuminate\Http\JsonResponse;
use Modules\Helpdesk\Http\Requests\Webhooks\WhatsAppWebhookRequest;
use Modules\Helpdesk\Jobs\ProcessSocialWebhookJob;
use Modules\Helpdesk\Services\WhatsAppBusinessService;

class WhatsAppWebhookController extends MetaWebhookController
{
    public function __construct(
        private readonly WhatsAppBusinessService $service
    ) {}

    protected function verifyChallenge(string $mode, string $challenge, string $token): string|false
    {
        return $this->service->verifyWebhook($mode, $challenge, $token);
    }

    protected function channelLabel(): string
    {
        return 'WhatsApp';
    }

    /**
     * Handle incoming WhatsApp message events (POST).
     */
    public function handle(WhatsAppWebhookRequest $request): JsonResponse
    {
        return $this->safelyHandle(function () use ($request): void {
            $events = $this->service->parseWebhookPayload($request->all());

            foreach ($events as $event) {
                if (($event['type'] ?? null) === 'message') {
                    ProcessSocialWebhookJob::dispatch('whatsapp', 'message', $event);

                    continue;
                }

                // Reacción del cliente (emoji) a un mensaje del agente.
                if (($event['type'] ?? null) === 'reaction') {
                    ProcessSocialWebhookJob::dispatch('whatsapp', 'reaction', $event);

                    continue;
                }

                // Recibos de estado: 'read'/'delivered' actualizan los recibos de
                // lectura/entrega del hilo. 'sent'/'failed' no tienen reflejo en la UI.
                if (($event['type'] ?? null) === 'status') {
                    $receiptType = match ($event['status'] ?? null) {
                        'read' => 'read',
                        'delivered' => 'delivery',
                        default => null,
                    };

                    if ($receiptType !== null) {
                        ProcessSocialWebhookJob::dispatch('whatsapp', $receiptType, $event);
                    }
                }
            }
        });
    }
}
