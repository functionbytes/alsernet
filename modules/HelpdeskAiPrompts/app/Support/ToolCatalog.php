<?php

namespace Modules\HelpdeskAiPrompts\Support;

use Modules\HelpdeskAiPrompts\Models\AiAction;

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

    /**
     * Herramientas que un caso puede permitir: las integradas (con la
     * descripción redefinida en el panel si la hay) más las acciones a medida
     * activas. `$alsoInclude` mantiene visibles las ya elegidas aunque su
     * acción esté ahora desactivada, para no perderlas al guardar el caso.
     *
     * @param  array<int, string>  $alsoInclude
     * @return array<string, string> clave => descripción
     */
    public static function catalog(array $alsoInclude = []): array
    {
        $tools = self::TOOLS;
        $custom = [];

        foreach (AiAction::query()->orderBy('key')->get(['key', 'description', 'type', 'is_active', 'config']) as $action) {
            if ($action->type === AiAction::TYPE_BUILTIN) {
                $tools = self::withBuiltinOverride($tools, $action);

                continue;
            }

            if ($action->is_active || in_array($action->key, $alsoInclude, true)) {
                $custom[$action->key] = $action->is_active
                    ? $action->description
                    : $action->description.' '.__('helpdeskaiprompts::ai-prompts.actions.tool_inactive');
            }
        }

        return $tools + $custom;
    }

    /**
     * Claves aceptadas al guardar un caso: integradas y cualquier acción a
     * medida (activa o no; una desactivada no se ofrece pero tampoco rompe un
     * caso que ya la tenía).
     *
     * @return array<int, string>
     */
    public static function validKeys(): array
    {
        $custom = AiAction::query()
            ->where('type', '!=', AiAction::TYPE_BUILTIN)
            ->pluck('key')
            ->all();

        return array_values(array_unique([...self::keys(), ...$custom]));
    }

    /**
     * @param  array<string, string>  $tools
     * @return array<string, string>
     */
    private static function withBuiltinOverride(array $tools, AiAction $action): array
    {
        if (! isset($tools[$action->key])) {
            return $tools;
        }

        if (! empty($action->config['description_overridden']) && trim((string) $action->description) !== '') {
            $tools[$action->key] = $action->description;
        }

        if (! $action->is_active) {
            $tools[$action->key] .= ' '.__('helpdeskaiprompts::ai-prompts.actions.tool_inactive');
        }

        return $tools;
    }
}
