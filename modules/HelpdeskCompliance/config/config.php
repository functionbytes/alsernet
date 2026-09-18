<?php

return [
    'name' => 'HelpdeskCompliance',

    /*
    |--------------------------------------------------------------------------
    | Texto de redaccion
    |--------------------------------------------------------------------------
    | Sustituye campos con PII al anonimizar (soft delete).
    */
    'redacted_text' => '[Contenido eliminado por solicitud GDPR]',

    /*
    |--------------------------------------------------------------------------
    | Modulos cubiertos por la cascada
    |--------------------------------------------------------------------------
    | Cada handler se aplica solo si su modulo esta habilitado (guard en runtime).
    */
    'cascade_modules' => [
        'HelpdeskTickets',
        'HelpdeskChatFlow',
        // Expedientes KYC vinculados por email/teléfono (los datos viven en el
        // módulo Document; el guard del job comprueba ese módulo, no el puente).
        'HelpdeskDocument',
        // EmailLog/EmailLogOpen/EmailSuppression, vinculados por email (viven en
        // la conexión por defecto, fuera de la transacción 'helpdesk').
        'HelpdeskEmailActivity',
    ],

    /*
    |--------------------------------------------------------------------------
    | Alerta de solicitudes GDPR estancadas
    |--------------------------------------------------------------------------
    | Un ComplianceRequest en 'pending' con más de esta antigüedad no ha sido
    | recogido por el worker de la cola 'helpdeskcompliance' — el borrado core
    | (irreversible) ya se aplicó pero la cascada del resto de módulos sigue
    | pendiente. Ver helpdeskcompliance:check-stale-requests.
    */
    'stale_pending_hours' => (int) env('HELPDESKCOMPLIANCE_STALE_PENDING_HOURS', 24),
];
