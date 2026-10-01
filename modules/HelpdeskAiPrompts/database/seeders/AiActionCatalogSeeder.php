<?php

namespace Modules\HelpdeskAiPrompts\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HelpdeskAiPrompts\Models\AiAction;

/**
 * Catálogo inicial de acciones del asistente IA. Idempotente: firstOrCreate por
 * `key`, de modo que volver a ejecutarlo NO pisa lo que se haya editado desde
 * el panel (activación, descripción, reglas...).
 *
 * - builtin: una por herramienta que ya existe en código (ChatFlowAgentService).
 *   Activas por defecto = el comportamiento actual no cambia. Su descripción de
 *   aquí es informativa; solo sustituye a la del código si el panel marca
 *   config.description_overridden = true.
 * - bridge: acciones del bridge de PrestaShop sobre la lista blanca. Se siembran
 *   INACTIVAS: se activan a mano tras revisarlas (las de escritura envían correos
 *   o crean avisos a nombre del cliente).
 */
class AiActionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->builtins() as $key => [$name, $description]) {
            AiAction::query()->firstOrCreate(['key' => $key], [
                'name' => $name,
                'description' => $description,
                'type' => AiAction::TYPE_BUILTIN,
                'is_active' => true,
                'parameters' => [],
                'config' => ['description_overridden' => false],
            ]);
        }

        foreach ($this->bridgeActions() as $definition) {
            AiAction::query()->firstOrCreate(['key' => $definition['key']], $definition + [
                'type' => AiAction::TYPE_BRIDGE,
                'is_active' => false,
                'channels' => null,
            ]);
        }
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function builtins(): array
    {
        return [
            'ask_customer' => ['Preguntar al cliente con opciones', 'Haz UNA pregunta para concretar lo que necesita el cliente (uso, talla, presupuesto…) con opciones cortas que pulsará como botones, y opcionalmente enlaces a categorías de la tienda. Úsala cuando la petición sea amplia antes de recomendar.'],
            'category_links' => ['Enlaces a categorías', 'Busca categorías de la tienda (con su URL) para ofrecer enlaces "Ver botas de caza"… mientras concretas con el cliente.'],
            'product_search' => ['Buscar productos', 'Busca productos con el buscador de la tienda traduciendo la necesidad del cliente a palabras clave y filtros (marca, categoría, precio, stock, orden).'],
            'product_detail' => ['Ficha de producto', 'Obtiene la ficha de un producto concreto del catálogo por su id para responder dudas sobre él.'],
            'product_variants' => ['Tallas y variantes', 'Consulta tallas, colores y variantes de un producto con su disponibilidad, plazo de entrega y stock en tiendas físicas.'],
            'compare_products' => ['Comparar productos', 'Compara 2 o 3 productos (marca, precio, disponibilidad, plazo, variantes y descripción).'],
            'show_cart' => ['Ver la cesta', 'Muestra lo que el cliente tiene ahora en su cesta de la tienda (productos, cantidades y total).'],
            'add_to_cart' => ['Añadir a la cesta', 'Añade un producto a la cesta del cliente. Solo si el cliente lo ha pedido o confirmado expresamente en su último mensaje.'],
            'lookup_order' => ['Consultar un pedido', 'Consulta el estado de un pedido por su número o referencia (y el email de la compra si el cliente no está identificado): estado, transportista, seguimiento y fechas.'],
            'list_my_orders' => ['Listar mis pedidos', 'Lista los últimos pedidos del cliente identificado (número, fecha, estado, total) para cuando no sabe el número.'],
            'search_help' => ['Buscar en el centro de ayuda', 'Busca información en el centro de ayuda para responder una pregunta del cliente.'],
        ];
    }

    /**
     * Los payloads están contrastados con alsernetbridge (api.php + helpers).
     *
     * @return array<int, array<string, mixed>>
     */
    private function bridgeActions(): array
    {
        $order = [
            'requires_verified' => false,
            'ownership' => 'order_email_pair',
            'confirm' => false,
            'max_per_conversation' => 5,
            'timeout' => 8,
        ];
        $verified = ['ownership' => 'verified', 'requires_verified' => true] + $order;

        return [
            [
                'key' => 'consultar_pedido',
                'name' => 'Consultar el estado de un pedido',
                'description' => 'Consulta un pedido del cliente: estado, transportista y seguimiento, fechas y total. Necesita el número de pedido y, si el cliente no está identificado, el email de la compra. Es el puente para los procedimientos de ChatFlow (lookup_order no es una acción del catálogo).',
                'parameters' => [],
                'config' => ['action' => 'order.detail', 'payload' => ['order_id' => '{{order.id}}']],
                'response' => [
                    'fields' => [
                        'reference', 'state_name', 'created_at', 'expected_date', 'currency', 'totals.total',
                        'tracking.*.carrier_name', 'tracking.*.tracking_number', 'tracking.*.tracking_url', 'tracking.*.date',
                    ],
                    'max_chars' => 1200,
                    'empty_message' => 'No hay información de ese pedido.',
                ],
                'rules' => $order,
            ],
            [
                'key' => 'documentos_pedido',
                'name' => 'Facturas y albaranes de un pedido',
                'description' => 'Indica qué facturas y albaranes tiene emitidos un pedido del cliente (número y fecha de cada uno). No devuelve el PDF ni enlaces de descarga. Necesita el número de pedido.',
                'parameters' => [],
                'config' => ['action' => 'order.documents', 'payload' => ['order_id' => '{{order.id}}']],
                'response' => [
                    'fields' => ['invoices.*.number', 'invoices.*.date', 'delivery_slips.*.number', 'delivery_slips.*.date'],
                    'max_chars' => 1200,
                    'empty_message' => 'El pedido todavía no tiene facturas ni albaranes emitidos.',
                ],
                'rules' => $order,
            ],
            [
                // El bridge solo admite order_conf|shipped|order_customer_comment en
                // order.send_email: NO existe plantilla de factura, así que se ofrece
                // el reenvío de la confirmación del pedido (va siempre al email de la cuenta).
                'key' => 'reenviar_confirmacion_pedido',
                'name' => 'Reenviar la confirmación de un pedido',
                'description' => 'Reenvía al email de la cuenta del cliente el correo de confirmación de uno de sus pedidos. No admite elegir otro destinatario. Pide siempre confirmación expresa al cliente antes de usarla.',
                'parameters' => [],
                'config' => ['action' => 'order.send_email', 'payload' => ['order_id' => '{{order.id}}', 'type' => 'order_conf']],
                'response' => [
                    'fields' => ['sent'],
                    'max_chars' => 300,
                    'empty_message' => 'No se ha podido reenviar el correo.',
                ],
                'rules' => ['confirm' => true, 'max_per_conversation' => 2] + $order,
            ],
            [
                'key' => 'estado_devoluciones',
                'name' => 'Estado de las devoluciones del cliente',
                'description' => 'Lista las devoluciones del cliente identificado: pedido, estado, fechas y productos devueltos.',
                'parameters' => [],
                'config' => ['action' => 'customer.returns', 'payload' => []],
                'response' => [
                    'fields' => [
                        'returns.*.order_reference', 'returns.*.state_name', 'returns.*.created_at', 'returns.*.updated_at',
                        'returns.*.items.*.product_name', 'returns.*.items.*.quantity',
                    ],
                    'max_chars' => 1500,
                    'empty_message' => 'El cliente no tiene devoluciones registradas.',
                ],
                'rules' => $verified,
            ],
            [
                'key' => 'pedido_reembolsable',
                'name' => 'Qué se puede reembolsar de un pedido',
                'description' => 'Muestra, para un pedido del cliente, qué líneas son reembolsables (cantidad reembolsable, ya reembolsada), si está pagado/entregado y el total pagado. Solo informa: no ejecuta reembolsos.',
                'parameters' => [],
                'config' => ['action' => 'refunds.order_refundable', 'payload' => ['order_id' => '{{order.id}}']],
                'response' => [
                    'fields' => [
                        'reference', 'paid', 'delivered', 'currency_iso', 'total_paid', 'already_refunded', 'shipping_refundable',
                        'lines.*.name', 'lines.*.quantity', 'lines.*.refunded', 'lines.*.refundable',
                    ],
                    'max_chars' => 1500,
                    'empty_message' => 'No hay información de reembolsos para ese pedido.',
                ],
                'rules' => $order,
            ],
            [
                'key' => 'cambios_rever',
                'name' => 'Cambios y devoluciones gestionados con REVER',
                'description' => 'Consulta el estado de los cambios (REVER) de un pedido del cliente: pedido de cambio, estado, productos, seguimiento y estado del proceso de devolución.',
                'parameters' => [],
                'config' => ['action' => 'rever.order_exchanges', 'payload' => ['order_id' => '{{order.id}}']],
                'response' => [
                    'fields' => [
                        'role',
                        'exchanges.*.exchange_order.reference', 'exchanges.*.exchange_order.state_name', 'exchanges.*.exchange_order.date',
                        'exchanges.*.exchange_order.lines.*.name', 'exchanges.*.exchange_order.lines.*.quantity',
                        'exchanges.*.exchange_order.tracking.*.carrier', 'exchanges.*.exchange_order.tracking.*.number',
                        'exchanges.*.original_order.reference', 'exchanges.*.process.status',
                        'processes.*.status', 'processes.*.started_at', 'processes.*.finished_at',
                    ],
                    'max_chars' => 1800,
                    'empty_message' => 'Ese pedido no tiene cambios ni procesos de devolución con REVER.',
                ],
                'rules' => $order,
            ],
            [
                // Solo cliente verificado: con un email libre se podría suscribir a un
                // tercero a avisos (spam). El bridge además exige que el cliente exista.
                'key' => 'aviso_stock',
                'name' => 'Avisar cuando vuelva el stock',
                'description' => 'Apunta al cliente identificado a un aviso por email cuando un producto sin stock vuelva a estar disponible. Si el producto tiene tallas/colores, necesita la combinación (product_attribute_id de product_variants). Pide confirmación expresa antes de usarla.',
                'parameters' => [
                    ['name' => 'product_id', 'type' => 'integer', 'description' => 'Id del producto (de product_search)', 'required' => true],
                    ['name' => 'product_attribute_id', 'type' => 'integer', 'description' => 'Id de la combinación (talla/color) elegida; omítelo si el producto no tiene variantes', 'required' => false],
                ],
                'config' => [
                    'action' => 'catalog.stock_alert',
                    'payload' => ['product_id' => '{{args.product_id}}', 'product_attribute_id' => '{{args.product_attribute_id|0}}'],
                ],
                'response' => [
                    'fields' => ['subscribed', 'already', 'error'],
                    'max_chars' => 300,
                    'empty_message' => 'No se ha podido crear el aviso.',
                ],
                'rules' => ['confirm' => true, 'max_per_conversation' => 3] + $verified,
            ],
            [
                'key' => 'mis_cupones',
                'name' => 'Cupones personales del cliente',
                'description' => 'Lista los cupones personales del cliente identificado (código, descuento, importe mínimo, caducidad y si siguen activos). Nunca incluye cupones de otros clientes.',
                'parameters' => [],
                'config' => ['action' => 'customer.vouchers', 'payload' => []],
                'response' => [
                    'fields' => [
                        'vouchers.*.code', 'vouchers.*.description', 'vouchers.*.reduction_percent', 'vouchers.*.reduction_amount',
                        'vouchers.*.free_shipping', 'vouchers.*.minimum_amount', 'vouchers.*.date_to', 'vouchers.*.active',
                    ],
                    'max_chars' => 1500,
                    'empty_message' => 'El cliente no tiene cupones personales.',
                ],
                'rules' => $verified,
            ],
        ];
    }
}
