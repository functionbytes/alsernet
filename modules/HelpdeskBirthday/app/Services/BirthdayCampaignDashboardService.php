<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Modules\HelpdeskBirthday\Models\BirthdayRedemption;

/**
 * Los números de UNA campaña, para su cuadro de mando.
 *
 * Separado de BirthdayDashboardService, que agrega un periodo entero: son dos
 * preguntas distintas —«¿cómo va el mes?» y «¿cómo fue este día?»— y mezclarlas
 * obligaba a que cada método supiera si tenía una campaña o treinta.
 *
 * El dinero sale de la copia local de canjes (helpdesk_birthday_redemptions) y
 * NO de PrestaShop en vivo: así la pantalla abre igual de rápido con la tienda
 * caída, y sobre todo conserva lo que la tienda borra —la `cart_rule` se elimina
 * al consumirse—.
 */
class BirthdayCampaignDashboardService
{
    public function __construct(
        private readonly BirthdayRedemptionSyncService $sync,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forCampaign(BirthdayCampaign $campaign): array
    {
        return [
            'bonos' => $this->bonos($campaign),
            'money' => $this->money($campaign),
            'timeline' => $this->timeline($campaign),
            'reconciliation' => $this->reconciliation($campaign),
            'last_sync' => BirthdayRedemption::where('campaign_id', $campaign->id)->max('synced_at'),
            'can_sync' => $this->sync->isAvailable(),
        ];
    }

    /**
     * Estado de los bonos de la campaña: cuántos se emitieron, cuántos faltan
     * y cuántos se han gastado ya.
     *
     * @return array<string, mixed>
     */
    public function bonos(BirthdayCampaign $campaign): array
    {
        $row = $campaign->recipients()
            ->selectRaw('COUNT(coupon_code) as issued')
            ->selectRaw('SUM(CASE WHEN coupon_code IS NULL AND status IN (?, ?) THEN 1 ELSE 0 END) as missing', [
                BirthdayRecipient::STATUS_PENDING,
                BirthdayRecipient::STATUS_SKIPPED,
            ])
            ->selectRaw('MAX(coupon_amount) as amount')
            ->selectRaw('MAX(coupon_min_purchase) as min_purchase')
            ->selectRaw('MAX(coupon_valid_to) as valid_to')
            ->first();

        $issued = (int) $row->issued;
        $used = BirthdayRedemption::where('campaign_id', $campaign->id)->count();

        return [
            'issued' => $issued,
            'missing' => (int) $row->missing,
            'used' => $used,
            // Los que se emitieron y nadie gastó. No son "perdidos": el bono
            // vive semanas, así que el día de la campaña este número es
            // naturalmente casi el total.
            'unused' => max(0, $issued - $used),
            'use_rate' => $issued > 0 ? round(($used / $issued) * 100, 1) : 0.0,
            'amount' => $row->amount,
            'min_purchase' => $row->min_purchase,
            'valid_to' => $row->valid_to,
        ];
    }

    /**
     * El dinero, que es lo que dice si la campaña sirvió de algo.
     *
     * Se distingue lo facturado (lo que entró) de lo descontado (lo que costó),
     * y el canje atribuido —de alguien a quien escribimos— del canje suelto, que
     * es alguien que compró con un código reenviado.
     *
     * @return array<string, mixed>
     */
    public function money(BirthdayCampaign $campaign): array
    {
        $base = BirthdayRedemption::where('campaign_id', $campaign->id);

        $totals = (clone $base)
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(CASE WHEN attributed = 1 THEN 1 ELSE 0 END) as attributed')
            ->selectRaw('COALESCE(SUM(order_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(discount), 0) as discount')
            ->first();

        $orders = (int) $totals->orders;
        $revenue = (float) $totals->revenue;
        $sent = (int) $campaign->sent_count;

        return [
            'orders' => $orders,
            'attributed' => (int) $totals->attributed,
            'revenue' => round($revenue, 2),
            'discount' => round((float) $totals->discount, 2),
            // Lo que hace que 5 € de descuento tengan sentido: el pedido medio.
            'avg_order' => $orders > 0 ? round($revenue / $orders, 2) : 0.0,
            // Conversión sobre los correos que SÍ salieron.
            'rate' => $sent > 0 ? round(((int) $totals->attributed / $sent) * 100, 1) : 0.0,
        ];
    }

    /**
     * Canjes por día desde el envío. Enseña cuánto tarda la gente en usar el
     * bono, que es lo que dice si la validez configurada tiene sentido.
     *
     * @return array<int, array{date: string, label: string, orders: int, revenue: float}>
     */
    public function timeline(BirthdayCampaign $campaign): array
    {
        return BirthdayRedemption::where('campaign_id', $campaign->id)
            ->selectRaw('DATE(ordered_at) as day, COUNT(*) as orders, COALESCE(SUM(order_total), 0) as revenue')
            ->whereNotNull('ordered_at')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r): array => [
                'date' => (string) $r->day,
                'label' => Carbon::parse($r->day)->format('d/m'),
                'orders' => (int) $r->orders,
                'revenue' => round((float) $r->revenue, 2),
            ])->all();
    }

    /**
     * Bonos que la tienda descontó y Gestión no registró: hasta que se marquen,
     * ese bono sigue vivo en el ERP y se puede volver a gastar.
     *
     * @return array<string, mixed>
     */
    public function reconciliation(BirthdayCampaign $campaign): array
    {
        $row = BirthdayRedemption::where('campaign_id', $campaign->id)
            ->selectRaw('SUM(CASE WHEN erp_marked = 0 THEN 1 ELSE 0 END) as pending')
            ->selectRaw('COALESCE(SUM(CASE WHEN erp_marked = 0 THEN discount ELSE 0 END), 0) as amount')
            ->selectRaw('SUM(CASE WHEN erp_marked = 0 AND coupon_code IS NOT NULL THEN 1 ELSE 0 END) as markable')
            ->first();

        return [
            'pending' => (int) $row->pending,
            'amount' => round((float) $row->amount, 2),
            // Sin código no hay nada que marcar en Gestión, por mucho que el
            // descuadre exista: la cart_rule se borró y nadie registró el bono.
            'markable' => (int) $row->markable,
        ];
    }

    /**
     * Los mismos números para TODA la tienda, sin campaña: es el histórico de
     * cheques de cumpleaños anterior a este módulo y la única referencia contra
     * la que decir si una campaña va bien.
     *
     * @return array<string, mixed>
     */
    public function historicalBaseline(): array
    {
        $row = BirthdayRedemption::query()
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('COALESCE(SUM(order_total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(discount), 0) as discount')
            ->selectRaw('MIN(ordered_at) as first_at')
            ->selectRaw('MAX(ordered_at) as last_at')
            ->first();

        $orders = (int) $row->orders;

        if ($orders === 0) {
            return ['available' => false];
        }

        $months = max(1, (int) round(
            (strtotime((string) $row->last_at) - strtotime((string) $row->first_at)) / (30 * 86400)
        ));

        return [
            'available' => true,
            'orders' => $orders,
            'revenue' => round((float) $row->revenue, 2),
            'discount' => round((float) $row->discount, 2),
            'avg_order' => round((float) $row->revenue / $orders, 2),
            'per_month' => round($orders / $months, 1),
            'first_at' => $row->first_at,
            'last_at' => $row->last_at,
        ];
    }

    /**
     * Motivos por los que se omitió gente EN ESTA campaña.
     *
     * @return array<string, int>
     */
    public function skipReasons(BirthdayCampaign $campaign): array
    {
        return $campaign->recipients()
            ->where('status', BirthdayRecipient::STATUS_SKIPPED)
            ->select('skip_reason', DB::raw('COUNT(*) as total'))
            ->groupBy('skip_reason')
            ->pluck('total', 'skip_reason')
            ->map(static fn ($v): int => (int) $v)
            ->all();
    }
}
