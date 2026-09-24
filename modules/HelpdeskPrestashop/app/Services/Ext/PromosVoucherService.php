<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Illuminate\Support\Facades\Cache;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Extensión "promos": cupones propios del cliente (pieza 33, editar o
 * duplicar) y promociones públicas de la tienda (pieza 34). Todo pasa por el
 * puente (alsernetbridge/helpers/ext/promos.php), que es quien comprueba que
 * el cupón es del cliente y si ya entró en algún pedido.
 */
class PromosVoucherService
{
    public const SHOP_CACHE_KEY = 'helpdeskprestashop.ext.promos.shop';

    public function __construct(
        private readonly PrestashopContextService $ps
    ) {}

    /**
     * Un cupón del cliente con sus usos y si se puede editar; null si no es
     * suyo o no existe.
     *
     * @return array<string, mixed>|null
     *
     * @throws PsUpstreamException
     */
    public function voucherInfo(Customer $customer, int $voucherId): ?array
    {
        $lookup = $this->lookup($customer, 'promos.voucher_info');
        if ($lookup === null) {
            return null;
        }

        $data = $this->ps->callBridge('promos.voucher_info', [
            'lookup' => $lookup,
            'voucher_id' => $voucherId,
        ]);

        return is_array($data['voucher'] ?? null) ? $data['voucher'] : null;
    }

    /**
     * Edita (mode=edit) o duplica (mode=duplicate) el cupón. $values:
     * amount (€) o percent, minimum (€), date_to (Y-m-d), quantity. $limits:
     * max_amount (€) + los de config ext.promos.edit.
     *
     * @return array<string, mixed>|null respuesta del puente (saved=true o
     *                                   ok_semantic=false + error); null si el
     *                                   cupón no es del cliente
     *
     * @throws PsUpstreamException
     */
    public function editVoucher(Customer $customer, int $voucherId, string $mode, array $values, float $maxAmount, string $agent, string $idempotencyKey): ?array
    {
        $lookup = $this->lookup($customer, 'promos.voucher_edit');
        if ($lookup === null) {
            return null;
        }

        $cfg = (array) config('helpdeskprestashop.ext.promos.edit', []);

        $payload = [
            'lookup' => $lookup,
            'voucher_id' => $voucherId,
            'mode' => $mode,
            'minimum_cents' => (int) round(((float) ($values['minimum'] ?? 0)) * 100),
            'date_to' => (string) $values['date_to'],
            'quantity' => (int) $values['quantity'],
            'max_amount_cents' => (int) round($maxAmount * 100),
            'max_percent' => (float) ($cfg['max_percent'] ?? 30),
            'max_quantity' => (int) ($cfg['max_quantity'] ?? 5),
            'max_validity_days' => (int) ($cfg['max_validity_days'] ?? 365),
            'agent' => $agent,
        ];

        // Solo viaja el valor del tipo que tiene el cupón; el puente ignora
        // el otro, pero así el log del puente refleja lo que se pidió.
        if (isset($values['amount']) && $values['amount'] !== null && $values['amount'] !== '') {
            $payload['amount_cents'] = (int) round(((float) $values['amount']) * 100);
        }
        if (isset($values['percent']) && $values['percent'] !== null && $values['percent'] !== '') {
            $payload['percent'] = round((float) $values['percent'], 2);
        }

        $result = $this->ps->callBridge('promos.voucher_edit', $payload, $idempotencyKey);

        // El contexto cacheado del cliente incluye sus cupones.
        if ($customer->email) {
            $this->ps->forgetCache($customer->email);
        }

        return $result;
    }

    /**
     * Promociones públicas y vigentes (con código) + automáticas de la tienda.
     * Es la misma lista para todos los clientes: caché corta compartida.
     *
     * @return array{promotions: array<int, array<string, mixed>>, automatic: array<int, array<string, mixed>>}
     *
     * @throws PsUpstreamException
     */
    public function shopPromotions(bool $fresh = false): array
    {
        if (! $fresh) {
            $cached = Cache::get(self::SHOP_CACHE_KEY);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $cfg = (array) config('helpdeskprestashop.ext.promos.shop', []);

        $data = $this->ps->callBridge('promos.shop_list', [
            'min_quantity' => max(2, (int) ($cfg['min_quantity'] ?? 2)),
            'exclude_codes' => array_values((array) ($cfg['exclude_codes'] ?? [])),
        ]);

        $out = [
            'promotions' => array_values((array) ($data['promotions'] ?? [])),
            'automatic' => array_values((array) ($data['automatic'] ?? [])),
        ];

        // Un ok=false (null) no se cachea: se reintenta en la próxima apertura.
        if ($data !== null) {
            Cache::put(self::SHOP_CACHE_KEY, $out, max(30, (int) ($cfg['cache_ttl'] ?? 300)));
        }

        return $out;
    }

    /**
     * @return array{email?: string, external_id?: int}|null
     */
    private function lookup(Customer $customer, string $action): ?array
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $this->ps->ownershipLookup(
            $customer->email ?: null,
            $externalId !== null ? (int) $externalId : null,
            $action,
        );
    }
}
