<?php

namespace Modules\Helpdesk\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Autodetecta y persiste los vínculos de e-commerce de un cliente del helpdesk:
 * lo busca en PrestaShop por email (id_customer) y, a través de sus pedidos,
 * en gestión/ERP (id_cliente_gestion de `seguimiento_pedidos`). Guarda ambos
 * como external_ids para que el panel de la conversación muestre integraciones
 * y pedidos sin necesidad de pasar por el simulador.
 */
class CustomerCommerceSyncService
{
    public function __construct(
        private readonly PrestashopContextService $ps,
    ) {}

    /**
     * Enlaza el cliente con PrestaShop y gestión. Idempotente: si ya está
     * enlazado a PrestaShop y no se fuerza, no hace nada (coste mínimo).
     *
     * @return array{prestashop_id: ?int, gestion_id: ?int, linked: bool}
     */
    public function sync(Customer $customer, bool $force = false): array
    {
        $email = $customer->email;

        if (! $email) {
            return ['prestashop_id' => null, 'gestion_id' => null, 'linked' => false];
        }

        $existingPsId = $customer->externalIdFor('prestashop');

        if ($existingPsId && ! $force) {
            return [
                'prestashop_id' => (int) $existingPsId,
                'gestion_id' => $customer->externalIdFor('erp') ? (int) $customer->externalIdFor('erp') : null,
                'linked' => true,
            ];
        }

        $match = $this->lookupInPrestashop($email);

        if (! $match) {
            return ['prestashop_id' => null, 'gestion_id' => null, 'linked' => false];
        }

        try {
            $customer->linkExternalId('prestashop', (string) $match['id_customer'], ['linked_via' => 'auto']);

            if ($match['gestion_id']) {
                $customer->linkExternalId('erp', (string) $match['gestion_id'], ['linked_via' => 'auto']);
            }
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: no se pudo enlazar', ['email' => $email, 'error' => $e->getMessage()]);
        }

        if ($force) {
            $this->ps->forgetCache($email);
        }

        return [
            'prestashop_id' => (int) $match['id_customer'],
            'gestion_id' => $match['gestion_id'] ? (int) $match['gestion_id'] : null,
            'linked' => true,
        ];
    }

    /**
     * Busca clientes en PrestaShop por email, ID numérico o nombre.
     *
     * @return list<array{id: string, name: string, email: string, meta: string}>
     */
    public function searchCustomers(string $query, string $type = 'email'): array
    {
        $db = (string) config('helpdeskprestashop.ps_db', 'alvarez_cristia');
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        try {
            $rows = match ($type) {
                'id' => DB::connection('mysql')->select(
                    "SELECT c.id_customer, c.firstname, c.lastname, c.email
                       FROM `{$db}`.aalv_customer c
                      WHERE c.deleted = 0 AND c.id_customer = ?
                      LIMIT 5",
                    [(int) $query]
                ),
                'name' => DB::connection('mysql')->select(
                    "SELECT c.id_customer, c.firstname, c.lastname, c.email
                       FROM `{$db}`.aalv_customer c
                      WHERE c.deleted = 0
                        AND CONCAT(c.firstname, ' ', c.lastname) LIKE ?
                      ORDER BY c.id_customer DESC
                      LIMIT 10",
                    [$query.'%']
                ),
                default => DB::connection('mysql')->select(
                    "SELECT c.id_customer, c.firstname, c.lastname, c.email
                       FROM `{$db}`.aalv_customer c
                      WHERE c.deleted = 0 AND c.email = ?
                      ORDER BY c.id_customer DESC
                      LIMIT 10",
                    [$query]
                ),
            };
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: fallo en búsqueda', ['type' => $type, 'error' => $e->getMessage()]);

            return [];
        }

        return array_map(fn ($row) => [
            'id' => (string) $row->id_customer,
            'name' => trim($row->firstname.' '.$row->lastname),
            'email' => $row->email,
            'meta' => 'PS-#'.$row->id_customer,
        ], $rows);
    }

    /**
     * Busca el cliente en la BD de PrestaShop por email y su id de gestión.
     *
     * @return array{id_customer: int, gestion_id: ?int}|null
     */
    private function lookupInPrestashop(string $email): ?array
    {
        $db = (string) config('helpdeskprestashop.ps_db', 'alvarez_cristia');

        try {
            $row = DB::connection('mysql')->selectOne(
                "SELECT c.id_customer,
                        (SELECT MAX(sp.id_cliente_gestion)
                           FROM `{$db}`.aalv_orders o
                           JOIN `{$db}`.seguimiento_pedidos sp ON sp.id_internet = o.id_order AND sp.id_cliente_gestion > 0
                          WHERE o.id_customer = c.id_customer) AS gestion_id
                   FROM `{$db}`.aalv_customer c
                  WHERE c.deleted = 0 AND c.email = ?
               ORDER BY c.id_customer DESC
                  LIMIT 1",
                [$email]
            );
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: fallo al consultar PrestaShop', ['error' => $e->getMessage()]);

            return null;
        }

        if (! $row || ! $row->id_customer) {
            return null;
        }

        return [
            'id_customer' => (int) $row->id_customer,
            'gestion_id' => $row->gestion_id ? (int) $row->gestion_id : null,
        ];
    }
}
