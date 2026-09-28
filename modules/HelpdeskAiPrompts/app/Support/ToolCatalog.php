<?php

namespace Modules\HelpdeskAiPrompts\Support;

/**
 * Catálogo de herramientas que un caso puede permitir al agente IA
 * (ChatFlowAgentService). Vive aquí, no en AiPromptCase, porque es un
 * detalle de presentación/validación del panel, no del modelo de datos:
 * el propio agente decide qué hacer con cada nombre de herramienta.
 */
class ToolCatalog
{
    public const TOOLS = [
        'answer_customer' => 'Responder directamente al cliente, sin usar ninguna herramienta.',
        'escalate_to_agent' => 'Derivar la conversación a un agente humano.',
        'product_search' => 'Buscar productos en el catálogo por palabras clave.',
        'product_detail' => 'Ampliar la ficha de un producto ya localizado.',
        'product_variants' => 'Consultar tallas, colores y stock de un producto.',
        'compare_products' => 'Comparar dos o tres productos entre sí.',
        'show_cart' => 'Mostrar el contenido actual del carrito del cliente.',
        'add_to_cart' => 'Añadir un producto (talla/color/cantidad) al carrito.',
        'lookup_order' => 'Buscar un pedido concreto por número de pedido y email.',
        'list_my_orders' => 'Listar los últimos pedidos del cliente identificado.',
        'search_help' => 'Buscar en el centro de ayuda / preguntas frecuentes.',
        'ask_customer' => 'Preguntar con opciones.',
        'category_links' => 'Enlaces a categorías.',
    ];

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::TOOLS);
    }
}
