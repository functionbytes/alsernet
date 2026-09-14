<?php

namespace Modules\HelpdeskTickets\Support;

use Modules\HelpdeskTickets\Models\Automation;

/**
 * Catálogo de lo que el motor de automatizaciones SABE ejecutar de verdad.
 *
 * Fuente única para el editor "Si… Entonces…" del modal de escalado y para su
 * FormRequest: si un campo, operador o acción no está aquí, ni se ofrece en la
 * interfaz ni se acepta al guardar. El criterio es que cada entrada tenga su
 * correspondencia exacta en AutomationEngine (matchesConditions/runActions);
 * el formulario de Ajustes acepta hoy cualquier JSON, así que era posible
 * guardar reglas con acciones inexistentes que el motor descartaba en
 * silencio (el `default => null` del match).
 */
final class AutomationCatalog
{
    /**
     * Operadores implementados en AutomationEngine::matchesConditions().
     *
     * @return array<string, string>
     */
    public static function operators(): array
    {
        return [
            'equals' => 'es',
            'not_equals' => 'no es',
            'in' => 'es una de',
            'contains' => 'contiene',
            'not_contains' => 'no contiene',
            'greater_than' => 'es mayor que',
            'less_than' => 'es menor que',
            'is_null' => 'está vacío',
            'is_not_null' => 'tiene valor',
        ];
    }

    /**
     * Prioridades reales de la columna helpdesk_tickets.priority (mismos
     * valores que valida StoreTicketRequest).
     *
     * @return array<int, array{id: string, name: string}>
     */
    public static function priorities(): array
    {
        return [
            ['id' => 'low', 'name' => 'Baja'],
            ['id' => 'normal', 'name' => 'Normal'],
            ['id' => 'high', 'name' => 'Alta'],
            ['id' => 'urgent', 'name' => 'Urgente'],
        ];
    }

    /**
     * Campos de ticket que se pueden usar como condición.
     *
     * `options` es la colección que el JS ya tiene cargada en TKA.state
     * (statuses/groups/agents/categories) o 'priorities', que viaja en este
     * mismo catálogo. `input` decide el control y `cast` cómo se guarda el
     * valor para que el `==` del motor compare lo que se espera.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function fields(): array
    {
        return [
            [
                'field' => 'priority',
                'label' => 'La prioridad',
                'input' => 'select',
                'options' => 'priorities',
                'cast' => 'string',
                'ops' => ['equals', 'not_equals', 'in'],
            ],
            [
                'field' => 'status_id',
                'label' => 'El estado',
                'input' => 'select',
                'options' => 'statuses',
                'cast' => 'int',
                'ops' => ['equals', 'not_equals'],
            ],
            [
                'field' => 'group_id',
                'label' => 'El equipo',
                'input' => 'select',
                'options' => 'groups',
                'cast' => 'int',
                'ops' => ['equals', 'not_equals'],
            ],
            [
                'field' => 'assignee_id',
                'label' => 'El agente asignado',
                'input' => 'select',
                'options' => 'agents',
                'cast' => 'int',
                // is_null / is_not_null cubren "sin asignar" y "ya asignado",
                // que es lo que de verdad se pregunta al escalar.
                'ops' => ['equals', 'is_null', 'is_not_null'],
            ],
            [
                'field' => 'category_id',
                'label' => 'La categoría',
                'input' => 'select',
                'options' => 'categories',
                'cast' => 'int',
                'ops' => ['equals', 'not_equals'],
            ],
            [
                'field' => 'sla_first_response_breached',
                'label' => 'El SLA de primera respuesta incumplido',
                'input' => 'bool',
                'options' => null,
                'cast' => 'bool',
                'ops' => ['equals'],
            ],
            [
                'field' => 'sla_next_response_breached',
                'label' => 'El SLA de respuesta siguiente incumplido',
                'input' => 'bool',
                'options' => null,
                'cast' => 'bool',
                'ops' => ['equals'],
            ],
            [
                'field' => 'sla_resolution_breached',
                'label' => 'El SLA de resolución incumplido',
                'input' => 'bool',
                'options' => null,
                'cast' => 'bool',
                'ops' => ['equals'],
            ],
            [
                'field' => 'escalation_count',
                'label' => 'Los escalados acumulados',
                'input' => 'number',
                'options' => null,
                'cast' => 'int',
                'ops' => ['greater_than', 'less_than', 'equals'],
            ],
            [
                'field' => 'subject',
                'label' => 'El asunto',
                'input' => 'text',
                'options' => null,
                'cast' => 'string',
                'ops' => ['contains', 'not_contains'],
            ],
            ...self::erpFields(),
        ];
    }

    /**
     * Datos del cliente en gestión (ERP).
     *
     * No son columnas del ticket: AutomationEngine los resuelve con
     * ErpFactsService a partir del cliente. Solo aparecen si el módulo
     * HelpdeskErp está instalado y encendido — ofrecer condiciones que nunca
     * van a poder evaluarse confunde más que ayuda.
     *
     * El disparador natural para estas condiciones es "El ERP ha respondido"
     * (ticket.erp_resolved): en ticket.created la búsqueda todavía está en la
     * cola y erp_linked sería false para todo el mundo.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function erpFields(): array
    {
        if (! function_exists('helpdesk_erp_enabled') || ! helpdesk_erp_enabled()) {
            return [];
        }

        return [
            [
                'field' => 'erp_linked',
                'label' => 'El cliente está en gestión',
                'input' => 'bool',
                'options' => null,
                'cast' => 'bool',
                'ops' => ['equals'],
            ],
            [
                'field' => 'erp_balance_pending',
                'label' => 'La deuda pendiente en gestión (€)',
                'input' => 'number',
                'options' => null,
                'cast' => 'float',
                'ops' => ['greater_than', 'less_than', 'equals'],
            ],
            [
                'field' => 'erp_credit_limit',
                'label' => 'El límite de crédito en gestión (€)',
                'input' => 'number',
                'options' => null,
                'cast' => 'float',
                'ops' => ['greater_than', 'less_than', 'equals'],
            ],
            [
                'field' => 'erp_loyalty_points',
                'label' => 'Los puntos de fidelidad',
                'input' => 'number',
                'options' => null,
                'cast' => 'int',
                'ops' => ['greater_than', 'less_than', 'equals'],
            ],
            [
                'field' => 'erp_orders_count',
                'label' => 'El número de pedidos recientes',
                'input' => 'number',
                'options' => null,
                'cast' => 'int',
                'ops' => ['greater_than', 'less_than', 'equals'],
            ],
            [
                'field' => 'erp_days_since_last_order',
                'label' => 'Días desde el último pedido',
                'input' => 'number',
                'options' => null,
                'cast' => 'int',
                'ops' => ['greater_than', 'less_than', 'equals', 'is_null'],
            ],
            [
                'field' => 'erp_province',
                'label' => 'La provincia en gestión',
                'input' => 'text',
                'options' => null,
                'cast' => 'string',
                'ops' => ['equals', 'contains', 'not_contains'],
            ],
        ];
    }

    /**
     * Acciones implementadas en AutomationEngine::runActions(). Ni una más:
     * el mockup pedía además "escalar a nivel 2", "avisar al manager" y
     * "enviar SlaBreachMail", que el motor no sabe hacer.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function actions(): array
    {
        return [
            [
                'type' => 'set_priority',
                'label' => 'Cambiar la prioridad a',
                'input' => 'select',
                'options' => 'priorities',
                'cast' => 'string',
            ],
            [
                'type' => 'assign_group',
                'label' => 'Reasignar al equipo',
                'input' => 'select',
                'options' => 'groups',
                'cast' => 'int',
            ],
            [
                'type' => 'assign_user',
                'label' => 'Reasignar al agente',
                'input' => 'select',
                'options' => 'agents',
                'cast' => 'int',
            ],
            [
                'type' => 'set_status',
                'label' => 'Cambiar el estado a',
                'input' => 'select',
                'options' => 'statuses',
                'cast' => 'int',
            ],
            [
                'type' => 'add_tag',
                'label' => 'Añadir la etiqueta',
                'input' => 'text',
                'options' => null,
                'cast' => 'string',
            ],
            [
                'type' => 'add_internal_note',
                'label' => 'Añadir una nota interna',
                'input' => 'textarea',
                'options' => null,
                'cast' => 'string',
            ],
            [
                'type' => 'notify_agent',
                'label' => 'Avisar al agente asignado',
                'input' => 'none',
                'options' => null,
                'cast' => null,
            ],
            [
                'type' => 'close',
                'label' => 'Cerrar el ticket',
                'input' => 'none',
                'options' => null,
                'cast' => null,
            ],
            [
                'type' => 'ai_route',
                'label' => 'Enrutar con IA (categoría sugerida y reparto)',
                'input' => 'none',
                'options' => null,
                'cast' => null,
            ],
        ];
    }

    /**
     * Disparadores reales: los cinco eventos que tienen listener
     * (RunAutomationsOnTicket*). No hay disparadores por tiempo.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function triggers(): array
    {
        $labels = [
            'ticket.created' => 'Se crea un ticket',
            'ticket.updated' => 'Se actualiza un ticket',
            'ticket.assigned' => 'Se asigna un ticket',
            'ticket.resolved' => 'Se resuelve un ticket',
            'ticket.closed' => 'Se cierra un ticket',
            'ticket.erp_resolved' => 'El ERP responde sobre el cliente',
        ];

        $out = [];
        foreach (array_keys(Automation::$triggerEvents) as $event) {
            $out[] = ['value' => $event, 'label' => $labels[$event] ?? $event];
        }

        return $out;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function fieldsByKey(): array
    {
        return collect(self::fields())->keyBy('field')->all();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function actionsByKey(): array
    {
        return collect(self::actions())->keyBy('type')->all();
    }

    /**
     * Convierte el valor recibido del formulario al tipo con el que el motor
     * lo va a comparar o escribir. Sin esto, un status_id llegaría como "3" y
     * el `==` de matchesConditions seguiría funcionando, pero set_status
     * escribiría una cadena en una columna entera.
     */
    public static function castValue(?string $cast, mixed $value): mixed
    {
        return match ($cast) {
            'int' => (int) $value,
            // Los importes del ERP (deuda, límite de crédito) llegan con
            // decimales: con 'int' una regla de "más de 150,50 €" se guardaba
            // como 150.
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'string' => (string) $value,
            default => null,
        };
    }
}
