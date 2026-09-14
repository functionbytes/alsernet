<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Pedidos de la tienda. Dos modos segun el ambito:
 *
 *  - Bloqueado a un ticket: lista los pedidos de ESE cliente (paginados).
 *  - Abierto (servidor MCP, persona autenticada): permite ademas buscar por
 *    referencia o texto libre con searchOrders().
 *
 * La busqueda libre no se ofrece en modo bloqueado a proposito: un ticket cuyo
 * texto pida "busca el pedido XYZ" no debe poder listar pedidos de terceros.
 */
#[IsReadOnly]
class SearchOrders extends HelpdeskTool
{
    protected string $description = 'Lista los pedidos de la tienda del cliente, del mas reciente al mas antiguo. Uselo cuando el cliente pregunte por el estado, el contenido o la fecha de un pedido.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_email' => $schema->string()
                ->description('Email del cliente. Se ignora cuando la herramienta se invoca desde un ticket.'),
            'query' => $schema->string()
                ->description('Referencia o texto a buscar. Solo disponible fuera del contexto de un ticket.'),
            'limit' => $schema->integer()
                ->description('Numero maximo de pedidos a devolver (1-20, por defecto 10).'),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->moduleAvailable('HelpdeskPrestashop', PrestashopContextService::class)) {
            return Response::error('La integracion con la tienda no esta disponible.');
        }

        $service = app(PrestashopContextService::class);
        $limit = max(1, min(20, (int) ($request->get('limit') ?? 10)));
        $query = trim((string) ($request->get('query') ?? ''));

        if ($query !== '' && ! $this->context()->isLocked()) {
            return Response::json([
                'mode' => 'search',
                'query' => $query,
                'orders' => $this->limitRows($service->searchOrders($query, $limit), $limit),
            ]);
        }

        $email = $this->resolveCustomerEmail($request);
        $orders = $service->getCustomerOrders($email, null, $limit);

        return Response::json([
            'mode' => 'customer',
            'customer_email' => $email,
            'orders' => $this->limitRows($orders['orders'] ?? $orders ?? [], $limit),
        ]);
    }
}
