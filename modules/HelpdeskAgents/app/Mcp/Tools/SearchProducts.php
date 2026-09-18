<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Modules\HelpdeskPrestashop\Services\PrestashopContextService;

/**
 * Catalogo de la tienda: busqueda por texto, referencia o los productos que el
 * cliente ya ha comprado.
 *
 * A diferencia del resto de tools, el catalogo NO es un dato privado del
 * cliente, asi que la busqueda libre si esta disponible tambien en modo
 * bloqueado — es la misma informacion que hay en la web publica.
 */
#[IsReadOnly]
class SearchProducts extends HelpdeskTool
{
    protected string $description = 'Busca productos en el catalogo de la tienda por texto o referencia, o lista los que este cliente ya ha comprado. Uselo para confirmar disponibilidad, precio o referencia de un articulo antes de mencionarlo en la respuesta.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Texto o referencia a buscar. Dejelo vacio junto con from_history=true para listar lo ya comprado por el cliente.'),
            'from_history' => $schema->boolean()
                ->description('Devolver los productos del historial de compra del cliente en lugar de buscar en el catalogo.'),
            'in_stock_only' => $schema->boolean()
                ->description('Restringir a articulos con existencias.'),
            'limit' => $schema->integer()
                ->description('Numero maximo de productos (1-15, por defecto 8).'),
        ];
    }

    protected function run(Request $request): Response
    {
        if (! $this->moduleAvailable('HelpdeskPrestashop', PrestashopContextService::class)) {
            return Response::error('La integracion con la tienda no esta disponible.');
        }

        $service = app(PrestashopContextService::class);
        $limit = max(1, min(15, (int) ($request->get('limit') ?? 8)));
        $query = trim((string) ($request->get('query') ?? ''));

        if ($request->get('from_history') === true || $query === '') {
            $email = $this->resolveCustomerEmail($request);

            return Response::json([
                'mode' => 'historial',
                'products' => $this->limitRows($service->getProductsFromHistory($email, $limit), $limit),
            ]);
        }

        $products = $service->searchProducts(
            $query,
            $limit,
            null,
            0,
            $request->get('in_stock_only') === true,
        );

        return Response::json([
            'mode' => 'catalogo',
            'query' => $query,
            'products' => $this->limitRows($products, $limit),
        ]);
    }
}
