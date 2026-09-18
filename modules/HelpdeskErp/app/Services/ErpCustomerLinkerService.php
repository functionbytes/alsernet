<?php

namespace Modules\HelpdeskErp\Services;

use Illuminate\Support\Facades\Log;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\PhoneNormalizerService;
use Modules\HelpdeskIntegration\Services\CustomerIntegrationService;

class ErpCustomerLinkerService
{
    /**
     * ¿Alguna de las búsquedas de esta pasada murió por un fallo del manager
     * (timeout, conexión, 5xx)? Distingue "el cliente no está en el ERP" de
     * "el ERP no contestó": el primero es definitivo y el segundo merece que
     * el job lo reintente más adelante.
     */
    private bool $searchFailed = false;

    /**
     * Ficha de Gestión que produjo la coincidencia (la guarda searchByEmail/
     * searchByPhone) para completar el contacto al vincular.
     *
     * @var array<string, mixed>|null
     */
    private ?array $matchedRecord = null;

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

        $this->searchFailed = false;
        $this->matchedRecord = null;

        // 1. Buscar por email (descartamos correos anónimos del chat web)
        $email = $customer->email;
        if ($email && ! str_ends_with($email, '@anonymous.local')) {
            $erpId = $this->searchByEmail($email);
            if ($erpId !== null) {
                return $this->persistLink($customer, $erpId, 'email');
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
                return $this->persistLink($customer, $erpId, 'phone');
            }
        }

        // 3. Buscar por email almacenado en los metadatos del link de PrestaShop
        //    (útil cuando el cliente del chat web es anónimo pero PS tiene su email real)
        $psLink = $customer->externalIds->firstWhere('platform', 'prestashop');
        $psEmail = $psLink?->metadata['email'] ?? null;
        if ($psEmail && $psEmail !== $email) {
            $erpId = $this->searchByEmail($psEmail);
            if ($erpId !== null) {
                return $this->persistLink($customer, $erpId, 'prestashop_email');
            }
        }

        // Ninguna estrategia encontró al cliente. Antes esto se iba en un
        // Log::info y no quedaba nada consultable; ahora se guarda para que la
        // UI pueda avisar al agente y el job sepa que ya se intentó. Se
        // distingue "no está en el ERP" de "el ERP no contestó": lo primero es
        // definitivo, lo segundo merece reintento.
        $status = $this->searchFailed ? 'error' : 'not_found';

        $customer->recordErpLookup($status);
        $this->auditFailedLookup($customer, $status);

        Log::info('HelpdeskErp: cliente no vinculado', [
            'customer_id' => $customer->id,
            'status' => $status,
        ]);

        return null;
    }

    /**
     * Escribe el vínculo pasando por CustomerIntegrationService cuando está
     * disponible, para que los vínculos automáticos aparezcan en el mismo
     * historial de integraciones que los que hace un agente a mano — hasta
     * ahora solo se auditaban estos últimos.
     *
     * HelpdeskIntegration es opcional (HelpdeskErp no depende de él; la
     * dependencia va en el otro sentido, su ErpIntegrationDriver usa
     * ErpContextService), así que sin él se escribe igual, solo sin auditar.
     */
    private function persistLink(Customer $customer, int $erpId, string $via): int
    {
        if (class_exists(CustomerIntegrationService::class)) {
            app(CustomerIntegrationService::class)
                ->linkAutomatically($customer, 'erp', (string) $erpId, $via);
        } else {
            $customer->linkExternalId('erp', (string) $erpId, ['linked_via' => $via]);
        }

        $customer->recordErpLookup('linked');

        $this->fillProfileFromErp($customer);

        // Invalidar el contexto cacheado de este cliente.
        //
        // getCustomerContext() cachea también los negativos (miss_ttl, 60 s por
        // defecto). Si algo preguntó por este email mientras aún no estaba
        // vinculado —o la consulta anterior murió por timeout de Oracle— queda
        // un "found: false" en caché, y quien lo lea justo después verá un
        // cliente sin ficha aunque el vínculo acabe de escribirse. Eso rompía
        // en concreto a ErpFactsService: CustomerErpResolved se emite un
        // instante después de este método, así que las reglas de enrutado
        // evaluaban erp_linked=false para un cliente que sí está en gestión.
        $this->forgetContextCache($customer);

        Log::info('HelpdeskErp: cliente vinculado', [
            'customer_id' => $customer->id,
            'erp_id' => $erpId,
            'via' => $via,
        ]);

        return $erpId;
    }

    /**
     * Tira el contexto cacheado del cliente por todas sus identidades: el
     * email y los teléfonos, porque la clave de caché usa el email si lo hay y
     * `phone:{phone}` cuando el cliente se encontró solo por número.
     */
    private function forgetContextCache(Customer $customer): void
    {
        $email = (string) ($customer->email ?? '');

        $this->erp->forgetAllFor($email, [
            $customer->whatsapp_phone,
            $customer->phone,
        ]);
    }

    /**
     * @param  'not_found'|'error'  $status
     */
    private function auditFailedLookup(Customer $customer, string $status): void
    {
        if (! class_exists(CustomerIntegrationService::class)) {
            return;
        }

        app(CustomerIntegrationService::class)->logFailedLookup($customer, 'erp', $status);
    }

    /**
     * Completa el contacto con la ficha de Gestión al vincularlo: el nombre de
     * Gestión reemplaza al del perfil de WhatsApp/Messenger, y el email se
     * rellena solo si el contacto no tiene uno. Si ese email ya es de otro
     * contacto (índice único, incluye borrados) no se toca: es señal de un
     * duplicado a fusionar a mano, no algo que se pueda resolver aquí.
     */
    private function fillProfileFromErp(Customer $customer): void
    {
        $record = $this->matchedRecord;

        if ($record === null) {
            return;
        }

        $updates = [];

        $name = trim(($record['label'] ?? '').' '.($record['surnames'] ?? ''));
        if ($name !== '' && $name !== $customer->name) {
            $updates['name'] = $name;
        }

        $email = $record['email'] ?? null;
        if (blank($customer->email) && is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $taken = Customer::withTrashed()
                ->where('email', $email)
                ->whereKeyNot($customer->getKey())
                ->exists();

            if ($taken) {
                Log::info('HelpdeskErp: el email de Gestión ya pertenece a otro contacto, no se copia.', [
                    'customer_id' => $customer->id,
                ]);
            } else {
                $updates['email'] = $email;
            }
        }

        if ($updates !== []) {
            $customer->update($updates);
        }
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
            $this->searchFailed = true;
            Log::warning('HelpdeskErp: linkCustomer no pudo buscar por email.', ['error' => $e->getMessage()]);

            return null;
        }

        foreach ($results as $r) {
            if (isset($r['email']) && strtolower($r['email']) === strtolower($email)) {
                $this->matchedRecord = $r;

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
            $this->searchFailed = true;
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

        $this->matchedRecord = $results[0];

        return $erpId;
    }
}
