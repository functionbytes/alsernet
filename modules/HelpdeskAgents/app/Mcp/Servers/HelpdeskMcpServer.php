<?php

namespace Modules\HelpdeskAgents\Mcp\Servers;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Tool;
use Modules\HelpdeskAgents\Mcp\Tools\GetContact360;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerContext;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerTimeline;
use Modules\HelpdeskAgents\Mcp\Tools\GetOrderDetail;
use Modules\HelpdeskAgents\Mcp\Tools\GetTicketHistory;
use Modules\HelpdeskAgents\Mcp\Tools\ListReplyTemplates;
use Modules\HelpdeskAgents\Mcp\Tools\SearchKnowledge;
use Modules\HelpdeskAgents\Mcp\Tools\SearchOrders;
use Modules\HelpdeskAgents\Mcp\Tools\SearchProducts;

/**
 * Servidor MCP del helpdesk.
 *
 * Expone a un cliente MCP externo (el Claude Desktop / Claude Code del equipo)
 * las mismas herramientas que consume internamente el sugeridor de respuestas.
 * Una sola definicion por herramienta: lo que se anada a $tools aparece en los
 * dos sitios.
 *
 * SEGURIDAD. Estas tools sirven datos de clientes reales — pedidos del ERP
 * Oracle de produccion y de la tienda viva. Dos condiciones, ambas aplicadas
 * en routes/ai.php y no aqui:
 *
 *  - Va autenticado y detras del permiso `helpdesk.ai.use`.
 *  - NO se publica fuera de la red interna. Por esa misma razon el sugeridor
 *    de respuestas no usa el conector MCP remoto del proveedor (que exigiria
 *    exponer esta ruta a internet) sino que ejecuta las tools dentro de la
 *    aplicacion via McpToolBridge.
 *
 * Aqui el contexto NUNCA esta bloqueado a un ticket: quien invoca es una
 * persona autenticada, asi que puede consultar por email como haria desde el
 * buscador del panel. El modo bloqueado es cosa del camino interno. Ver
 * McpToolContext.
 */
class HelpdeskMcpServer extends Server
{
    protected string $name = 'Helpdesk Alsernet';

    protected string $version = '1.0.0';

    protected string $instructions = <<<'MARKDOWN'
    Acceso de solo lectura a los datos de soporte del helpdesk: clientes,
    pedidos del ERP y de la tienda, historial de tickets, catalogo de productos
    y plantillas de respuesta aprobadas.

    Todas las herramientas son de consulta. Ninguna modifica pedidos, tickets ni
    datos de cliente: cambiar el estado de un pedido, abrir una devolucion o
    responder a un cliente se hace desde el panel, no desde aqui.

    Salvo que se indique otra cosa, las herramientas necesitan `customer_email`
    para saber sobre quien consultar. Empiece por `get-customer-context`: de una
    sola llamada devuelve quien es el cliente y sus ultimos pedidos.
    MARKDOWN;

    /**
     * @var array<int, class-string<Tool>>
     */
    protected array $tools = [
        GetCustomerContext::class,
        GetContact360::class,
        SearchOrders::class,
        GetOrderDetail::class,
        GetCustomerTimeline::class,
        SearchProducts::class,
        GetTicketHistory::class,
        SearchKnowledge::class,
        ListReplyTemplates::class,
    ];
}
