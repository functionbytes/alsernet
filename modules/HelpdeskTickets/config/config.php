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
];
