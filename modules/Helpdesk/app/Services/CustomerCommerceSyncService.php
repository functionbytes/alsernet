<?php

namespace Modules\Helpdesk\Services;

use App\Helpers\PiiMasker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpCustomerLinkerService;
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
            // El cliente no tiene cuenta en la tienda online (p. ej. llego por
            // WhatsApp/tienda fisica) — antes esto rendia "sin vincular" aunque
            // el cliente existiera en el ERP directo. Fallback: intentar el
            // mismo resolver que ya usa el job en background al crear una
            // conversacion (email -> telefono -> email de PrestaShop).
            return $this->syncErpOnly($customer, $email);
        }

        try {
            $customer->linkExternalId('prestashop', (string) $match['id_customer'], ['linked_via' => 'auto']);

            if ($match['gestion_id']) {
                $customer->linkExternalId('erp', (string) $match['gestion_id'], ['linked_via' => 'auto']);
            }
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: no se pudo enlazar', ['email' => PiiMasker::email($email), 'error' => $e->getMessage()]);
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
     * Fallback cuando el cliente no aparece en PrestaShop: intenta vincularlo
     * directo al ERP (email -> telefono -> email de PrestaShop). Se degrada a
     * "no vinculado" sin lanzar si el modulo HelpdeskErp no esta instalado.
     *
     * @return array{prestashop_id: ?int, gestion_id: ?int, linked: bool}
     */
    private function syncErpOnly(Customer $customer, string $email): array
    {
        $existingErpId = $customer->externalIdFor('erp');

        if ($existingErpId) {
            return ['prestashop_id' => null, 'gestion_id' => (int) $existingErpId, 'linked' => true];
        }

        if (! class_exists(ErpCustomerLinkerService::class)) {
            return ['prestashop_id' => null, 'gestion_id' => null, 'linked' => false];
        }

        try {
            $erpId = app(ErpCustomerLinkerService::class)->linkCustomer($customer);
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: fallo el fallback a ERP directo', ['email' => PiiMasker::email($email), 'error' => $e->getMessage()]);

            return ['prestashop_id' => null, 'gestion_id' => null, 'linked' => false];
        }

        return ['prestashop_id' => null, 'gestion_id' => $erpId, 'linked' => $erpId !== null];
    }

    /**
     * Busca clientes en PrestaShop por email, ID numérico o nombre.
     * Los fallos de conexión se degradan a lista vacía (con log); si el
     * caller necesita distinguir "sin resultados" de "PrestaShop caído",
     * usar searchCustomersOrFail().
     *
     * @return list<array{id: string, name: string, email: string, meta: string}>
     */
    public function searchCustomers(string $query, string $type = 'email'): array
    {
        try {
            return $this->searchCustomersOrFail($query, $type);
        } catch (\Throwable $e) {
            Log::warning('CustomerCommerceSync: fallo en búsqueda', ['type' => $type, 'error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Variante que propaga el fallo de la BD de PrestaShop en lugar de
     * degradarlo a [] — la usa el driver de integraciones para poder mostrar
     * "la plataforma no respondió" en vez de un falso "sin resultados".
     *
     * @return list<array{id: string, name: string, email: string, meta: string}>
     *
     * @throws \Throwable
     */
    public function searchCustomersOrFail(string $query, string $type = 'email'): array
    {
        $query = trim($query);

        if (strlen($query) < 2) {
            return [];
        }

        // 'auto': el modal de búsqueda externa ya no obliga a elegir "Buscar
        // por" — se infiere el tipo por el contenido, igual que ya hace el
        // manager Oracle del lado ERP (ver ErpIntegrationDriver::search()).
        if ($type === 'auto') {
            $type = match (true) {
                str_contains($query, '@') => 'email',
                ctype_digit($query) => 'id',
                default => 'name_or_nif',
            };
        }

        // Conexión dedicada 'prestashop' (servidor remoto, ver config/DB_*_PRESTASHOP
        // en .env) — antes usaba la conexión local 'mysql' con sintaxis de
        // base de datos cruzada `{db}`.tabla, que solo funciona si ambas bases
        // viven en el mismo servidor MySQL. No es el caso: PrestaShop está en
        // un host aparte (213.134.40.101), así que esa consulta fallaba siempre
        // con "Incorrect database name" en cuanto HELPDESK_PS_DB estaba vacío
        // (y aunque no lo estuviera, jamás iba a alcanzar la base remota).
        $ps = DB::connection('prestashop');

        // Dirección más reciente del cliente (subquery determinista por
        // id_address, evita el problema de ONLY_FULL_GROUP_BY de un JOIN +
        // GROUP BY simple) — de ahí salen DNI/teléfono/ciudad, que no viven
        // en aalv_customer.
        $addressJoin = 'LEFT JOIN aalv_address a
                           ON a.id_address = (
                                SELECT a2.id_address FROM aalv_address a2
                                 WHERE a2.id_customer = c.id_customer AND a2.deleted = 0
                                 ORDER BY a2.date_add DESC LIMIT 1
                           )';
        $cols = 'c.id_customer, c.firstname, c.lastname, c.email, c.date_add, c.active, '.
                'a.dni, a.phone, a.phone_mobile, a.city';

        $rows = match ($type) {
            'id' => $ps->select(
                "SELECT {$cols}
                   FROM aalv_customer c
                   {$addressJoin}
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1 AND c.id_customer = ?
                  LIMIT 5",
                [(int) $query]
            ),
            'name' => $ps->select(
                "SELECT {$cols}
                   FROM aalv_customer c
                   {$addressJoin}
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1
                    AND CONCAT(c.firstname, ' ', c.lastname) LIKE ?
                  ORDER BY c.id_customer DESC
                  LIMIT 10",
                [$query.'%']
            ),
            'nif' => $ps->select(
                "SELECT {$cols}
                   FROM aalv_customer c
                   {$addressJoin}
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1 AND a.dni = ?
                  ORDER BY c.id_customer DESC
                  LIMIT 10",
                [$query]
            ),
            // Texto que no es email ni solo dígitos (búsqueda "auto"): puede
            // ser un NIF/DNI o un nombre, no hay forma de saberlo de antemano
            // — se prueban ambos y se combinan resultados.
            'name_or_nif' => $ps->select(
                "SELECT {$cols}
                   FROM aalv_customer c
                   {$addressJoin}
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1
                    AND (a.dni = ? OR CONCAT(c.firstname, ' ', c.lastname) LIKE ?)
                  ORDER BY c.id_customer DESC
                  LIMIT 10",
                [$query, $query.'%']
            ),
            default => $ps->select(
                "SELECT {$cols}
                   FROM aalv_customer c
                   {$addressJoin}
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1 AND c.email = ?
                  ORDER BY c.id_customer DESC
                  LIMIT 10",
                [$query]
            ),
        };

        return array_map(fn ($row) => [
            'id' => (string) $row->id_customer,
            'name' => trim($row->firstname.' '.$row->lastname),
            'email' => $row->email,
            'meta' => 'PS-#'.$row->id_customer,
            'nif' => $row->dni ?: null,
            'phone' => $row->phone ?: $row->phone_mobile ?: null,
            'city' => $row->city ?: null,
            'active' => (bool) $row->active,
            'created_at' => $row->date_add,
        ], $rows);
    }

    /**
     * Busca el cliente en la BD de PrestaShop por email y su id de gestión.
     *
     * @return array{id_customer: int, gestion_id: ?int}|null
     */
    private function lookupInPrestashop(string $email): ?array
    {
        try {
            $row = DB::connection('prestashop')->selectOne(
                'SELECT c.id_customer,
                        (SELECT MAX(sp.id_cliente_gestion)
                           FROM aalv_orders o
                           JOIN seguimiento_pedidos sp ON sp.id_internet = o.id_order AND sp.id_cliente_gestion > 0
                          WHERE o.id_customer = c.id_customer) AS gestion_id
                   FROM aalv_customer c
                  WHERE c.deleted = 0 AND c.is_guest = 0 AND c.active = 1 AND c.email = ?
               ORDER BY c.id_customer DESC
                  LIMIT 1',
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
