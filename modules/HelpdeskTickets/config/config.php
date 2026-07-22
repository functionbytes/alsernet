<?php

return [
    'name' => 'HelpdeskTickets',

    /*
    |--------------------------------------------------------------------------
    | Motor de escalado de tickets (EscalateTicketsJob / EscalationService)
    |--------------------------------------------------------------------------
    | Los umbrales base por prioridad y el interruptor global viven en
    | config('helpdesk.escalation.*') (módulo Helpdesk). Aquí van los ajustes
    | propios del motor de este módulo.
    */
    'escalation' => [
        // Escalar también por SLA de resolución (vencido o próximo a vencer).
        'sla_enabled' => env('HELPDESK_ESCALATION_SLA_ENABLED', true),

        // Minutos antes del vencimiento del SLA de resolución a partir de los
        // cuales el ticket se considera "próximo a vencer" y se escala.
        'sla_due_within_minutes' => env('HELPDESK_ESCALATION_SLA_DUE_WITHIN_MINUTES', 60),

        // Horas mínimas entre dos escalados del mismo ticket.
        'cooldown_hours' => env('HELPDESK_ESCALATION_COOLDOWN_HOURS', 24),

        // Número máximo de escalados por ticket (escalation_count).
        'max_escalations' => env('HELPDESK_ESCALATION_MAX', 3),

        // Notificar a los managers además del agente asignado.
        'notify_managers' => env('HELPDESK_ESCALATION_NOTIFY_MANAGERS', true),

        // Roles que reciben la notificación de escalado.
        'notify_roles' => ['manager'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Observabilidad operativa (helpdesk:ops-metrics, cada 5 min)
    |--------------------------------------------------------------------------
    | El comando programado recolecta profundidad de colas, dead-letters
    | (failed_jobs), webhooks fallidos, breaches SLA de la última hora y
    | tickets sin asignar con SLA próximo; cachea el snapshot (lo muestra el
    | dashboard de reports) y evalúa las alertas de abajo.
    |
    | alerts.enabled: OFF por defecto ("do no harm") — sin activarlo solo se
    | recolecta y loguea, nunca se envía mail. Al activarlo, superar un umbral
    | envía un mail encolado a los usuarios con permiso manage_helpdesk, con
    | cooldown para no repetir en cada run.
    */
    'ops' => [
        // Colas vigiladas (Queue::size). Deben existir en el driver activo.
        'queues' => array_values(array_filter(array_map('trim', explode(
            ',',
            (string) env('HELPDESK_OPS_QUEUES', 'default,helpdesk,helpdesk-ai,helpdesk-webhooks,notifications,emails')
        )))),

        // Ventana "SLA próximo" para tickets sin asignar (minutos).
        'sla_warning_minutes' => (int) env('HELPDESK_OPS_SLA_WARNING_MINUTES', 60),

        'alerts' => [
            'enabled' => env('HELPDESK_OPS_ALERTS_ENABLED', false),

            // Alerta si alguna cola vigilada supera este nº de jobs (0 = off).
            'queue_depth' => (int) env('HELPDESK_OPS_ALERT_QUEUE_DEPTH', 500),

            // Alerta si failed_jobs (dead-letter) supera este valor.
            'failed_jobs' => (int) env('HELPDESK_OPS_ALERT_FAILED_JOBS', 0),

            // Alerta si los breaches SLA de la última hora superan este valor (0 = off).
            'sla_breaches_per_hour' => (int) env('HELPDESK_OPS_ALERT_SLA_BREACHES', 10),

            // Minutos mínimos entre dos mails de alerta.
            'cooldown_minutes' => (int) env('HELPDESK_OPS_ALERT_COOLDOWN', 60),
        ],
    ],
];
