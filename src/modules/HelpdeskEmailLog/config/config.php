<?php

return [
    'name' => 'HelpdeskEmailLog',

    /*
    |--------------------------------------------------------------------------
    | Almacenamiento del cuerpo del email
    |--------------------------------------------------------------------------
    | Si es false, no se guarda body_html ni body_text (solo metadatos).
    | Útil para reducir el tamaño de la tabla o por motivos de privacidad.
    */
    'store_body' => env('EMAIL_LOG_STORE_BODY', true),

    /*
    | Tamaño máximo (en bytes) del cuerpo HTML/texto que se almacena.
    | Si el cuerpo lo supera, se trunca. null = sin límite.
    */
    'max_body_bytes' => env('EMAIL_LOG_MAX_BODY_BYTES', 512 * 1024),

    /*
    | Días de retención. Los registros más antiguos se eliminan con
    | `php artisan email-logs:prune`. 0 o null desactiva la purga.
    */
    'retention_days' => env('EMAIL_LOG_RETENTION_DAYS', 90),

    /*
    | Horas tras las que un registro que sigue en estado "queued" (nunca llegó
    | a confirmarse como enviado) se marca como "failed" durante la purga.
    | 0 o null desactiva esta comprobación.
    */
    'stale_queued_hours' => env('EMAIL_LOG_STALE_QUEUED_HOURS', 24),

    /*
    | Registros por página en el listado (valor por defecto) y opciones que
    | ofrece el selector de la UI.
    */
    'per_page' => env('EMAIL_LOG_PER_PAGE', 25),
    'per_page_options' => [10, 25, 50, 100],

    /*
    | Mapa entity_type => nombre de ruta para enlazar la entidad relacionada
    | desde la vista previa. La ruta recibe el entity_id como primer parámetro.
    | Solo se genera el enlace si la ruta existe (Route::has); si no, se muestra
    | texto plano. Ejemplo:
    |   'Ticket' => 'manager.helpdesk.tickets.show',
    |   'Order'  => 'ecommerce.orders.show',
    */
    'entity_routes' => [
        // 'EntityType' => 'route.name',
    ],

    /*
    | Mapa entity_type => etiqueta legible para mostrar en la vista previa en
    | lugar del nombre de clase FQCN. Si no hay entrada, se usa el basename de
    | la clase en formato legible (p.ej. "Customer").
    */
    'entity_labels' => [
        'Modules\\Helpdesk\\Models\\Customer' => 'Cliente',
        'Modules\\HelpdeskTickets\\Models\\Ticket' => 'Ticket',
        'Modules\\Helpdesk\\Models\\Conversation' => 'Conversación',
    ],

    /*
    | Cabeceras MIME internas que se leen para enriquecer el log y que se
    | eliminan del mensaje antes de enviarlo (no deben llegar al destinatario).
    */
    'internal_headers' => [
        'X-Email-Module',
        'X-Entity-Type',
        'X-Entity-Id',
        'X-External-Id',
        'X-Mailable-Class',
    ],

    /*
    |--------------------------------------------------------------------------
    | Redacción del cuerpo para Mailables sensibles
    |--------------------------------------------------------------------------
    | Para correos que contengan secretos efímeros (password resets, magic
    | links, OTP, API tokens) NO se debe guardar el cuerpo aunque
    | `store_body` esté habilitado. Tres mecanismos, evaluados en orden:
    |
    |  1. La Mailable implementa Contracts\RedactsEmailLogBody (preferido).
    |  2. La clase del Mailable está en `redact_body_for_classes`.
    |  3. La cabecera X-Email-Module está en `redact_body_for_modules`.
    |
    | Cuando se redacta, body_html/body_text quedan null y se añade a
    | metadata.redacted = true.
    */
    'redact_body_for_classes' => [
        // 'Modules\\Auth\\Mail\\ResetPasswordMail',
    ],

    'redact_body_for_modules' => [
        // 'Auth',
    ],
];
