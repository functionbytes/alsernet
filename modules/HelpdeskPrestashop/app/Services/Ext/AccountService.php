<?php

namespace Modules\HelpdeskPrestashop\Services\Ext;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Exceptions\PsUpstreamException;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Cuenta del cliente en PrestaShop (extensión "account"): ficha editable,
 * grupo, acceso y RGPD. Solo traduce a acciones del puente; permisos, límites
 * y auditoría viven en AccountController.
 *
 * La propiedad se resuelve siempre desde el Customer del helpdesk (email +
 * external_id vinculado), nunca desde datos del request.
 */
class AccountService
{
    public function __construct(
        private readonly PrestashopContextService $bridge
    ) {}

    /**
     * @throws PsUpstreamException
     */
    public function profile(Customer $customer): ?array
    {
        return $this->call($customer, 'account.profile');
    }

    /**
     * @param  array<string, mixed>  $changes  solo los campos que cambian
     *
     * @throws PsUpstreamException
     */
    public function update(Customer $customer, array $changes, string $idempotencyKey): ?array
    {
        return $this->afterWrite($customer, $this->call($customer, 'account.update', $changes, $idempotencyKey));
    }

    /**
     * @throws PsUpstreamException
     */
    public function setGroup(Customer $customer, int $groupId, string $version, string $idempotencyKey): ?array
    {
        return $this->afterWrite($customer, $this->call($customer, 'account.set_group', [
            'group_id' => $groupId,
            'version' => $version,
        ], $idempotencyKey));
    }

    /**
     * @throws PsUpstreamException
     */
    public function sendPasswordReset(Customer $customer, string $idempotencyKey): ?array
    {
        return $this->call($customer, 'account.password_reset', [], $idempotencyKey);
    }

    /**
     * @throws PsUpstreamException
     */
    public function gdprExport(Customer $customer): ?array
    {
        return $this->call($customer, 'account.gdpr_export');
    }

    public function externalId(Customer $customer): ?int
    {
        $externalId = $customer->externalIdFor('prestashop');

        return $externalId !== null ? (int) $externalId : null;
    }

    private function call(Customer $customer, string $action, array $payload = [], ?string $idempotencyKey = null): ?array
    {
        $lookup = $this->bridge->ownershipLookup($customer->email ?: null, $this->externalId($customer), $action);
        if ($lookup === null) {
            return null;
        }

        return $this->bridge->callBridge($action, $payload + ['lookup' => $lookup], $idempotencyKey);
    }

    /**
     * Tras escribir, el contexto cacheado del tab Tienda (nombre, grupo,
     * suscripciones) queda viejo: se descarta para que el siguiente render lo
     * pida de nuevo. También si la escritura fue parcial (la ficha se guardó
     * pero el teléfono no: 'changed' no vacío).
     */
    private function afterWrite(Customer $customer, ?array $result): ?array
    {
        if (is_array($result) && (! empty($result['updated']) || ! empty($result['changed'])) && $customer->email) {
            $this->bridge->forgetCache($customer->email);
        }

        return $result;
    }
}
