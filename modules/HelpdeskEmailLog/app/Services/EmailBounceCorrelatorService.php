<?php

namespace Modules\HelpdeskEmailLog\Services;

use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Models\EmailLog;

/**
 * Único punto que sabe "cómo encontrar y marcar un EmailLog" a partir de un
 * evento de rebote/queja ya identificado (Message-ID o destinatario) — sin
 * importar de dónde vino el evento. Compartido por dos orígenes distintos:
 *
 *  - BounceProcessorService (poller IMAP, hoy — el único mecanismo real,
 *    porque el proyecto envía por SMTP genérico sin proveedor con webhooks).
 *  - El futuro receptor de webhooks de proveedor (SES/Postmark/Mailgun), si
 *    algún día se conecta uno real.
 *
 * Extraído de lo que antes vivía privado dentro de
 * Modules\Document\Services\DocumentBounceProcessorService, generalizado
 * para no acotar por módulo salvo que el caller lo pida explícitamente.
 *
 * DECISIÓN (papelera, ver EmailLog::class/SoftDeletes): ambos métodos
 * consultan con withTrashed(). Un registro movido a la papelera desde el
 * panel sigue siendo recuperable durante 30 días (no está "borrado de
 * verdad" hasta la purga automática) — si en ese margen llega un DSN de
 * rebote real para ese envío, es evidencia legítima que debe reflejarse en
 * el registro tal cual, igual que si nunca se hubiera enviado a la
 * papelera. Ignorarlo solo porque un agente lo ocultó de la vista
 * principal degradaría la auditoría sin ningún beneficio de privacidad
 * (el borrado GDPR, que sí debe ser ciego a esto, es definitivo — usa
 * forceDelete(), ver EmailLogComplianceHandler — así que nunca llega vivo
 * hasta aquí).
 */
class EmailBounceCorrelatorService
{
    /**
     * Correlación exacta por Message-ID — la de mayor confianza, se intenta
     * siempre primero.
     */
    public function correlateByMessageId(string $messageId, string $reason, bool $isHard, bool $isComplaint = false): bool
    {
        $emailLog = EmailLog::withTrashed()->where('message_id', $messageId)->first();

        if (! $emailLog) {
            return false;
        }

        $this->mark($emailLog, $reason, $isHard, $isComplaint);

        return true;
    }

    /**
     * Fallback cuando no hay Message-ID: correlaciona por destinatario
     * fallido dentro de una ventana de 7 días, SOLO si hay exactamente un
     * candidato ambiguo-libre (con más de uno, no se puede saber cuál de los
     * envíos rebotó exactamente — se deja sin correlacionar antes que
     * arriesgar un falso positivo, mismo criterio que
     * LogEmailSent::findQueued() para queued→sent).
     *
     * @param  list<string>|null  $moduleScope  null = sin filtrar por módulo
     *                                          (buzón de rebotes compartido
     *                                          por todo el sistema)
     */
    public function correlateByRecipient(string $recipient, string $subject, ?array $moduleScope, bool $isHard, bool $isComplaint = false): bool
    {
        $candidates = EmailLog::withTrashed()
            ->when($moduleScope, fn ($q) => $q->whereIn('module', $moduleScope))
            ->sent()
            ->whereJsonContains('to_addresses', $recipient)
            ->where('created_at', '>=', now()->subDays(7))
            ->get();

        if ($candidates->count() !== 1) {
            return false;
        }

        $this->mark(
            $candidates->first(),
            '[correlación por destinatario, sin Message-ID en el origen] '.$subject,
            $isHard,
            $isComplaint,
        );

        return true;
    }

    private function mark(EmailLog $emailLog, string $reason, bool $isHard, bool $isComplaint): void
    {
        // No degradar un estado ya terminal más específico si el mismo
        // evento llega dos veces (el caller ya marca "Seen"/deduplica, esto
        // es una segunda capa de protección barata).
        if (in_array($emailLog->status, [EmailStatus::Bounced, EmailStatus::Complained], true)) {
            return;
        }

        if ($isComplaint) {
            $emailLog->markAsComplained($reason);

            return;
        }

        $emailLog->markAsBounced($reason, $isHard);
    }
}
