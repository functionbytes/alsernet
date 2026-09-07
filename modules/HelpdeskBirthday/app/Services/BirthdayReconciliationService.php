<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRedemption;

/**
 * El descuadre entre la tienda y gestión.
 *
 * Un bono que PrestaShop descontó pero el ERP nunca registró sigue vivo en
 * gestión: el cliente ya se llevó el dinero y el bono se puede volver a gastar.
 * Sobre la tienda real esto no es un caso raro — de los 1.116 cheques de
 * cumpleaños canjeados, 381 no constan en gestión, y la tabla `marcarbono`
 * dejó de escribirse por completo el 28 de mayo de 2025.
 *
 * MARCAR ES ESCRIBIR EN EL ERP, Y NO ES REVERSIBLE
 * ------------------------------------------------
 * Por eso esto no vive en el scheduler: lo lanza una persona, sobre una
 * selección concreta. Un bono de hace un año puede estar ya caducado, o
 * consumido por otra vía, o pertenecer a un pedido que acabó devuelto, y
 * marcarlo en bloque sin mirar sería peor que el descuadre.
 *
 * Y sin código no hay nada que marcar: la `cart_rule` se borra al consumirse y
 * en 363 canjes el código no sobrevive en ninguna parte. Esos se pueden ver,
 * pero no cuadrar desde aquí.
 */
class BirthdayReconciliationService
{
    public function __construct(
        private readonly BirthdayCouponService $coupons,
    ) {}

    /**
     * Los canjes sin registrar en gestión.
     *
     * @return Builder<BirthdayRedemption>
     */
    public function pending(?BirthdayCampaign $campaign = null)
    {
        return BirthdayRedemption::query()
            ->unreconciled()
            ->when($campaign !== null, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->orderByDesc('ordered_at');
    }

    /**
     * El resumen del agujero: cuántos, cuánto dinero y cuántos se pueden cerrar.
     *
     * @return array<string, mixed>
     */
    public function summary(?BirthdayCampaign $campaign = null): array
    {
        $row = $this->pending($campaign)
            ->reorder()
            ->selectRaw('COUNT(*) as pending')
            ->selectRaw('COALESCE(SUM(discount), 0) as amount')
            ->selectRaw('SUM(CASE WHEN coupon_code IS NOT NULL THEN 1 ELSE 0 END) as markable')
            ->selectRaw('MIN(ordered_at) as oldest')
            ->selectRaw('MAX(ordered_at) as newest')
            ->first();

        return [
            'pending' => (int) $row->pending,
            'amount' => round((float) $row->amount, 2),
            'markable' => (int) $row->markable,
            // Sin código no hay bono que marcar: se ven, pero no se cierran.
            'unmarkable' => (int) $row->pending - (int) $row->markable,
            'oldest' => $row->oldest,
            'newest' => $row->newest,
        ];
    }

    /**
     * Marca en gestión los canjes indicados.
     *
     * Va uno a uno y en serie a propósito: cada llamada escribe en el ERP y su
     * respuesta importa —gestión rechaza el consumo de un bono caducado, ya
     * usado o con un importe menor que el mínimo— así que el motivo se guarda
     * en la fila para poder mirarlo después.
     *
     * @param  array<int, int>  $ids
     * @return array{ok: int, failed: int, skipped: int}
     */
    public function markInErp(array $ids, ?BirthdayCampaign $campaign = null): array
    {
        $result = ['ok' => 0, 'failed' => 0, 'skipped' => 0];

        $rows = BirthdayRedemption::query()
            ->whereIn('id', $ids)
            ->unreconciled()
            // Acotado a la campaña desde la que se pulsó: sin esto, un POST a
            // mano podría consumir en gestión el bono de cualquier otra.
            ->when($campaign !== null, fn ($q) => $q->where('campaign_id', $campaign->id))
            ->get();

        foreach ($rows as $row) {
            if (! $row->canBeMarkedInErp()) {
                $result['skipped']++;

                continue;
            }

            // El importe que se envía es el del pedido, igual que hace
            // PrestaShop al canjear: gestión valida la compra mínima con él.
            $response = $this->coupons->markAsUsed((string) $row->coupon_code, (float) $row->order_total);

            $row->forceFill([
                'erp_marked' => $response['ok'],
                'erp_response' => mb_substr($response['message'], 0, 1000),
                'erp_marked_at' => $response['ok'] ? now() : null,
                'erp_sale_amount' => $row->order_total,
            ])->save();

            $result[$response['ok'] ? 'ok' : 'failed']++;
        }

        Log::info('[HelpdeskBirthday] Marcado de bonos en gestión', $result + [
            'campaign_id' => $campaign?->id,
            'requested' => count($ids),
        ]);

        return $result;
    }
}
