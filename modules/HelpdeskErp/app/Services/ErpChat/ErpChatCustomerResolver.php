<?php

namespace Modules\HelpdeskErp\Services\ErpChat;

use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskErp\Services\ErpContextService;

/**
 * Traduce el cliente del helpdesk a su IDCLIENTE de Gestión. El navegador
 * nunca manda el id ERP: se resuelve aquí, en servidor.
 *
 * 1. Vínculo guardado (helpdesk_customer_external_ids, platform = 'erp'),
 *    que escriben ErpCustomerLinkerService y ErpContextService.
 * 2. Si no hay vínculo, el contexto ERP ya cacheado por email (lectura pura
 *    de caché: nunca lanza la búsqueda por email, que tarda ~12 s en Oracle).
 *
 * Los ids no numéricos (restos de importaciones, p. ej. "C004004") no son
 * IDCLIENTE válidos y se ignoran.
 */
class ErpChatCustomerResolver
{
    public function __construct(
        private readonly ErpContextService $context,
    ) {}

    public function erpIdFor(Customer $customer): ?int
    {
        $ids = $customer->externalIds()
            ->where('platform', 'erp')
            ->orderBy('id')
            ->pluck('external_id');

        foreach ($ids as $id) {
            if ($this->isValidId($id)) {
                return (int) $id;
            }
        }

        $email = trim((string) $customer->email);

        if ($email === '' || str_ends_with($email, '@anonymous.local')) {
            return null;
        }

        try {
            $cached = $this->context->peekCachedContext($email);
        } catch (\Throwable) {
            return null;
        }

        $id = $cached['customer']['id'] ?? null;

        if (($cached['customer']['found'] ?? false) && $this->isValidId($id)) {
            return (int) $id;
        }

        return null;
    }

    private function isValidId(mixed $id): bool
    {
        return (is_int($id) || (is_string($id) && ctype_digit($id))) && (int) $id > 0;
    }
}
