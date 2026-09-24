<?php

namespace Modules\HelpdeskErp\Services\ErpCross;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpChat\ErpChatService;

/**
 * Pedido de Gestión → pedido de la tienda (PrestaShop). SOLO LECTURA.
 *
 * Orden de pruebas (se para en la primera que da un resultado fiable):
 *
 *   1. origin_id — la API REST de Gestión (pedido-cliente/?idcliente=) da,
 *      para cada pedido del cliente, su `identificadororigen`: el id_order de
 *      PrestaShop que manda AlvarezERP al pasar el pedido a Gestión. Solo se
 *      acepta si el bridge de PrestaShop confirma que ese pedido es de ESTE
 *      cliente (order.detail con lookup email/id_customer).
 *   2. date_amount — último recurso, solo si Gestión no da identificador de
 *      origen (o no responde) y el pedido es de origen INTERNET: pedidos del
 *      cliente en la tienda (id_customer = CODIGO_INTERNET de Gestión) del
 *      mismo día y con el mismo importe con IVA. Solo si hay UNO.
 *
 * Nunca se "adivina": sin coincidencia fiable, ps_order_id = null.
 */
class ErpCrossShopMatcher
{
    public const MATCH_ORIGIN = 'origin_id';

    public const MATCH_DATE_AMOUNT = 'date_amount';

    private const PS_SERVICE = 'Modules\\HelpdeskPrestashop\\Services\\PrestashopContextService';

    private const CACHE_PREFIX = 'helpdeskerp:cross:shop:';

    public function __construct(
        private readonly ErpChatService $erp,
        private readonly ErpCrossGestionClient $gestion,
    ) {}

    public static function prestashopAvailable(): bool
    {
        if (! class_exists(self::PS_SERVICE)) {
            return false;
        }

        if (function_exists('helpdesk_prestashop_enabled')) {
            try {
                return helpdesk_prestashop_enabled();
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{state: string, data: array<string, mixed>|null, message: ?string, reason: ?string, cached?: bool}
     */
    public function shopOrderFor(Customer $customer, int $erpId, int $centralOrderId, bool $fresh = false): array
    {
        if (! self::prestashopAvailable()) {
            return $this->result('unavailable', null, 'La tienda (PrestaShop) no está activa.', 'prestashop_disabled');
        }

        $key = self::CACHE_PREFIX.$customer->id.':'.$erpId.':'.$centralOrderId;

        if (! $fresh && is_array($cached = Cache::get($key))) {
            $cached['cached'] = true;

            return $cached;
        }

        $result = $this->build($customer, $erpId, $centralOrderId, $fresh);

        $ttl = match (true) {
            $result['state'] === 'ok' && ($result['data']['ps_order_id'] ?? null) !== null => (int) config('helpdeskErp.ext.cross.match_cache_ttl', 1800),
            $result['state'] === 'ok' => (int) config('helpdeskErp.ext.cross.miss_cache_ttl', 600),
            default => 0, // caídas y "no encontrado": que se reintente
        };

        if ($ttl > 0) {
            Cache::put($key, $result, $ttl);
        }

        $result['cached'] = false;

        return $result;
    }

    /**
     * @return array{state: string, data: array<string, mixed>|null, message: ?string, reason: ?string}
     */
    private function build(Customer $customer, int $erpId, int $centralOrderId, bool $fresh): array
    {
        // 1. El pedido es de este cliente de Gestión (el manager filtra por
        //    cliente: uno ajeno o inexistente es "unavailable").
        $detail = $this->erp->orderDetail($erpId, $centralOrderId, $fresh);

        if ($detail['state'] === 'unavailable') {
            return $this->result('unavailable', null, 'Pedido no encontrado para este cliente.', 'not_found');
        }

        $order = $detail['state'] === 'ok' && is_array($detail['data']) ? $detail['data'] : [];
        $orderId = $this->localOrderId($order, $centralOrderId);
        $isInternet = $this->isInternet($order);

        $base = [
            'erp_order_id' => (string) $centralOrderId,
            'erp_number' => isset($order['number']) && is_scalar($order['number']) ? (string) $order['number'] : null,
            'ps_order_id' => null,
            'reference' => null,
            'matched_by' => null,
            'origin' => $this->originLabel($order),
        ];

        // 2. Identificador de origen en Gestión.
        $gestion = $this->gestion->ordersByCustomer($erpId, $fresh);
        $row = $gestion['ok'] && $orderId !== null ? ($gestion['data'][$orderId] ?? null) : null;

        if ($row !== null && $base['erp_number'] === null) {
            $base['erp_number'] = $row['number'];
        }

        $psService = app(self::PS_SERVICE);
        $email = $this->email($customer);
        $psCustomerId = $this->psCustomerId($customer, $erpId);

        if ($row !== null && $row['origin_id'] !== null) {
            if ($email === null && $psCustomerId === null) {
                return $this->result('ok', $base, 'No se puede comprobar en la tienda que el pedido sea de este cliente.', 'unverified');
            }

            try {
                /** @var array<string, mixed>|null $ps */
                $ps = $psService->getOrderDetail((int) $row['origin_id'], $email, $psCustomerId);
            } catch (\Throwable) {
                return $this->result('down', $base, 'La tienda no responde ahora mismo.', 'prestashop_down');
            }

            if (is_array($ps) && (int) ($ps['id'] ?? 0) === (int) $row['origin_id']) {
                return $this->result('ok', array_merge($base, [
                    'ps_order_id' => (int) $row['origin_id'],
                    'reference' => isset($ps['reference']) && is_scalar($ps['reference']) ? (string) $ps['reference'] : null,
                    'matched_by' => self::MATCH_ORIGIN,
                ]), null, null);
            }

            // El identificador existe pero la tienda no lo da como de este
            // cliente: no se enlaza (fail-closed).
            return $this->result('ok', $base, 'El pedido de la tienda de origen no pertenece a este cliente.', 'foreign');
        }

        // 3. Último recurso: mismo día y mismo importe, único, solo INTERNET.
        if (! $isInternet) {
            // Sin cabecera del manager no se sabe el origen: es una caída
            // (no se cachea), no un "no viene de la tienda".
            return $order === []
                ? $this->result('down', $base, 'Gestión no responde: no se puede localizar el pedido en la tienda.', 'erp_down')
                : $this->result('ok', $base, 'Este pedido no viene de la tienda online.', 'not_internet');
        }

        if ($psCustomerId === null) {
            return $this->result('ok', $base, 'El cliente no tiene cuenta en la tienda.', 'no_shop_customer');
        }

        $day = $this->day($row['date'] ?? null) ?? $this->day($order['date'] ?? null);
        $total = $row['total'] ?? $this->orderTotal($order);

        if ($day === null || $total === null) {
            return $this->result('ok', $base, 'No hay datos suficientes para localizar el pedido en la tienda.', 'no_match');
        }

        try {
            /** @var array<string, mixed>|null $list */
            $list = $psService->getCustomerOrders($email ?? '', $psCustomerId, (int) config('helpdeskErp.ext.cross.fallback_orders', 50));
        } catch (\Throwable) {
            return $this->result('down', $base, 'La tienda no responde ahora mismo.', 'prestashop_down');
        }

        $orders = is_array($list['data'] ?? null) ? $list['data'] : (is_array($list['orders'] ?? null) ? $list['orders'] : []);
        $tolerance = (float) config('helpdeskErp.ext.cross.amount_tolerance', 0.01);

        $candidates = array_values(array_filter($orders, function ($o) use ($day, $total, $tolerance): bool {
            if (! is_array($o) || ! is_numeric($o['total'] ?? null)) {
                return false;
            }

            return $this->day($o['created_at'] ?? ($o['placed_at'] ?? ($o['date_add'] ?? null))) === $day
                && abs((float) $o['total'] - $total) <= $tolerance + 0.0001;
        }));

        if (count($candidates) !== 1) {
            return $this->result('ok', $base, $candidates === []
                ? 'No hay en la tienda un pedido de este cliente de ese día y ese importe.'
                : 'Hay varios pedidos en la tienda de ese día y ese importe: no se puede saber cuál es.', 'no_match');
        }

        return $this->result('ok', array_merge($base, [
            'ps_order_id' => (int) $candidates[0]['id'],
            'reference' => isset($candidates[0]['reference']) && is_scalar($candidates[0]['reference']) ? (string) $candidates[0]['reference'] : null,
            'matched_by' => self::MATCH_DATE_AMOUNT,
        ]), null, null);
    }

    /* ── Helpers ──────────────────────────────────────────────────────── */

    /**
     * idpedidocli del pedido: el `order_id` del manager o, si no llega, el id
     * central sin el "10" de delante (10102138690 ↔ 102138690).
     *
     * @param  array<string, mixed>  $order
     */
    private function localOrderId(array $order, int $centralOrderId): ?string
    {
        $fromManager = $order['order_id'] ?? null;
        if (is_scalar($fromManager) && ctype_digit((string) $fromManager) && (int) $fromManager > 0) {
            return ltrim((string) $fromManager, '0') ?: null;
        }

        $central = (string) $centralOrderId;

        return strlen($central) > 2 && str_starts_with($central, '10') ? ltrim(substr($central, 2), '0') ?: null : null;
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function isInternet(array $order): bool
    {
        $origin = $order['origin'] ?? null;

        if (is_array($origin)) {
            $desc = (string) ($origin['description'] ?? '');
            if ($desc !== '') {
                return (bool) preg_match('/internet|web|online/i', $desc);
            }
            $origin = $origin['id'] ?? null;
        }

        $internetIds = array_map('strval', (array) config('helpdeskErp.ext.cross.internet_origin_ids', ['4']));

        return is_scalar($origin) && in_array((string) $origin, $internetIds, true);
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function originLabel(array $order): ?string
    {
        $origin = $order['origin'] ?? null;
        if (is_array($origin)) {
            return isset($origin['description']) && is_scalar($origin['description']) ? (string) $origin['description'] : null;
        }

        return null;
    }

    /**
     * Total con IVA calculado de las líneas del manager (subtotal sin IVA).
     *
     * @param  array<string, mixed>  $order
     */
    private function orderTotal(array $order): ?float
    {
        $lines = is_array($order['lines'] ?? null) ? $order['lines'] : [];
        if ($lines === []) {
            return null;
        }

        $sum = 0.0;
        foreach ($lines as $l) {
            if (! is_array($l)) {
                continue;
            }
            $sub = is_numeric($l['subtotal'] ?? null) ? (float) $l['subtotal'] : ((float) ($l['units'] ?? 1)) * ((float) ($l['price'] ?? 0)) * (1 - ((float) ($l['discount_percent'] ?? 0)) / 100);
            $sum += $sub * (1 + ((float) ($l['tax_percent'] ?? 0)) / 100);
        }

        return round($sum, 2);
    }

    private function day(mixed $value): ?string
    {
        if (! is_scalar($value) || ! preg_match('/^(\d{4}-\d{2}-\d{2})/', (string) $value, $m)) {
            return null;
        }

        return $m[1];
    }

    private function email(Customer $customer): ?string
    {
        $email = trim((string) $customer->email);

        return $email === '' || str_ends_with($email, '@anonymous.local') ? null : $email;
    }

    /**
     * id_customer de PrestaShop: vínculo guardado o CODIGO_INTERNET de Gestión.
     */
    private function psCustomerId(Customer $customer, int $erpId): ?int
    {
        try {
            $linked = $customer->externalIdFor('prestashop');
        } catch (\Throwable) {
            $linked = null;
        }

        if ($linked !== null && ctype_digit((string) $linked) && (int) $linked > 0) {
            return (int) $linked;
        }

        $summary = $this->erp->summary($erpId);
        $code = $summary['state'] === 'ok' ? ($summary['data']['code_internet'] ?? null) : null;

        return is_scalar($code) && ctype_digit(trim((string) $code)) && (int) $code > 0 ? (int) $code : null;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @return array{state: string, data: array<string, mixed>|null, message: ?string, reason: ?string}
     */
    private function result(string $state, ?array $data, ?string $message, ?string $reason): array
    {
        return ['state' => $state, 'data' => $data, 'message' => $message, 'reason' => $reason];
    }
}
