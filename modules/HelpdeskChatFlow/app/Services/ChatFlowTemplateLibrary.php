<?php

namespace Modules\HelpdeskChatFlow\Services;

/**
 * Pre-built chat flow templates so users can start from a working flow
 * instead of a blank canvas. Each template returns a normalized node tree
 * ({id, type, parentId, label, data}) the editor can render directly.
 */
class ChatFlowTemplateLibrary
{
    private const SHOPPING_ASSISTANT_INSTRUCTIONS = <<<'TXT'
Eres el asistente virtual de Álvarez (tienda de caza, pesca, golf, hípica, náutica, buceo, esquí, pádel y aventura), gestionado por inteligencia artificial. Tono cercano y profesional, respuestas breves (2-5 frases), con **negrita** para lo importante y listas cortas cuando ayuden.

Productos
- Si el cliente está viendo un producto (CONTEXTO_VISITANTE), "este/esta" se refiere a ese producto.
- Tallas, colores o variantes: consulta product_variants antes de responder; di si hay stock de la opción que pide y el plazo de entrega si lo tienes. Nunca inventes stock ni plazos.
- Para recomendar busca con product_search (palabras clave, no frases). Para comparar usa compare_products con los ids.
- Precios: los muestra la tarjeta del producto; no los recalcules.
- Añade a la cesta solo si el cliente lo pide o lo confirma, con la talla/variante elegida.

Pedidos
- Si el cliente está identificado (sesión iniciada), usa list_my_orders / lookup_order directamente.
- Si no, pide el número o la referencia del pedido y el email de la compra, y usa lookup_order con ambos. Si no coincide, dilo sin dar detalles del pedido.
- Da estado, transportista, número y enlace de seguimiento y la fecha prevista si existen. Nunca des direcciones ni datos de pago.

Políticas de la tienda (publicadas en la web)
- Entrega: la mayoría de pedidos en 48 horas; plazo general aproximado de 7 días laborables (productos bajo pedido, personalizados o artesanales pueden tardar más). Entrega a domicilio u oficina de Correos (no apartados de correos; algunos productos, como armas o armeros, no admiten Correos).
- Envío gratis a partir de 99 € (península, salvo excepciones).
- Cambios y devoluciones: 15 días naturales desde la recepción, producto en perfecto estado y con su embalaje. Si no es por causa de Álvarez, los gastos los asume el cliente; Álvarez puede gestionar la recogida por 4,99 € (España peninsular, productos estándar). Ropa y calzado marcados con "devolución gratuita" se devuelven gratis. Defectuosos o envíos erróneos: gastos a cargo de Álvarez si se comunica al recibir el pedido. Gestión: https://returns.itsrever.com/alvarez
- Pago: tarjeta, Google Pay, Apple Pay, contra reembolso (salvo algunos productos) y pago a plazos con SeQura.
- Teléfono 981 17 91 00 · web@a-alvarez.com · tiendas en https://www.a-alvarez.com/tiendas

Límites
- Armas, licencias, munición y temas legales: informa con prudencia y deriva a un agente.
- Si no sabes algo o el cliente pide una persona, usa escalate_to_agent. No inventes datos.
TXT;

    /**
     * @return array<int, array{key: string, name: string, description: string, icon: string, color: string, trigger_type: string}>
     */
    public function all(): array
    {
        return [
            [
                'key' => 'shopping_assistant',
                'name' => 'Asistente de compras (IA)',
                'description' => 'Agente IA que contesta desde el primer mensaje: productos, tallas y stock, comparativas, plazos de entrega, estado de pedido y políticas de la tienda. Añade a la cesta y pasa a un agente si no puede resolver.',
                'icon' => 'fas fa-bag-shopping',
                'color' => '#90bb13',
                'trigger_type' => 'conversation_start',
            ],
            [
                'key' => 'faq_ai',
                'name' => 'FAQ con IA',
                'description' => 'El bot responde preguntas usando tu centro de conocimiento y, si no resuelve, transfiere a un agente.',
                'icon' => 'fas fa-robot',
                'color' => '#a855f7',
                'trigger_type' => 'conversation_start',
            ],
            [
                'key' => 'order_status',
                'name' => 'Estado de pedido',
                'description' => 'Identifica al cliente, pide el número de pedido y consulta su estado real en el ERP/PrestaShop.',
                'icon' => 'fas fa-box',
                'color' => '#0d9488',
                'trigger_type' => 'conversation_start',
            ],
            [
                'key' => 'rma_return',
                'name' => 'Devolución / RMA',
                'description' => 'Flujo de devolución: identifica al cliente, busca el pedido, solicita documentos y deriva a un agente.',
                'icon' => 'fas fa-rotate-left',
                'color' => '#f59e0b',
                'trigger_type' => 'manual',
            ],
            [
                'key' => 'lead_capture',
                'name' => 'Captura de lead',
                'description' => 'Recoge nombre y email del visitante, lo etiqueta como lead y se despide.',
                'icon' => 'fas fa-user-plus',
                'color' => '#3b82f6',
                'trigger_type' => 'conversation_start',
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, trigger_type: string, nodes: array<int, array<string,mixed>>}|null
     */
    public function build(string $key): ?array
    {
        $meta = collect($this->all())->firstWhere('key', $key);

        if (! $meta) {
            return null;
        }

        $nodes = match ($key) {
            'shopping_assistant' => $this->shoppingAssistant(),
            'faq_ai' => $this->faqAi(),
            'order_status' => $this->orderStatus(),
            'rma_return' => $this->rmaReturn(),
            'lead_capture' => $this->leadCapture(),
            default => [],
        };

        return [
            'name' => $meta['name'],
            'description' => $meta['description'],
            'trigger_type' => $meta['trigger_type'],
            'nodes' => $nodes,
        ];
    }

    /**
     * Plantillas de PROCEDIMIENTO (trigger `procedure`): flujos que solo corren
     * cuando otro flujo los llama con `call_flow`. Van aparte de all() porque no
     * se ofrecen en el selector de plantillas del panel; las siembra
     * ProcedureFlowsSeeder.
     *
     * @return array<int, array{key: string, name: string, description: string, icon: string, color: string, trigger_type: string}>
     */
    public function procedures(): array
    {
        return [
            [
                'key' => 'estado_pedido',
                'name' => 'Procedimiento: estado de pedido',
                'description' => 'Pide el número de pedido y el email (si no está identificado), consulta el pedido con la acción consultar_pedido y responde según esté enviado, en preparación o no se encuentre.',
                'icon' => 'fas fa-box',
                'color' => '#0d9488',
                'trigger_type' => 'procedure',
            ],
            [
                'key' => 'devoluciones',
                'name' => 'Procedimiento: devoluciones',
                'description' => 'Comprueba con pedido_reembolsable si el pedido admite devolución, explica la política y enlaza al portal de devoluciones; muestra los cambios existentes (REVER).',
                'icon' => 'fas fa-rotate-left',
                'color' => '#f59e0b',
                'trigger_type' => 'procedure',
            ],
            [
                'key' => 'aviso_stock',
                'name' => 'Procedimiento: aviso de stock',
                'description' => 'Para clientes identificados: confirma y apunta un aviso por email cuando el producto que está viendo vuelva a tener stock.',
                'icon' => 'fas fa-bell',
                'color' => '#3b82f6',
                'trigger_type' => 'procedure',
            ],
        ];
    }

    /**
     * @return array{name: string, description: string, trigger_type: string, nodes: array<int, array<string,mixed>>}|null
     */
    public function buildProcedure(string $key): ?array
    {
        $meta = collect($this->procedures())->firstWhere('key', $key);

        if (! $meta) {
            return null;
        }

        $nodes = match ($key) {
            'estado_pedido' => $this->procedureOrderStatus(),
            'devoluciones' => $this->procedureReturns(),
            'aviso_stock' => $this->procedureStockAlert(),
        };

        return [
            'name' => $meta['name'],
            'description' => $meta['description'],
            'trigger_type' => $meta['trigger_type'],
            'nodes' => $nodes,
        ];
    }

    /**
     * Agente IA en bucle: contesta el mensaje que abrió la conversación
     * (first_message → last_input), espera el siguiente sin volver a
     * preguntar y vuelve al agente. Las políticas de las instrucciones son las
     * publicadas en la web (envíos, cambios y devoluciones, pago): revisarlas
     * al crear el flujo si cambian.
     *
     * @return array<int, array<string,mixed>>
     */
    private function shoppingAssistant(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('agent', 'ai_agent', 'start', 'Asistente IA', [
                'instructions' => self::SHOPPING_ASSISTANT_INSTRUCTIONS,
                'question_variable' => 'last_input',
                'use_memory' => true,
                // Con el módulo HelpdeskAiPrompts el prompt sale del panel (base +
                // caso + conocimiento); estas instrucciones quedan como respaldo.
                'use_prompt_library' => true,
                'append_instructions' => false,
                'tool_products' => true,
                'tool_cart' => true,
                'tool_order_lookup' => true,
                'tool_knowledge' => true,
                'fallback_message' => 'Te paso con una persona del equipo para ayudarte mejor. 🙋',
            ]),
            $this->node('wait', 'collect_input', 'agent', 'Siguiente pregunta', [
                'question' => '',
                'variable_name' => 'last_input',
            ]),
            $this->node('loop', 'go_to_step', 'wait', 'Volver al asistente', [
                'target_node_id' => 'agent',
            ]),
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function faqAi(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('welcome', 'message', 'start', 'Bienvenida', [
                'text' => '¡Hola! 👋 Soy el asistente virtual. ¿En qué puedo ayudarte?',
            ]),
            $this->node('ask', 'collect_input', 'welcome', 'Pregunta del cliente', [
                'question' => 'Escribe tu pregunta y trataré de ayudarte.',
                'variable_name' => 'pregunta',
            ]),
            $this->node('ai', 'ai_response', 'ask', 'Respuesta IA', [
                'instructions' => 'Eres un asistente de atención al cliente. Responde de forma breve, clara y amable en español.',
                'use_knowledge_base' => true,
                'kb_results' => 4,
                'question_variable' => 'pregunta',
                'fallback_message' => 'No encontré la respuesta. Te paso con un agente.',
            ]),
            $this->node('confirm', 'quick_replies', 'ai', '¿Resuelto?', [
                'text' => '¿He resuelto tu duda?',
                'options' => ['Sí, gracias', 'No, hablar con un agente'],
                'variable_name' => 'resuelto',
            ]),
            $this->node('branch', 'branches', 'confirm', 'Condición'),
            $this->node('b_yes', 'branchItem', 'branch', 'Resuelto', [
                'name' => 'Resuelto', 'isElse' => false,
                'conditions' => [['variable' => 'resuelto', 'operator' => '=', 'value' => 'Sí, gracias']],
            ]),
            $this->node('close_yes', 'close', 'b_yes', 'Cerrar', [
                'farewell' => '¡Genial! Gracias por contactarnos. 👋',
            ]),
            $this->node('b_no', 'branchItem', 'branch', 'Else', [
                'name' => 'Else', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('transfer_no', 'transfer', 'b_no', 'Transferir agente', [
                'message' => 'Te paso con un agente que terminará de ayudarte.',
            ]),
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function orderStatus(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('intro', 'message', 'start', 'Bienvenida', [
                'text' => 'Te ayudo a consultar el estado de tu pedido. 📦',
            ]),
            $this->node('identify', 'identify_customer', 'intro', 'Identificar cliente', [
                'question' => 'Para empezar, indícame tu email, teléfono o NIF.',
                'sources' => ['erp', 'ps'],
                'found_message' => '¡Perfecto, {{customer_name}}!',
            ]),
            $this->node('ask_order', 'collect_input', 'identify', 'Número de pedido', [
                'question' => '¿Cuál es tu número de pedido?',
                'variable_name' => 'numero_pedido',
            ]),
            $this->node('lookup', 'order_lookup', 'ask_order', 'Consultar pedido', [
                'order_variable' => 'numero_pedido',
                'source' => 'auto',
                'not_found_message' => 'No encontré ese pedido en tu cuenta. Verifica el número.',
            ]),
            $this->node('end', 'end', 'lookup', 'Fin', [
                'action' => 'close',
                'farewell' => '¿Necesitas algo más?',
            ]),
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function rmaReturn(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('intro', 'message', 'start', 'Bienvenida', [
                'text' => 'Vamos a tramitar tu devolución. 🔄',
            ]),
            $this->node('identify', 'identify_customer', 'intro', 'Identificar cliente', [
                'question' => 'Indícame tu email para localizar tu cuenta.',
                'sources' => ['erp', 'ps'],
            ]),
            $this->node('ask_order', 'collect_input', 'identify', 'Número de pedido', [
                'question' => '¿Cuál es el número del pedido que quieres devolver?',
                'variable_name' => 'numero_pedido',
            ]),
            $this->node('lookup', 'order_lookup', 'ask_order', 'Consultar pedido', [
                'order_variable' => 'numero_pedido',
                'source' => 'auto',
            ]),
            $this->node('docs', 'request_documents', 'lookup', 'Solicitar documentos', [
                'text' => 'Para completar la devolución necesito estos documentos:',
                'doc_types' => ['factura', 'foto_producto'],
            ]),
            $this->node('confirm', 'message', 'docs', 'Confirmación', [
                'text' => 'He registrado tu solicitud de devolución. Un agente la revisará en breve.',
            ]),
            $this->node('transfer', 'transfer', 'confirm', 'Transferir agente', [
                'message' => 'Te paso con el equipo de devoluciones.',
            ]),
        ];
    }

    /**
     * @return array<int, array<string,mixed>>
     */
    private function leadCapture(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('intro', 'message', 'start', 'Bienvenida', [
                'text' => '¡Hola! 👋 Déjanos tus datos y te contactamos enseguida.',
            ]),
            $this->node('ask_name', 'collect_input', 'intro', 'Nombre', [
                'question' => '¿Cuál es tu nombre?',
                'variable_name' => 'nombre',
            ]),
            $this->node('ask_email', 'collect_input', 'ask_name', 'Email', [
                'question' => 'Gracias {{nombre}}. ¿A qué email te contactamos?',
                'variable_name' => 'email',
            ]),
            $this->node('tag', 'add_tag', 'ask_email', 'Agregar etiqueta', [
                'tags' => ['lead'],
            ]),
            $this->node('end', 'end', 'tag', 'Fin', [
                'action' => 'close',
                'farewell' => '¡Gracias, {{nombre}}! Te contactaremos pronto. 🙌',
            ]),
        ];
    }

    /**
     * Estado de pedido. El resultado de consultar_pedido queda en `pedido`
     * (pedido_ok, pedido.state_name, pedido.tracking.0.tracking_url…).
     *
     * @return array<int, array<string,mixed>>
     */
    private function procedureOrderStatus(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            ...$this->orderAndEmailQuestions('start'),
            $this->node('lookup', 'ai_action', 'ask_email', 'Consultar pedido', [
                'action_key' => 'consultar_pedido',
                'args' => ['order_ref' => '{{numero_pedido}}', 'email' => '{{customer_email}}'],
                'save_to' => 'pedido',
                'on_error' => 'continue',
            ]),
            $this->node('route', 'branches', 'lookup', 'Resultado'),

            $this->node('b_missing', 'branchItem', 'route', 'No encontrado', [
                'name' => 'No encontrado', 'isElse' => false,
                'conditions' => [['variable' => 'pedido_ok', 'operator' => '!=', 'value' => '1']],
            ]),
            $this->node('m_missing', 'message', 'b_missing', 'No encontrado', [
                'text' => 'No he podido encontrar ese pedido con los datos que me has dado. Revisa el número y el email de la compra.',
            ]),
            $this->node('retry', 'quick_replies', 'm_missing', '¿Qué hacemos?', [
                'text' => '¿Qué prefieres?',
                'options' => ['Probar otro número', 'Hablar con una persona'],
                'variable_name' => 'opcion_pedido',
            ]),
            $this->node('retry_again', 'go_to_step', 'retry', 'Probar otro número', [
                'target_node_id' => 'ask_order', 'target_label' => 'Número de pedido',
            ]),
            $this->node('retry_human', 'transfer', 'retry', 'Hablar con una persona', [
                'message' => 'Te paso con una persona del equipo para que te ayude con tu pedido.',
            ]),

            $this->node('b_shipped', 'branchItem', 'route', 'Enviado', [
                'name' => 'Enviado', 'isElse' => false,
                'conditions' => [['variable' => 'pedido.tracking.0.tracking_url', 'operator' => 'not_empty', 'value' => '']],
            ]),
            $this->node('card', 'rich_message', 'b_shipped', 'Seguir envío', [
                'title' => 'Seguir envío',
                // Una sola tarjeta se publica como texto: el enlace va también en el subtítulo.
                'subtitle' => 'Sigue tu paquete aquí: {{pedido.tracking.0.tracking_url}}',
                'cards' => [[
                    'title' => 'Seguir envío',
                    'subtitle' => 'Pedido {{pedido.reference}}',
                    'url' => '{{pedido.tracking.0.tracking_url}}',
                ]],
            ]),
            $this->node('m_shipped', 'message', 'card', 'Datos del envío', [
                'text' => 'Tu pedido {{pedido.reference}} está «{{pedido.state_name}}». Transportista: {{pedido.tracking.0.carrier_name}}, nº de seguimiento {{pedido.tracking.0.tracking_number}}.',
            ]),
            $this->node('shipped_more', 'go_to_step', 'm_shipped', 'Ir a ¿Algo más?', [
                'target_node_id' => 'more', 'target_label' => '¿Algo más?',
            ]),

            $this->node('b_prep', 'branchItem', 'route', 'En preparación', [
                'name' => 'En preparación', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('m_prep', 'message', 'b_prep', 'Estado del pedido', [
                'text' => 'Tu pedido {{pedido.reference}} está «{{pedido.state_name}}».',
            ]),
            $this->node('route_date', 'branches', 'm_prep', 'Fecha prevista'),
            $this->node('b_date', 'branchItem', 'route_date', 'Con fecha prevista', [
                'name' => 'Con fecha prevista', 'isElse' => false,
                'conditions' => [['variable' => 'pedido.expected_date', 'operator' => 'not_empty', 'value' => '']],
            ]),
            $this->node('m_date', 'message', 'b_date', 'Fecha prevista', [
                'text' => 'La fecha estimada de entrega es el {{pedido.expected_date}}.',
            ]),
            $this->node('more', 'quick_replies', 'm_date', '¿Algo más?', [
                'text' => '¿Te ayudo con algo más?',
                'options' => ['Sí', 'No'],
                'variable_name' => 'algo_mas',
            ]),
            $this->node('more_yes', 'return', 'more', 'Sí'),
            $this->node('more_no', 'message', 'more', 'No', [
                'text' => '¡Perfecto! Que tengas un buen día. 👋',
            ]),
            $this->node('more_no_return', 'return', 'more_no', 'Volver'),
            $this->node('b_nodate', 'branchItem', 'route_date', 'Sin fecha prevista', [
                'name' => 'Sin fecha prevista', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('nodate_more', 'go_to_step', 'b_nodate', 'Ir a ¿Algo más?', [
                'target_node_id' => 'more', 'target_label' => '¿Algo más?',
            ]),
        ];
    }

    /**
     * Devoluciones. Un pedido admite devolución si está pagado, entregado y
     * alguna línea tiene cantidad reembolsable (pedido_reembolsable → `reembolso`).
     *
     * @return array<int, array<string,mixed>>
     */
    private function procedureReturns(): array
    {
        $lookupArgs = ['order_ref' => '{{numero_pedido}}', 'email' => '{{customer_email}}'];

        return [
            $this->node('start', 'start', null, 'Inicio'),
            ...$this->orderAndEmailQuestions('start'),
            $this->node('refund', 'ai_action', 'ask_email', 'Comprobar devolución', [
                'action_key' => 'pedido_reembolsable',
                'args' => $lookupArgs,
                'save_to' => 'reembolso',
                'on_error' => 'continue',
            ]),
            $this->node('route', 'branches', 'refund', 'Resultado'),

            $this->node('b_missing', 'branchItem', 'route', 'No encontrado', [
                'name' => 'No encontrado', 'isElse' => false,
                'conditions' => [['variable' => 'reembolso_ok', 'operator' => '!=', 'value' => '1']],
            ]),
            $this->node('m_missing', 'message', 'b_missing', 'No encontrado', [
                'text' => 'No he podido verificar ese pedido con el número y el email que me has dado, así que no puedo comprobar su devolución.',
            ]),
            $this->node('help', 'quick_replies', 'm_missing', '¿Qué prefieres?', [
                'text' => '¿Qué prefieres?',
                'options' => ['Hablar con una persona', 'Volver'],
                'variable_name' => 'opcion_devolucion',
            ]),
            $this->node('help_human', 'transfer', 'help', 'Hablar con una persona', [
                'message' => 'Te paso con el equipo de devoluciones.',
            ]),
            $this->node('help_back', 'return', 'help', 'Volver'),

            $this->node('b_blocked', 'branchItem', 'route', 'Aún no devolvible', [
                'name' => 'Aún no devolvible', 'isElse' => false, 'match' => 'any',
                'conditions' => [
                    ['variable' => 'reembolso.paid', 'operator' => '!=', 'value' => '1'],
                    ['variable' => 'reembolso.delivered', 'operator' => '!=', 'value' => '1'],
                ],
            ]),
            $this->node('m_blocked', 'message', 'b_blocked', 'Motivo', [
                'text' => 'Ese pedido todavía no consta como pagado y entregado, así que aún no se puede devolver. Cuando lo recibas tendrás 15 días naturales para solicitarlo.',
            ]),
            $this->node('blocked_help', 'go_to_step', 'm_blocked', 'Ir a ¿Qué prefieres?', [
                'target_node_id' => 'help', 'target_label' => '¿Qué prefieres?',
            ]),

            $this->node('b_refundable', 'branchItem', 'route', 'Se puede devolver', [
                'name' => 'Se puede devolver', 'isElse' => false,
                'conditions' => [['variable' => 'reembolso.lines', 'operator' => 'regex', 'value' => '"refundable":\s*"?[1-9]']],
            ]),
            $this->node('m_policy', 'message', 'b_refundable', 'Política de devolución', [
                'text' => 'Puedes devolver tu pedido dentro de los 15 días naturales desde que lo recibiste, con el producto en perfecto estado y con su embalaje.',
            ]),
            $this->node('card', 'rich_message', 'm_policy', 'Gestionar devolución', [
                'title' => 'Gestionar devolución',
                // Una sola tarjeta se publica como texto: el enlace va también en el subtítulo.
                'subtitle' => 'Inicia tu devolución o cambio aquí: https://returns.itsrever.com/alvarez',
                'cards' => [[
                    'title' => 'Gestionar devolución',
                    'subtitle' => 'Inicia tu devolución o cambio en nuestro portal',
                    'url' => 'https://returns.itsrever.com/alvarez',
                ]],
            ]),
            $this->node('exchanges', 'ai_action', 'card', 'Cambios existentes', [
                'action_key' => 'cambios_rever',
                'args' => $lookupArgs,
                'save_to' => 'cambios',
                'on_error' => 'continue',
            ]),
            $this->node('route_ex', 'branches', 'exchanges', 'Cambios'),
            $this->node('b_ex', 'branchItem', 'route_ex', 'Con cambios', [
                'name' => 'Con cambios', 'isElse' => false,
                'conditions' => [['variable' => 'cambios.exchanges', 'operator' => 'not_empty', 'value' => '']],
            ]),
            $this->node('m_ex', 'message', 'b_ex', 'Cambio existente', [
                'text' => 'Este pedido ya tiene un cambio en curso: {{cambios.exchanges.0.exchange_order.reference}}, en estado «{{cambios.exchanges.0.exchange_order.state_name}}».',
            ]),
            $this->node('ex_return', 'return', 'm_ex', 'Volver'),
            $this->node('b_noex', 'branchItem', 'route_ex', 'Sin cambios', [
                'name' => 'Sin cambios', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('noex_return', 'return', 'b_noex', 'Volver'),

            $this->node('b_nothing', 'branchItem', 'route', 'Nada reembolsable', [
                'name' => 'Nada reembolsable', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('m_nothing', 'message', 'b_nothing', 'Motivo', [
                'text' => 'En ese pedido ya no queda ningún artículo pendiente de devolver o reembolsar.',
            ]),
            $this->node('nothing_help', 'go_to_step', 'm_nothing', 'Ir a ¿Qué prefieres?', [
                'target_node_id' => 'help', 'target_label' => '¿Qué prefieres?',
            ]),
        ];
    }

    /**
     * Aviso de stock: solo clientes identificados. El producto sale del
     * contexto del visitante (`current_product_id`); la combinación talla/color
     * se lee de `product_attribute_id` si quien llama al procedimiento la pasa
     * como entrada de call_flow.
     *
     * @return array<int, array<string,mixed>>
     */
    private function procedureStockAlert(): array
    {
        return [
            $this->node('start', 'start', null, 'Inicio'),
            $this->node('gate', 'branches', 'start', 'Identificación'),

            $this->node('b_verified', 'branchItem', 'gate', 'Identificado', [
                'name' => 'Identificado', 'isElse' => false, 'match' => 'any',
                'conditions' => [
                    ['variable' => 'identity_verified', 'operator' => '=', 'value' => '1'],
                    ['variable' => 'customer_identified_via_otp', 'operator' => '=', 'value' => '1'],
                ],
            ]),
            $this->node('product', 'branches', 'b_verified', 'Producto'),
            $this->node('b_noproduct', 'branchItem', 'product', 'Sin producto', [
                'name' => 'Sin producto', 'isElse' => false,
                'conditions' => [['variable' => 'current_product_id', 'operator' => 'is_empty', 'value' => '']],
            ]),
            $this->node('m_noproduct', 'message', 'b_noproduct', 'Sin producto', [
                'text' => 'Abre la ficha del producto que te interesa y vuelve a pedirme el aviso.',
            ]),
            $this->node('noproduct_return', 'return', 'm_noproduct', 'Volver'),

            $this->node('b_product', 'branchItem', 'product', 'Con producto', [
                'name' => 'Con producto', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('confirm', 'quick_replies', 'b_product', 'Confirmar aviso', [
                'text' => '¿Quieres que te avise por email cuando este producto vuelva a tener stock?',
                'options' => ['Sí', 'No'],
                'variable_name' => 'confirmar_aviso',
            ]),
            $this->node('alert', 'ai_action', 'confirm', 'Sí', [
                'action_key' => 'aviso_stock',
                'args' => ['product_id' => '{{current_product_id}}', 'product_attribute_id' => '{{product_attribute_id}}'],
                'confirmed_variable' => 'confirmar_aviso',
                'save_to' => 'aviso',
                'on_error' => 'continue',
            ]),
            $this->node('result', 'branches', 'alert', 'Resultado'),
            $this->node('b_ok', 'branchItem', 'result', 'Aviso creado', [
                'name' => 'Aviso creado', 'isElse' => false,
                'conditions' => [['variable' => 'aviso.subscribed', 'operator' => '=', 'value' => '1']],
            ]),
            $this->node('m_ok', 'message', 'b_ok', 'Aviso creado', [
                'text' => '¡Hecho! Te avisaremos por email en cuanto vuelva a haber stock. 🔔',
            ]),
            $this->node('ok_return', 'return', 'm_ok', 'Volver'),
            $this->node('b_already', 'branchItem', 'result', 'Ya apuntado', [
                'name' => 'Ya apuntado', 'isElse' => false,
                'conditions' => [['variable' => 'aviso.already', 'operator' => '=', 'value' => '1']],
            ]),
            $this->node('m_already', 'message', 'b_already', 'Ya apuntado', [
                'text' => 'Ya estabas apuntado a este aviso. Te escribiremos cuando haya stock.',
            ]),
            $this->node('already_return', 'return', 'm_already', 'Volver'),
            $this->node('b_ko', 'branchItem', 'result', 'No se pudo', [
                'name' => 'No se pudo', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('m_ko', 'message', 'b_ko', 'No se pudo', [
                'text' => 'No he podido crear el aviso ahora mismo. Inténtalo de nuevo más tarde o pide hablar con una persona.',
            ]),
            $this->node('ko_return', 'return', 'm_ko', 'Volver'),
            $this->node('declined', 'message', 'confirm', 'No', [
                'text' => 'De acuerdo, no te aviso. Si cambias de idea, dímelo.',
            ]),
            $this->node('declined_return', 'return', 'declined', 'Volver'),

            $this->node('b_anonymous', 'branchItem', 'gate', 'Sin identificar', [
                'name' => 'Sin identificar', 'isElse' => true, 'conditions' => [],
            ]),
            $this->node('m_login', 'message', 'b_anonymous', 'Iniciar sesión', [
                'text' => 'Para apuntarte a un aviso de stock tienes que iniciar sesión en tu cuenta de la tienda. Cuando lo hayas hecho, vuelve a pedírmelo.',
            ]),
            $this->node('login_return', 'return', 'm_login', 'Volver'),
        ];
    }

    /**
     * Número de pedido y email de la compra (el email se omite si el cliente ya
     * está identificado). Deja `numero_pedido` y `customer_email` en el contexto.
     *
     * @return array<int, array<string,mixed>>
     */
    private function orderAndEmailQuestions(string $parentId): array
    {
        return [
            $this->node('ask_order', 'collect_input', $parentId, 'Número de pedido', [
                'question' => '¿Cuál es tu número de pedido?',
                'variable_name' => 'numero_pedido',
                'validation' => 'order_ref',
            ]),
            $this->node('ask_email', 'collect_input', 'ask_order', 'Email de la compra', [
                'question' => '¿Con qué email hiciste la compra?',
                'variable_name' => 'customer_email',
                'validation' => 'email',
                'skip_if_set' => true,
            ]),
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function node(string $id, string $type, ?string $parentId, string $label, array $data = []): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parentId, 'label' => $label, 'data' => $data];
    }
}
