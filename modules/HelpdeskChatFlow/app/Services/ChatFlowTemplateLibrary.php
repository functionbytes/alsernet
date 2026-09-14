<?php

namespace Modules\HelpdeskChatFlow\Services;

/**
 * Pre-built chat flow templates so users can start from a working flow
 * instead of a blank canvas. Each template returns a normalized node tree
 * ({id, type, parentId, label, data}) the editor can render directly.
 */
class ChatFlowTemplateLibrary
{
    /**
     * @return array<int, array{key: string, name: string, description: string, icon: string, color: string, trigger_type: string}>
     */
    public function all(): array
    {
        return [
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
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function node(string $id, string $type, ?string $parentId, string $label, array $data = []): array
    {
        return ['id' => $id, 'type' => $type, 'parentId' => $parentId, 'label' => $label, 'data' => $data];
    }
}
