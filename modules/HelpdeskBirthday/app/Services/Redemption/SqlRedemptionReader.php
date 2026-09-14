<?php

namespace Modules\HelpdeskBirthday\Services\Redemption;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lee los canjes directamente de la base de datos de PrestaShop.
 *
 * Vale mientras webadmin y la tienda compartan MariaDB, que es el caso en
 * desarrollo. Es la red de seguridad cuando el bridge no está desplegado, se
 * cae o abre el circuito, y lo que permite trabajar en esto sin desplegar nada
 * en PrestaShop.
 *
 * Reproduce la MISMA consulta que el helper del bridge, con sus dos decisiones
 * importantes (y por las mismas razones, medidas sobre la tienda real):
 *
 *  - `cart_rule` se une con LEFT y no con INNER. PrestaShop borra la regla al
 *    consumirla: de 1.116 cheques de cumpleaños canjeados solo 4 la conservan,
 *    así que un INNER JOIN escondía el 99,6% de los canjes.
 *  - `marcarbono` se une por `id_cart_rule` y no por `id_order`. Un pedido
 *    puede llevar dos bonos, y unir por pedido cruzaba cada línea de descuento
 *    con cada registro de Gestión: 1.116 canjes se convertían en 1.132.
 */
class SqlRedemptionReader implements BirthdayRedemptionReader
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

    public function label(): string
    {
        return 'sql';
    }

    public function redemptions(?string $from, ?string $to, ?string $nameLike = null, int $limit = 500, int $offset = 0): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $pfx = $this->prefix;

        try {
            $rows = $this->base($from, $to, $nameLike)
                ->leftJoin("{$this->db}.{$pfx}customer as c", 'c.id_customer', '=', 'o.id_customer')
                ->leftJoin("{$this->db}.{$pfx}order_state_lang as osl", function ($j): void {
                    $j->on('osl.id_order_state', '=', 'o.current_state')->where('osl.id_lang', '=', 1);
                })
                ->select([
                    // El código sobrevive en la regla mientras exista y, si no,
                    // en lo que Gestión registró al consumirlo.
                    DB::raw('COALESCE(cr.code, CONCAT(mb.bono, "-", mb.codigo_verificacion)) as code'),
                    DB::raw('cr.code as live_code'),
                    'ocr.id_order_cart_rule as line_id',
                    'cr.id_cart_rule',
                    'o.id_order', 'o.reference', 'o.total_paid', 'o.date_add', 'o.valid',
                    'c.id_customer', 'c.email',
                    'ocr.name as voucher_name', 'ocr.value as discount',
                    'osl.name as order_state',
                    'mb.bono as erp_bono', 'mb.operacion as erp_operation',
                    'mb.erp_response', 'mb.importe_venta as erp_sale_amount', 'mb.date_add as erp_marked_at',
                ])
                ->orderByDesc('o.date_add')
                ->limit($limit)
                ->offset($offset)
                ->get();
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudieron leer los canjes de PrestaShop por SQL', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        return $rows->map(function ($r): array {
            $response = trim((string) ($r->erp_response ?? ''));

            return [
                // La clave de la línea de descuento: lo único que identifica un
                // canje siempre. Ver la nota de la cabecera sobre cart_rule.
                'line_id' => (int) $r->line_id,
                'code' => $r->code,
                'code_source' => $r->live_code ? 'cart_rule' : ($r->erp_bono ? 'erp' : null),
                'voucher_name' => $r->voucher_name,
                'cart_rule_id' => (int) $r->id_cart_rule,
                'order_id' => (int) $r->id_order,
                'order_reference' => $r->reference,
                'order_total' => (float) $r->total_paid,
                'order_date' => $r->date_add,
                'order_valid' => (bool) $r->valid,
                'order_state' => $r->order_state,
                'customer_id' => (int) $r->id_customer,
                'customer_email' => $r->email,
                'discount' => (float) $r->discount,
                'erp' => [
                    // OK de Gestión, no que exista la fila: marcarbono guarda
                    // también los intentos que el ERP rechazó.
                    'marked' => $response !== '' && mb_strtolower($response) === 'ok',
                    'bono' => $r->erp_bono,
                    'operation' => $r->erp_operation !== null ? (int) $r->erp_operation : null,
                    'response' => $r->erp_response,
                    'sale_amount' => $r->erp_sale_amount !== null ? (float) $r->erp_sale_amount : null,
                    'marked_at' => $r->erp_marked_at,
                ],
            ];
        })->all();
    }

    public function count(?string $from, ?string $to, ?string $nameLike = null): int
    {
        if (! $this->isAvailable()) {
            return 0;
        }

        try {
            return (int) $this->base($from, $to, $nameLike)->count();
        } catch (Throwable $e) {
            Log::warning('[HelpdeskBirthday] No se pudieron contar los canjes', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * El esqueleto compartido por la consulta y el conteo. `order_cart_rule` es
     * el ancla —es la única tabla que sobrevive a todo— y `orders` el único
     * INNER JOIN.
     */
    private function base(?string $from, ?string $to, ?string $nameLike): Builder
    {
        $db = $this->db;
        $pfx = $this->prefix;

        return DB::table("{$db}.{$pfx}order_cart_rule as ocr")
            ->join("{$db}.{$pfx}orders as o", 'o.id_order', '=', 'ocr.id_order')
            ->leftJoin("{$db}.{$pfx}cart_rule as cr", 'cr.id_cart_rule', '=', 'ocr.id_cart_rule')
            ->leftJoin("{$db}.{$pfx}marcarbono as mb", function ($j): void {
                $j->on('mb.id_cart_rule', '=', 'ocr.id_cart_rule')->where('mb.deleted', '=', 0);
            })
            ->where('ocr.deleted', 0)
            ->when($from !== null, fn ($q) => $q->where('o.date_add', '>=', $from.' 00:00:00'))
            ->when($to !== null, fn ($q) => $q->where('o.date_add', '<=', $to.' 23:59:59'))
            ->when($nameLike !== null && $nameLike !== '', fn ($q) => $q->where('ocr.name', 'like', '%'.$nameLike.'%'));
    }
}
