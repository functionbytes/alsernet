<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskErp\Services\ErpContextService;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Ficha comercial del cliente: datos de gestion (ERP) y de tienda
 * (PrestaShop), con sus ultimos pedidos y facturas.
 *
 * Es la primera tool que deberia usar el modelo: responde "quien es este
 * cliente y que ha comprado" de una sola llamada, evitando encadenar tres
 * busquedas para lo mismo.
 */
#[IsReadOnly]
class GetCustomerContext extends HelpdeskTool
{
    protected string $description = 'Ficha del cliente en el ERP y en la tienda: datos fiscales, saldo pendiente, limite de credito y sus ultimos pedidos y facturas. Uselo antes que ninguna otra herramienta para saber con quien esta hablando.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_email' => $schema->string()
                ->description('Email del cliente. Se ignora cuando la herramienta se invoca desde un ticket: en ese caso el cliente es siempre el del ticket.'),
            'include_store' => $schema->boolean()
                ->description('Incluir tambien el contexto de la tienda PrestaShop. Por defecto true.'),
        ];
    }

    protected function run(Request $request): Response
    {
        $email = $this->resolveCustomerEmail($request);
        $includeStore = $request->get('include_store') !== false;

        $payload = ['customer_email' => $email];

        if ($this->moduleAvailable('HelpdeskErp', ErpContextService::class)) {
            $erp = app(ErpContextService::class)->getCustomerContext(
                $email,
                $this->context()->customerPhone(),
                $this->context()->customer()?->id,
            );

            $payload['erp'] = [
                'found' => (bool) ($erp['customer']['found'] ?? false),
                'customer' => $erp['customer'] ?? null,
                'orders' => $this->limitRows($erp['orders'] ?? [], 10),
                'invoices' => $this->limitRows($erp['invoices'] ?? [], 10),
            ];
        } else {
            $payload['erp'] = ['available' => false];
        }

        if ($includeStore && $this->moduleAvailable('HelpdeskPrestashop', PrestashopContextService::class)) {
            $store = app(PrestashopContextService::class)->getCustomerContext($email);

            $payload['tienda'] = [
                'found' => (bool) ($store['customer']['found'] ?? ! empty($store['customer'])),
                'customer' => $store['customer'] ?? null,
                'orders' => $this->limitRows($store['orders'] ?? [], 10),
            ];
        } elseif ($includeStore) {
            $payload['tienda'] = ['available' => false];
        }

        return Response::json($payload);
    }
}
