<?php

namespace Modules\HelpdeskEmailLog\Observers;

use Modules\HelpdeskEmailLog\Enums\EmailStatus;
use Modules\HelpdeskEmailLog\Enums\SuppressionReason;
use Modules\HelpdeskEmailLog\Models\EmailLog;
use Modules\HelpdeskEmailLog\Services\EmailSuppressionService;

/**
 * Auto-alimenta la lista de supresión desde transiciones REALES de estado
 * de EmailLog, sin importar qué servicio las causó (BounceProcessorService
 * hoy; un futuro webhook de proveedor mañana — ambos pasan por
 * EmailLog::markAsBounced()/markAsComplained(), así que engancharse aquí
 * cubre los dos sin acoplarse a ninguno).
 *
 * Hard bounce y queja SIEMPRE suprimen en GLOBAL (module=null): afectan al
 * buzón/destinatario, no al módulo que envió — reintentar desde otro módulo
 * solo sigue dañando la reputación del dominio sin proteger nada. Soft
 * bounce NUNCA auto-suprime (es transitorio por definición — buzón lleno,
 * greylisting; suprimir aquí crearía falsos positivos permanentes sobre
 * direcciones válidas).
 */
class EmailLogObserver
{
    public function __construct(private readonly EmailSuppressionService $suppressions) {}

    public function updated(EmailLog $emailLog): void
    {
        if (! $emailLog->wasChanged('status')) {
            return;
        }

        $recipient = $emailLog->to_addresses[0] ?? null;

        if (! $recipient) {
            return;
        }

        if ($emailLog->status === EmailStatus::Complained) {
            $this->suppressions->suppress(
                $recipient,
                SuppressionReason::Complaint,
                module: null,
                emailLog: $emailLog,
                automatic: true,
            );

            return;
        }

        if ($emailLog->status === EmailStatus::Bounced && $emailLog->bounceType() === 'hard') {
            $this->suppressions->suppress(
                $recipient,
                SuppressionReason::HardBounce,
                module: null,
                emailLog: $emailLog,
                automatic: true,
            );
        }
    }
}
