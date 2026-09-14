<?php

namespace Modules\HelpdeskTickets\Support;

use Modules\Helpdesk\Models\Setting;

/**
 * Catálogo central de funcionalidades togglables de la vista de ticket
 * (Settings → Helpdesk · Tickets → Funcionalidades, 14-sep-2026) — mismo
 * patrón que FeaturesSettingsController de Conversaciones, pero para
 * HelpdeskTickets: un único sitio que describe cada sección/etiqueta para
 * la pantalla de ajustes Y resuelve el mapa {feature_x_enabled => bool}
 * que consume tanto el controlador de ajustes como la vista del listado
 * (que lo pasa al JS vía #tkt-data → TKA.state.features).
 *
 * Fase 1 (14-sep-2026): vista de detalle del ticket (composer, acciones,
 * gestión/asignación, pestañas). El listado (filtros/bulk/vistas), las
 * páginas de gestión (plantillas, SLA vencidos, automatizaciones…) y las
 * funcionalidades de módulos satélite visibles en tickets (traducción
 * automática, vínculo ERP) se añaden en tandas siguientes — ver
 * project_helpdesktickets_features_toggle_2026_09_14 en la memoria del
 * proyecto para el estado y lo que falta.
 */
class TicketFeatures
{
    public const GROUP = 'ticket_features';

    /**
     * Cada sección: title/desc para la pantalla de ajustes + items
     * [slug => label]. El slug es el nombre "desnudo" que consumen
     * helpdesk_ticket_feature_enabled() (PHP) y featureEnabled() (JS) —
     * la key real en BD es "feature_{slug}_enabled".
     */
    public const SECTIONS = [
        'composer' => [
            'title' => 'Barra de redacción',
            'desc' => 'Pestañas y herramientas disponibles al responder un ticket. "Respuesta" y "Enviar" no se pueden apagar: sin ellas no hay forma de contestar.',
            'items' => [
                'composer_note' => 'Nota interna (pestaña redactor)',
                'composer_templates' => 'Plantillas de respuesta',
                'composer_translate' => 'Traducir (herramienta manual)',
                'composer_attach' => 'Adjuntar archivos',
                'composer_macros' => 'Macros',
                'composer_followup' => 'Seguimiento automático',
                'composer_ai' => 'Sugerencia IA',
                'composer_mention' => 'Mencionar agente (@)',
                'composer_schedule' => 'Programar envío',
            ],
        ],
        'actions' => [
            'title' => 'Panel derecho — Acciones',
            'desc' => 'Botones de la tarjeta "Acciones". "Responder al cliente" no se puede apagar.',
            'items' => [
                'action_resolve' => 'Marcar como resuelto',
                'action_close' => 'Cerrar ticket',
                'action_reopen' => 'Reabrir ticket',
                'action_snooze' => 'Aplazar seguimiento',
                'action_merge' => 'Fusionar duplicado',
                'action_split' => 'Dividir ticket',
                'action_portal' => 'Ver como el cliente',
                'action_blacklist' => 'Pasar a lista negra',
                'action_delete' => 'Eliminar / Archivar',
            ],
        ],
        'management' => [
            'title' => 'Panel derecho — Gestión y asignación',
            'desc' => 'Tarjetas "Gestión del ticket", "Asignado a", "SLA" y "CSAT".',
            'items' => [
                'mgmt_fields' => 'Campos de gestión (Estado, Prioridad, Categoría, Equipo)',
                'mgmt_reassign' => 'Reasignar agente',
                'mgmt_followers' => 'Seguidores',
                'mgmt_sla_card' => 'Tarjeta SLA (con calendario)',
                'mgmt_csat_card' => 'Encuesta de satisfacción (CSAT)',
            ],
        ],
        'tabs' => [
            'title' => 'Panel derecho — Pestañas del detalle',
            'desc' => 'Pestañas de la cabecera del ticket. "Hilo" no se puede apagar: es la vista principal.',
            'items' => [
                'tab_mail' => 'Pestaña Correo',
                'tab_trace' => 'Pestaña Traza',
                'tab_activity' => 'Pestaña Actividad',
                'tab_files' => 'Pestaña Adjuntos',
            ],
        ],
    ];

    /** @return array<string,bool> ['feature_composer_note_enabled' => true, ...] */
    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::SECTIONS as $section) {
            foreach (array_keys($section['items']) as $slug) {
                $defaults["feature_{$slug}_enabled"] = true;
            }
        }

        return $defaults;
    }

    /** @return string[] */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    /**
     * Mapa resuelto {feature_x_enabled => bool} mergeando BD con defaults
     * — lo que consume tanto el controlador de ajustes (index) como la
     * vista del listado de tickets para hidratar TKA.state.features.
     *
     * @return array<string,bool>
     */
    public static function resolved(): array
    {
        $defaults = self::defaults();

        try {
            $stored = Setting::allAsFlatArray(self::GROUP);
        } catch (\Throwable) {
            $stored = [];
        }

        $merged = array_merge($defaults, array_intersect_key($stored, $defaults));

        foreach ($merged as $key => $value) {
            $merged[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return $merged;
    }
}
