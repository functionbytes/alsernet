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
    ],
];
