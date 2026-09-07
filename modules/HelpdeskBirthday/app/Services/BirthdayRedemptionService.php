<?php

namespace Modules\HelpdeskBirthday\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskBirthday\Models\BirthdayCampaign;
use Modules\HelpdeskBirthday\Models\BirthdayRecipient;
use Throwable;

/**
 * Cuánta gente usó de verdad el cupón, que es la única métrica que dice si la
 * campaña sirve para algo. Abrir el correo no es comprar.
 *
 * El dato vive en PrestaShop: un cupón canjeado deja una fila en
 * `order_cart_rule` atada al pedido. Se consulta con el mismo patrón que
 * HelpdeskPrestashop\Services\PrestashopProductQueryService (base de datos
 * cualificada por nombre, sin conexión Eloquent propia, porque la conexión
 * 'prestashop' de config/database.php no está configurada en este entorno).
 *
 * LIMITACIÓN CONOCIDA: el cupón del día es el mismo para todos, así que
 * PrestaShop no sabe "a quién se lo mandamos". La atribución se hace cruzando
 * el email del pedido con los destinatarios de la campaña; quien lo reenvíe a
 * un amigo cuenta como canje del cupón pero no como canje atribuido.
 */
class BirthdayRedemptionService
{
    private string $db;

    private string $prefix;

    public function __construct()
    {
        $this->db = (string) config('helpdeskprestashop.ps_db', '');
        $this->prefix = (string) config('helpdeskprestashop.ps_prefix', 'aalv_');
    }

    public function isAvailable(): bool
    {
        return $this->db !== '';
    }

    /**
     * Canjes de la campaña: cuántos pedidos usaron su cupón, cuánto se
     * descontó y cuántos de esos pedidos son de gente a la que se lo enviamos.
     *
     * @return array<string, mixed>
     */
    public function forCampaign(BirthdayCampaign $campaign): array
    {
        $empty = [
            'available' => false,
            'redemptions' => 0,
            'attributed' => 0,
            'revenue' => 0.0,
            'discount' => 0.0,
            'rate' => 0.0,
        ];

        if (! $this->isAvailable() || ! $campaign->coupon_code) {
            return $empty;
        }

        try {
            $rows = $this->redemptionRows($campaign->coupon_code, $campaign->coupon_valid_from?->toDateString());
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudieron leer los canjes en PrestaShop', [
                'coupon' => $campaign->coupon_code,
                'error' => $e->getMessage(),
            ]);

            return $empty;
        }

        // A quién se lo mandamos, para separar canje atribuido de canje suelto.
        $sentTo = $campaign->recipients()
            ->where('status', 'sent')
            ->pluck('email')
            ->map(static fn (string $e): string => mb_strtolower($e))
            ->flip();

        $attributed = 0;
        $revenue = 0.0;
        $discount = 0.0;

        foreach ($rows as $row) {
            $revenue += (float) $row->total_paid;
            $discount += (float) $row->value;

            if (isset($sentTo[mb_strtolower((string) $row->email)])) {
                $attributed++;
            }
        }

        $sentCount = (int) $campaign->sent_count;

        return [
            'available' => true,
            'redemptions' => count($rows),
            'attributed' => $attributed,
            'revenue' => round($revenue, 2),
            'discount' => round($discount, 2),
            // Sobre los correos enviados: es la conversión real de la campaña.
            'rate' => $sentCount > 0 ? round(($attributed / $sentCount) * 100, 1) : 0.0,
        ];
    }

    /**
     * Canjes de VARIAS campañas en una sola consulta.
     *
     * El panel agrega 30 o 90 días de campañas; pedirlos uno a uno era un N+1
     * contra la base de PrestaShop, y cada consulta es un JOIN de cuatro
     * tablas sobre cientos de miles de clientes.
     *
     * @param  Collection<int, BirthdayCampaign>  $campaigns
     * @return array<string, mixed> agregado de todas
     */
    public function forCampaigns($campaigns): array
    {
        $empty = ['available' => false, 'redemptions' => 0, 'attributed' => 0, 'revenue' => 0.0, 'discount' => 0.0, 'rate' => 0.0];

        $withCoupon = $campaigns->filter(fn (BirthdayCampaign $c): bool => (bool) $c->coupon_code);

        if (! $this->isAvailable() || $withCoupon->isEmpty()) {
            return $empty;
        }

        // La ventana arranca en el inicio de validez más antiguo: acotar por
        // fecha es lo que impide contar canjes de campañas anteriores que
        // reutilizaran el mismo código.
        $since = $withCoupon
            ->map(fn (BirthdayCampaign $c): ?string => $c->coupon_valid_from?->toDateString())
            ->filter()
            ->min();

        try {
            $rows = $this->redemptionRowsForCodes($withCoupon->pluck('coupon_code')->unique()->all(), $since);
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudieron leer los canjes agregados', ['error' => $e->getMessage()]);

            return $empty;
        }

        // Destinatarios de todas las campañas del periodo, de una consulta.
        $sentTo = BirthdayRecipient::query()
            ->whereIn('campaign_id', $withCoupon->pluck('id'))
            ->where('status', BirthdayRecipient::STATUS_SENT)
            ->pluck('email')
            ->map(static fn (string $e): string => mb_strtolower($e))
            ->flip();

        $attributed = 0;
        $revenue = 0.0;
        $discount = 0.0;

        foreach ($rows as $row) {
            $revenue += (float) $row->total_paid;
            $discount += (float) $row->value;

            if (isset($sentTo[mb_strtolower((string) $row->email)])) {
                $attributed++;
            }
        }

        $sent = (int) $withCoupon->sum('sent_count');

        return [
            'available' => true,
            'redemptions' => count($rows),
            'attributed' => $attributed,
            'revenue' => round($revenue, 2),
            'discount' => round($discount, 2),
            'rate' => $sent > 0 ? round(($attributed / $sent) * 100, 1) : 0.0,
        ];
    }

    /**
     * Emails que canjearon el cupón, para marcar la fila del destinatario.
     *
     * @return array<string, array{order_id: int, value: float, date: ?string}>
     */
    public function redeemersFor(BirthdayCampaign $campaign): array
    {
        if (! $this->isAvailable() || ! $campaign->coupon_code) {
            return [];
        }

        try {
            $rows = $this->redemptionRows($campaign->coupon_code, $campaign->coupon_valid_from?->toDateString());
        } catch (Throwable) {
            return [];
        }

        $out = [];

        foreach ($rows as $row) {
            $out[mb_strtolower((string) $row->email)] = [
                'order_id' => (int) $row->id_order,
                'value' => (float) $row->value,
                'date' => $row->date_add ?? null,
            ];
        }

        return $out;
    }

    /**
     * Detalle completo de cada canje, para la pantalla de validación: qué
     * pedido se hizo, en qué estado está y qué contestó gestión cuando se
     * marcó el bono.
     *
     * `marcarbono` es la tabla que PrestaShop escribe al canjear un bono
     * contra gestión (ver Marcarbono.php del override): su `erp_response` es
     * literalmente la validación en el ERP, y es lo que permite detectar un
     * cupón consumido en la tienda que NO llegó a marcarse en gestión.
     *
     * @return array<int, array<string, mixed>>
     */
    public function detailFor(BirthdayCampaign $campaign): array
    {
        if (! $this->isAvailable() || ! $campaign->coupon_code) {
            return [];
        }

        $db = $this->db;
        $pfx = $this->prefix;
        $since = $campaign->coupon_valid_from?->toDateString();

        try {
            $rows = DB::table("{$db}.{$pfx}order_cart_rule as ocr")
                ->join("{$db}.{$pfx}cart_rule as cr", 'cr.id_cart_rule', '=', 'ocr.id_cart_rule')
                ->join("{$db}.{$pfx}orders as o", 'o.id_order', '=', 'ocr.id_order')
                ->join("{$db}.{$pfx}customer as c", 'c.id_customer', '=', 'o.id_customer')
                ->leftJoin("{$db}.{$pfx}order_state_lang as osl", function ($j): void {
                    $j->on('osl.id_order_state', '=', 'o.current_state')->where('osl.id_lang', '=', 1);
                })
                // El canje del bono contra gestión: puede no existir si el
                // cupón se aplicó sin pasar por el flujo del ERP.
                ->leftJoin("{$db}.{$pfx}marcarbono as mb", function ($j): void {
                    $j->on('mb.id_order', '=', 'o.id_order')->where('mb.deleted', '=', 0);
                })
                ->where('cr.code', $campaign->coupon_code)
                ->where('ocr.deleted', 0)
                ->when($since !== null, fn ($q) => $q->whereDate('o.date_add', '>=', $since))
                ->orderByDesc('o.date_add')
                ->select([
                    'c.email', 'c.id_customer',
                    'o.id_order', 'o.reference', 'o.total_paid', 'o.date_add', 'o.valid',
                    'osl.name as order_state',
                    'ocr.value as discount',
                    'mb.operacion as erp_operation',
                    'mb.erp_response',
                    'mb.date_add as erp_marked_at',
                ])
                ->get();
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudo leer el detalle de canjes', [
                'coupon' => $campaign->coupon_code,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        // Destinatarios de la campaña, para saber si el pedido es de alguien a
        // quien se lo mandamos y poder saltar a su ficha del ERP.
        $recipients = $campaign->recipients()
            ->get(['email', 'erp_customer_id', 'name'])
            ->keyBy(fn ($r): string => mb_strtolower($r->email));

        return $rows->map(function ($row) use ($recipients): array {
            $email = mb_strtolower((string) $row->email);
            $recipient = $recipients->get($email);

            return [
                'email' => $email,
                'name' => $recipient->name ?? null,
                'attributed' => $recipient !== null,
                'erp_customer_id' => $recipient->erp_customer_id ?? null,
                'ps_customer_id' => (int) $row->id_customer,
                'order_id' => (int) $row->id_order,
                'order_reference' => $row->reference,
                'order_state' => $row->order_state,
                'order_valid' => (bool) $row->valid,
                'order_total' => (float) $row->total_paid,
                'discount' => (float) $row->discount,
                'ordered_at' => $row->date_add,
                // Validación en gestión.
                'erp_operation' => $row->erp_operation,
                'erp_response' => $row->erp_response,
                'erp_marked_at' => $row->erp_marked_at,
                'erp_ok' => $row->erp_response !== null && mb_strtolower(trim((string) $row->erp_response)) === 'ok',
            ];
        })->all();
    }

    /**
     * @return array<int, object>
     */
    private function redemptionRows(string $code, ?string $since): array
    {
        return $this->redemptionRowsForCodes([$code], $since);
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, object>
     */
    private function redemptionRowsForCodes(array $codes, ?string $since): array
    {
        if ($codes === []) {
            return [];
        }

        $db = $this->db;
        $pfx = $this->prefix;

        return DB::table("{$db}.{$pfx}order_cart_rule as ocr")
            ->join("{$db}.{$pfx}cart_rule as cr", 'cr.id_cart_rule', '=', 'ocr.id_cart_rule')
            ->join("{$db}.{$pfx}orders as o", 'o.id_order', '=', 'ocr.id_order')
            ->join("{$db}.{$pfx}customer as c", 'c.id_customer', '=', 'o.id_customer')
            ->whereIn('cr.code', $codes)
            ->where('ocr.deleted', 0)
            // El código del cupón puede reutilizarse entre campañas; acotar
            // desde el inicio de validez evita contar canjes de otro día.
            ->when($since !== null, fn ($q) => $q->whereDate('o.date_add', '>=', $since))
            ->select([
                'c.email',
                'o.id_order',
                'o.total_paid',
                'o.date_add',
                'ocr.value',
            ])
            ->get()
            ->all();
    }
}
