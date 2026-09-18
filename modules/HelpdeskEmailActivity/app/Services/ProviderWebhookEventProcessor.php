<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Support\ParsedEmailEvent;

/**
 * Qué hacer con un ParsedEmailEvent ya verificado: si el tipo de evento está
 * habilitado en la configuración (shouldProcess) y, si lo está, a qué
 * EmailLog correlaciona (correlate). Extraído de
 * EmailProviderWebhookController porque WebhookEventsController::reprocess()
 * necesita exactamente la misma decisión sobre un evento ya guardado (no un
 * webhook en caliente) — antes de esto vivía solo dentro del controlador del
 * webhook, duplicarla ahí y aquí habría sido el mismo despacho por status a
 * mantener dos veces.
 */
class ProviderWebhookEventProcessor
{
    public function __construct(
        private readonly EmailBounceCorrelatorService $bounceCorrelator,
        private readonly EmailDeliveryEventCorrelatorService $deliveryCorrelator,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public function shouldProcess(ParsedEmailEvent $event, array $config): bool
    {
        return match (true) {
            $event->isBounce() => (bool) $config['process_bounces'],
            $event->isComplaint() => (bool) $config['process_complaints'],
            $event->isDelivered() => (bool) $config['process_deliveries'],
            $event->isOpen() => (bool) $config['process_opens'],
            default => false,
        };
    }

    /**
     * Despacha al correlador correcto según el tipo de evento y devuelve el
     * EmailLog correlacionado, o null si no se pudo correlacionar con
     * ninguno (sin Message-ID reconocible ni un único destinatario
     * candidato).
     */
    public function correlate(ParsedEmailEvent $event): ?EmailLog
    {
        if ($event->isDelivered()) {
            return $this->correlateDelivered($event);
        }

        if ($event->isOpen()) {
            return $this->correlateOpen($event);
        }

        return $this->correlateBounceOrComplaint($event);
    }

    /**
     * Correlación por Message-ID primero (alta confianza); si no vino o no
     * hubo match, cae a correlación por destinatario sin acotar por módulo
     * (el webhook de un proveedor cubre TODO lo que ese proveedor envía).
     */
    private function correlateBounceOrComplaint(ParsedEmailEvent $event): ?EmailLog
    {
        $isComplaint = $event->isComplaint();

        if ($event->messageId !== null && $event->messageId !== '') {
            $emailLog = $this->bounceCorrelator->resolveByMessageId($event->messageId, $event->reason, $event->isHard, $isComplaint);

            if ($emailLog !== null) {
                return $emailLog;
            }
        }

        if ($event->recipient !== null && $event->recipient !== '') {
            return $this->bounceCorrelator->resolveByRecipient($event->recipient, $event->reason, null, $event->isHard, $isComplaint);
        }

        return null;
    }

    private function correlateDelivered(ParsedEmailEvent $event): ?EmailLog
    {
        if ($event->messageId !== null && $event->messageId !== '') {
            $emailLog = $this->deliveryCorrelator->resolveDeliveredByMessageId($event->messageId);

            if ($emailLog !== null) {
                return $emailLog;
            }
        }

        if ($event->recipient !== null && $event->recipient !== '') {
            return $this->deliveryCorrelator->resolveDeliveredByRecipient($event->recipient);
        }

        return null;
    }

    private function correlateOpen(ParsedEmailEvent $event): ?EmailLog
    {
        if ($event->messageId !== null && $event->messageId !== '') {
            $emailLog = $this->deliveryCorrelator->resolveOpenByMessageId($event->messageId, $event->ip, $event->userAgent);

            if ($emailLog !== null) {
                return $emailLog;
            }
        }

        if ($event->recipient !== null && $event->recipient !== '') {
            return $this->deliveryCorrelator->resolveOpenByRecipient($event->recipient, $event->ip, $event->userAgent);
        }

        return null;
    }
}
