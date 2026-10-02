<?php

namespace Modules\HelpdeskCampaigns\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Events\CustomerGdprDeleted;
use Modules\Helpdesk\Models\CustomerSession;
use Modules\HelpdeskCampaigns\Models\CampaignImpression;

/**
 * Borra el dato personal de las impresiones de campaña cuando un cliente
 * ejerce su derecho de supresión.
 *
 * Las filas se conservan (los contadores y el CTR dependen de ellas); solo se
 * vacían la IP, la sesión y el metadata, que pueden identificar a la persona.
 *
 * Obligación legal: no se gatea con el toggle del módulo ni con el de
 * seguimiento de impresiones. Se ejecuta síncrono, como el resto de
 * listeners de CustomerGdprDeleted.
 */
class AnonymizeCampaignImpressions
{
    public function handle(CustomerGdprDeleted $event): void
    {
        $customerId = $event->customer->id ?? null;

        if ($customerId === null) {
            return;
        }

        $sessionIds = CustomerSession::query()
            ->where('customer_id', $customerId)
            ->pluck('session_id')
            ->filter()
            ->all();

        $anonymized = CampaignImpression::query()
            ->where(function ($query) use ($customerId, $sessionIds): void {
                $query->where('customer_id', $customerId);

                if ($sessionIds !== []) {
                    $query->orWhereIn('customer_session_id', $sessionIds);
                }
            })
            ->update([
                'ip_address' => null,
                'customer_session_id' => null,
                'metadata' => null,
            ]);

        if ($anonymized > 0) {
            Log::info('[HelpdeskCampaigns] Impresiones anonimizadas por GDPR', [
                'count' => $anonymized,
            ]);
        }
    }
}
