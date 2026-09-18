<?php

namespace Modules\HelpdeskEmailActivity\Services;

use Modules\HelpdeskEmailActivity\Enums\EmailOpenSource;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Modules\HelpdeskEmailActivity\Models\EmailLogOpen;

/**
 * Hermano de EmailBounceCorrelatorService para los dos eventos de proveedor
 * que NO mueven a EmailLog a un status terminal negativo: 'delivered'
 * (confirma email_logs.delivered_at, ver
 * 2026_09_01_040000_add_delivered_at_to_email_logs_table) y 'open' (añade una
 * fila a email_log_opens con source = EmailOpenSource::Provider, ver
 * 2026_09_01_050000_add_source_to_email_log_opens_table).
 *
 * Se separa de EmailBounceCorrelatorService en vez de extenderlo porque su
 * interfaz actual (isHard/reason/isComplaint, mark() -> markAsBounced()/
 * markAsComplained()) no encaja aquí: 'delivered' no tiene motivo ni
 * severidad, y 'open' no muta un status sino que añade una fila (puede haber
 * varias, una por apertura, igual que ya hace el píxel propio).
 *
 * Misma regla de correlación que el hermano de rebotes: Message-ID primero
 * (alta confianza); si no vino o no hubo match, destinatario dentro de una
 * ventana de 7 días SOLO si hay un único candidato ambiguo-libre entre los
 * envíos ya marcados 'sent'.
 *
 * DECISIÓN (papelera): igual que EmailBounceCorrelatorService, todas las
 * búsquedas usan withTrashed() — un registro en la papelera (recuperable
 * durante 30 días, ver EmailLog::class) sigue siendo el mismo envío real;
 * un webhook de entrega/apertura que llega mientras tanto no debe perderse
 * solo porque un agente lo ocultó de la vista principal. El borrado GDPR es
 * definitivo (forceDelete, ver EmailLogComplianceHandler) y por tanto nunca
 * deja una fila viva que este servicio pueda encontrar.
 */
class EmailDeliveryEventCorrelatorService
{
    public function correlateDeliveredByMessageId(string $messageId): bool
    {
        return $this->resolveDeliveredByMessageId($messageId) !== null;
    }

    public function correlateDeliveredByRecipient(string $recipient): bool
    {
        return $this->resolveDeliveredByRecipient($recipient) !== null;
    }

    public function correlateOpenByMessageId(string $messageId, ?string $ip, ?string $userAgent): bool
    {
        return $this->resolveOpenByMessageId($messageId, $ip, $userAgent) !== null;
    }

    public function correlateOpenByRecipient(string $recipient, ?string $ip, ?string $userAgent): bool
    {
        return $this->resolveOpenByRecipient($recipient, $ip, $userAgent) !== null;
    }

    /**
     * Igual que correlateDeliveredByMessageId() pero además devuelve el
     * EmailLog correlacionado (o null) — lo usa ProviderWebhookEventProcessor
     * para persistir email_log_id en el registro de auditoría del webhook
     * (ver email_provider_events).
     */
    public function resolveDeliveredByMessageId(string $messageId): ?EmailLog
    {
        $emailLog = EmailLog::withTrashed()->where('message_id', $messageId)->first();

        if (! $emailLog) {
            return null;
        }

        $this->markDelivered($emailLog);

        return $emailLog;
    }

    public function resolveDeliveredByRecipient(string $recipient): ?EmailLog
    {
        $emailLog = $this->findSingleCandidateByRecipient($recipient);

        if (! $emailLog) {
            return null;
        }

        $this->markDelivered($emailLog);

        return $emailLog;
    }

    public function resolveOpenByMessageId(string $messageId, ?string $ip, ?string $userAgent): ?EmailLog
    {
        $emailLog = EmailLog::withTrashed()->where('message_id', $messageId)->first();

        if (! $emailLog) {
            return null;
        }

        $this->recordOpen($emailLog, $ip, $userAgent);

        return $emailLog;
    }

    public function resolveOpenByRecipient(string $recipient, ?string $ip, ?string $userAgent): ?EmailLog
    {
        $emailLog = $this->findSingleCandidateByRecipient($recipient);

        if (! $emailLog) {
            return null;
        }

        $this->recordOpen($emailLog, $ip, $userAgent);

        return $emailLog;
    }

    /**
     * No pisa un delivered_at ya presente (de un evento 'delivered' anterior
     * ya procesado, o de un reintento del mismo webhook que no traía
     * providerEventId para deduplicarse antes de llegar aquí).
     */
    private function markDelivered(EmailLog $emailLog): void
    {
        if ($emailLog->delivered_at !== null) {
            return;
        }

        $emailLog->update(['delivered_at' => now()]);
    }

    /**
     * Mismos campos que ya registra el pixel propio
     * (EmailOpenTrackingController::pixel()), distinguidos solo por
     * `source` — ambas fuentes conviven, ninguna sustituye a la otra.
     */
    private function recordOpen(EmailLog $emailLog, ?string $ip, ?string $userAgent): void
    {
        EmailLogOpen::create([
            'email_log_id' => $emailLog->id,
            'source' => EmailOpenSource::Provider,
            'ip' => $ip,
            'user_agent' => $userAgent !== null ? substr($userAgent, 0, 512) : null,
            'opened_at' => now(),
        ]);
    }

    /**
     * Mismo criterio que EmailBounceCorrelatorService::correlateByRecipient():
     * con más de un candidato no se puede saber cuál de los envíos es el que
     * de verdad corresponde al evento — se deja sin correlacionar antes que
     * arriesgar un falso positivo.
     */
    private function findSingleCandidateByRecipient(string $recipient): ?EmailLog
    {
        $candidates = EmailLog::withTrashed()
            ->sent()
            ->whereJsonContains('to_addresses', $recipient)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }
}
