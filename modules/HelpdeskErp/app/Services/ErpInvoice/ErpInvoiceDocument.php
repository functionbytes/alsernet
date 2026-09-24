<?php

namespace Modules\HelpdeskErp\Services\ErpInvoice;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Modelo de vista de la COPIA INFORMATIVA de una factura de Gestión.
 *
 * Parte del `data` de ErpChatService::invoiceDetail(), que es el de
 * CustomerController::invoiceDetail del manager tal cual:
 *   {id, series, number, year, date, type, simplified, payment_method,
 *    warehouse, catalog, debt_id, status, observations,
 *    customer:{name,cif,address,city,postal_code,province,country},
 *    company:{…mismo shape…},
 *    lines:[{id, article:{id,code,description}, units, price_bi,
 *            discount_percent, tax_percent, surcharge_percent, total_bi,
 *            total_with_taxes, warehouse, created}],
 *    totals:{lines_total_bi, lines_total_with_taxes}, statistics, created, updated}
 *
 * No inventa importes: bases y totales salen de las líneas del ERP. La
 * cuota de IVA de cada tipo es (total con impuestos − base − recargo), así
 * que el desglose siempre suma el total que da Gestión.
 */
final class ErpInvoiceDocument
{
    /**
     * @param  array<string, mixed>  $data  data de invoiceDetail (state ok)
     * @return array<string, mixed>
     */
    public static function fromDetail(array $data): array
    {
        $lines = [];
        $groups = [];

        foreach ((array) ($data['lines'] ?? []) as $line) {
            if (! is_array($line)) {
                continue;
            }

            $article = is_array($line['article'] ?? null) ? $line['article'] : [];
            $units = self::num($line['units'] ?? null) ?? 0.0;
            $price = self::num($line['price_bi'] ?? null);
            $discount = self::num($line['discount_percent'] ?? null) ?? 0.0;
            $tax = self::num($line['tax_percent'] ?? null) ?? 0.0;
            $surcharge = self::num($line['surcharge_percent'] ?? null) ?? 0.0;
            $base = self::num($line['total_bi'] ?? null);
            if ($base === null && $price !== null) {
                $base = round($units * $price * (1 - $discount / 100), 2);
            }
            $base ??= 0.0;
            $withTaxes = self::num($line['total_with_taxes'] ?? null) ?? round($base * (1 + ($tax + $surcharge) / 100), 2);

            $lines[] = [
                'code' => self::text($article['code'] ?? null),
                'description' => self::text($article['description'] ?? null) ?? 'Artículo',
                'units' => $units,
                'price' => $price,
                'discount' => $discount,
                'tax' => $tax,
                'surcharge' => $surcharge,
                'base' => $base,
                'total' => $withTaxes,
            ];

            $key = self::pct($tax).'|'.self::pct($surcharge);
            $groups[$key] ??= ['tax' => $tax, 'surcharge' => $surcharge, 'base' => 0.0, 'total' => 0.0];
            $groups[$key]['base'] += $base;
            $groups[$key]['total'] += $withTaxes;
        }

        $taxes = [];
        foreach ($groups as $g) {
            $base = round($g['base'], 2);
            $total = round($g['total'], 2);
            $surchargeAmount = $g['surcharge'] > 0 ? round($base * $g['surcharge'] / 100, 2) : 0.0;
            $taxes[] = [
                'tax' => $g['tax'],
                'surcharge' => $g['surcharge'],
                'base' => $base,
                'tax_amount' => round($total - $base - $surchargeAmount, 2),
                'surcharge_amount' => $surchargeAmount,
                'total' => $total,
            ];
        }
        usort($taxes, fn (array $a, array $b): int => $b['tax'] <=> $a['tax']);

        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];
        $sumBase = round(array_sum(array_column($lines, 'base')), 2);
        $sumTotal = round(array_sum(array_column($lines, 'total')), 2);
        $totalBase = self::num($totals['lines_total_bi'] ?? null) ?? $sumBase;
        $total = self::num($totals['lines_total_with_taxes'] ?? null) ?? $sumTotal;

        return [
            'id' => $data['id'] ?? null,
            'ref' => self::ref($data),
            'series' => self::text($data['series'] ?? null),
            'number' => self::text($data['number'] ?? null),
            'year' => self::text($data['year'] ?? null),
            'date' => self::date($data['date'] ?? null),
            'simplified' => (bool) ($data['simplified'] ?? false),
            'void' => self::isVoid($data['status'] ?? null),
            'payment_method' => self::text($data['payment_method'] ?? null),
            'warehouse' => self::text($data['warehouse'] ?? null),
            'observations' => self::text($data['observations'] ?? null),
            'company' => self::party($data['company'] ?? null),
            'customer' => self::party($data['customer'] ?? null),
            'lines' => $lines,
            'taxes' => $taxes,
            'totals' => [
                'base' => $totalBase,
                'taxes' => round($total - $totalBase, 2),
                'total' => $total,
            ],
        ];
    }

    /**
     * "F-51498/2025" (serie-número/año) o el id si no hay nada más.
     *
     * @param  array<string, mixed>  $data
     */
    public static function ref(array $data): string
    {
        $parts = array_filter([self::text($data['series'] ?? null), self::text($data['number'] ?? null)], fn ($v) => $v !== null);
        $ref = implode('-', $parts);
        $year = self::text($data['year'] ?? null);
        if ($year !== null) {
            $ref .= ($ref !== '' ? '/' : '').$year;
        }

        return $ref !== '' ? $ref : (string) ($data['id'] ?? '');
    }

    public static function filename(array $data): string
    {
        $slug = Str::slug(str_replace('/', '-', self::ref($data)));

        return 'copia-factura-'.($slug !== '' ? $slug : 'erp').'.pdf';
    }

    /**
     * Factura de ejemplo con EXACTAMENTE la forma de invoiceDetail del
     * manager (cliente de prueba 101544116, artículos reales de sus
     * albaranes). Sirve para ver el PDF mientras Oracle no dé el GRANT.
     *
     * @return array<string, mixed>
     */
    public static function sample(): array
    {
        $lines = [
            ['id' => 10104949884, 'article' => ['id' => 100127374, 'code' => 'PW20143-3', 'description' => 'CAÑA KALI KUNNAN GENESIS 270'],
                'units' => 1.0, 'price_bi' => 33.0496, 'discount_percent' => 0.0, 'tax_percent' => 21.0, 'surcharge_percent' => 0.0,
                'total_bi' => 33.05, 'total_with_taxes' => 39.99, 'warehouse' => '1', 'created' => '2025-11-11 15:41:55'],
            ['id' => 10104949885, 'article' => ['id' => 100098123, 'code' => 'C307232-42', 'description' => 'PANTALÓN HART FIELDPRO-T TALLA 42'],
                'units' => 2.0, 'price_bi' => 41.314, 'discount_percent' => 10.0, 'tax_percent' => 21.0, 'surcharge_percent' => 0.0,
                'total_bi' => 74.37, 'total_with_taxes' => 89.99, 'warehouse' => '1', 'created' => '2025-11-11 15:41:55'],
            ['id' => 10104949886, 'article' => ['id' => 100011002, 'code' => 'LIB-0042', 'description' => 'GUÍA DE PESCA DE RÍO (LIBRO)'],
                'units' => 1.0, 'price_bi' => 18.2692, 'discount_percent' => 0.0, 'tax_percent' => 4.0, 'surcharge_percent' => 0.0,
                'total_bi' => 18.27, 'total_with_taxes' => 19.0, 'warehouse' => '1', 'created' => '2025-11-11 15:41:55'],
            ['id' => 10104949887, 'article' => ['id' => 100000001, 'code' => 'PORTES', 'description' => 'GASTOS DE ENVÍO'],
                'units' => 1.0, 'price_bi' => 0.0, 'discount_percent' => 0.0, 'tax_percent' => 21.0, 'surcharge_percent' => 0.0,
                'total_bi' => 0.0, 'total_with_taxes' => 0.0, 'warehouse' => '1', 'created' => '2025-11-11 15:41:55'],
        ];

        return [
            'id' => 10100874512,
            'series' => 'FA',
            'number' => '51498',
            'year' => '2025',
            'date' => '2025-11-11',
            'type' => '1',
            'simplified' => false,
            'payment_method' => 'TARJETA',
            'warehouse' => '1',
            'catalog' => '5',
            'debt_id' => null,
            'status' => 1,
            'observations' => "Pedido web 10102142050.\nEntrega en domicilio.",
            'customer' => [
                'name' => 'ALBERTO MARCOS CAMARZANA',
                'cif' => '45688302K',
                'address' => 'QUIJADAS 2',
                'city' => 'CASTROVERDE DE CAMPOS',
                'postal_code' => '49110',
                'province' => 'ZAMORA',
                'country' => 'ESPAÑA',
            ],
            'company' => [
                'name' => 'EMPRESA DE EJEMPLO, S.L.',
                'cif' => 'B00000000',
                'address' => 'CALLE DE EJEMPLO 1',
                'city' => 'LEÓN',
                'postal_code' => '24001',
                'province' => 'LEÓN',
                'country' => 'ESPAÑA',
            ],
            'lines' => $lines,
            'totals' => [
                'lines_total_bi' => round(array_sum(array_column($lines, 'total_bi')), 2),
                'lines_total_with_taxes' => round(array_sum(array_column($lines, 'total_with_taxes')), 2),
            ],
            'statistics' => ['lines' => ['total' => count($lines)]],
            'created' => '2025-11-11 15:41:55',
            'updated' => '2025-11-11 15:41:55',
        ];
    }

    /* ── Helpers ─────────────────────────────────────────────────────── */

    /**
     * @return array{name: ?string, cif: ?string, address: ?string, city_line: ?string, country: ?string}
     */
    private static function party(mixed $p): array
    {
        $p = is_array($p) ? $p : [];
        $cityLine = trim(implode(' ', array_filter([self::text($p['postal_code'] ?? null), self::text($p['city'] ?? null)])));
        $province = self::text($p['province'] ?? null);
        if ($province !== null && strcasecmp($province, (string) self::text($p['city'] ?? null)) !== 0) {
            $cityLine .= ($cityLine !== '' ? ' (' : '(').$province.')';
        }

        return [
            'name' => self::text($p['name'] ?? null),
            'cif' => self::text($p['cif'] ?? null),
            'address' => self::text($p['address'] ?? null),
            'city_line' => $cityLine !== '' ? $cityLine : null,
            'country' => self::text($p['country'] ?? null),
        ];
    }

    private static function isVoid(mixed $status): bool
    {
        return $status === false || $status === 0 || $status === '0';
    }

    private static function date(mixed $value): ?string
    {
        $v = self::text($value);
        if ($v === null) {
            return null;
        }

        try {
            return Carbon::parse($v)->format('d/m/Y');
        } catch (\Throwable) {
            return $v;
        }
    }

    private static function text(mixed $v): ?string
    {
        if ($v === null || is_array($v) || is_object($v)) {
            return null;
        }
        $s = trim((string) $v);

        return $s !== '' ? $s : null;
    }

    private static function num(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    private static function pct(float $v): string
    {
        return number_format($v, 2, '.', '');
    }
}
