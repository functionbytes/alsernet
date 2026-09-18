<?php

namespace Modules\HelpdeskErp\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\PhoneNormalizerService;

class ErpCustomerLinkerService
{
    public function __construct(
        private readonly ErpContextService $erp,
        private readonly PhoneNormalizerService $phoneNormalizer,
    ) {}

    /**
     * Busca el cliente en el ERP intentando todos los identificadores disponibles
     * (email → teléfono → email de PrestaShop) y guarda el vínculo en
     * helpdesk_customer_external_ids con platform='erp'.
     *
     * Retorna el IDCLIENTE de Oracle, o null si no se encontró coincidencia.
     */
    public function linkCustomer(Customer $customer): ?int
    {
        // Ya vinculado — no volver a consultar el ERP
        $existing = $customer->externalIds->firstWhere('platform', 'erp');
        if ($existing !== null) {
            return (int) $existing->external_id;
        }

        // 1. Buscar por email (descartamos correos anónimos del chat web)
        $email = $customer->email;
        if ($email && ! str_ends_with($email, '@anonymous.local')) {
            $erpId = $this->searchByEmail($email);
            if ($erpId !== null) {
                $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => 'email']);
                Log::info('HelpdeskErp: cliente vinculado por email', ['customer_id' => $customer->id, 'erp_id' => $erpId]);

                return $erpId;
            }
        }

        // 2. Buscar por teléfono (WhatsApp primero, luego teléfono general)
        foreach ([$customer->whatsapp_phone, $customer->phone] as $rawPhone) {
            if ($rawPhone === null) {
                continue;
            }

            $digits = $this->phoneNormalizer->toDigits($rawPhone);
            if ($digits === null) {
                continue;
            }

            $erpId = $this->searchByPhone($digits);
            if ($erpId !== null) {
                $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => 'phone']);
                Log::info('HelpdeskErp: cliente vinculado por teléfono', ['customer_id' => $customer->id, 'erp_id' => $erpId]);

                return $erpId;
            }
        }

        // 3. Buscar por email almacenado en los metadatos del link de PrestaShop
        //    (útil cuando el cliente del chat web es anónimo pero PS tiene su email real)
        $psLink = $customer->externalIds->firstWhere('platform', 'prestashop');
        $psEmail = $psLink?->metadata['email'] ?? null;
        if ($psEmail && $psEmail !== $email) {
            $erpId = $this->searchByEmail($psEmail);
            if ($erpId !== null) {
                $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => 'prestashop_email']);
                Log::info('HelpdeskErp: cliente vinculado por email de PrestaShop', ['customer_id' => $customer->id, 'erp_id' => $erpId]);

                return $erpId;
            }
        }

        return null;
    }

    private function searchByEmail(string $email): ?int
    {
        // searchCustomers() propaga las caídas del manager (conexión o status
        // no-2xx) como excepción; aquí las tratamos como "sin coincidencia" para
        // que linkCustomer() siga probando las demás estrategias en vez de
        // abortar el job entero por un fallo transitorio del ERP.
        try {
            $results = $this->erp->searchCustomers($email, 'email');
        } catch (\Throwable $e) {
            Log::warning('HelpdeskErp: linkCustomer no pudo buscar por email.', ['error' => $e->getMessage()]);

            return null;
        }

        foreach ($results as $r) {
            if (isset($r['email']) && strtolower($r['email']) === strtolower($email)) {
                return (int) $r['id'];
            }
        }

        return null;
    }

    /**
     * Mismo criterio que searchByEmail(): la búsqueda por dígitos del manager
     * es fuzzy (IDCLIENTE / IDTARJETA / CODIGO_INTERNET además de teléfono),
     * así que nunca se vincula results[0] a ciegas. Con varios candidatos el
     * match es ambiguo → no vincular y log; con uno solo, se verifica contra
     * su ficha que el teléfono normalizado realmente coincide.
     */
    private function searchByPhone(string $digits): ?int
    {
        try {
            $results = $this->erp->searchCustomers($digits, 'phone');
        } catch (\Throwable $e) {
            Log::warning('HelpdeskErp: linkCustomer no pudo buscar por teléfono.', ['error' => $e->getMessage()]);

            return null;
        }

        if (! is_array($results) || $results === []) {
            return null;
        }

        if (count($results) > 1) {
            Log::info('HelpdeskErp: linkCustomer — búsqueda por teléfono ambigua, no se vincula.', [
                'candidates' => count($results),
            ]);

            return null;
        }

        $erpId = isset($results[0]['id']) && is_numeric($results[0]['id']) ? (int) $results[0]['id'] : null;

        if ($erpId === null) {
            return null;
        }

        if ($this->erp->customerHasPhone($erpId, $digits) !== true) {
            Log::info('HelpdeskErp: linkCustomer — el candidato no tiene el teléfono buscado, no se vincula.', [
                'erp_id' => $erpId,
            ]);

            return null;
        }

        return $erpId;
    }
}
