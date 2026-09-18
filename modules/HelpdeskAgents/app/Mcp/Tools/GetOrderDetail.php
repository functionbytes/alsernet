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
 * Detalle de un pedido concreto: lineas, importes, estado y envio.
 *
 * El pedido se comprueba SIEMPRE contra el cliente del ambito. En PrestaShop
 * eso lo hace el propio servicio, que exige $customerEmail; en el ERP hace
 * falta resolver antes el id del cliente y pedir el pedido bajo el, que es lo
 * que impide leer el pedido de otro con solo cambiar el numero.
 */
#[IsReadOnly]
class GetOrderDetail extends HelpdeskTool
{
    protected string $description = 'Detalle completo de un pedido: lineas, cantidades, importes, estado y datos de envio. Uselo cuando necesite un dato concreto de un pedido ya identificado.';

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()
                ->description('Identificador numerico del pedido.')
                ->required(),
            'source' => $schema->string()
                ->description('Origen del pedido: "tienda" (PrestaShop) o "erp" (gestion). Por defecto "tienda".')
                ->enum(['tienda', 'erp']),
            'customer_email' => $schema->string()
                ->description('Email del cliente. Se ignora cuando la herramienta se invoca desde un ticket.'),
        ];
    }

    protected function run(Request $request): Response
    {
        $orderId = (int) $request->get('order_id');

        if ($orderId <= 0) {
            return Response::error('order_id debe ser un numero positivo.');
        }

        $email = $this->resolveCustomerEmail($request);
        $source = (string) ($request->get('source') ?? 'tienda');

        if ($source === 'erp') {
            return $this->erpOrder($orderId, $email);
        }

        if (! $this->moduleAvailable('HelpdeskPrestashop', PrestashopContextService::class)) {
            return Response::error('La integracion con la tienda no esta disponible.');
        }

        // El servicio filtra por propiedad: un pedido de otro cliente devuelve null.
        $order = app(PrestashopContextService::class)->getOrderDetail($orderId, $email);

        if ($order === null) {
            return Response::error("No se encontro el pedido {$orderId} para este cliente.");
        }

        return Response::json(['source' => 'tienda', 'order' => $order]);
    }

    private function erpOrder(int $orderId, string $email): Response
    {
        if (! $this->moduleAvailable('HelpdeskErp', ErpContextService::class)) {
            return Response::error('La integracion con el ERP no esta disponible.');
        }

        $service = app(ErpContextService::class);
        $context = $service->getCustomerContext($email, $this->context()->customerPhone());

        $erpCustomerId = (int) ($context['customer']['id'] ?? 0);

        if ($erpCustomerId <= 0) {
            return Response::error('Este cliente no esta dado de alta en el ERP.');
        }

        $order = $service->getOrderDetail($erpCustomerId, $orderId);

        if ($order === null) {
            return Response::error("No se encontro el pedido {$orderId} para este cliente en el ERP.");
        }

        return Response::json(['source' => 'erp', 'order' => $order]);
    }
}
