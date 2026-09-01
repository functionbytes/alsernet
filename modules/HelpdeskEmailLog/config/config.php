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
    | Tamaño máximo (en bytes) del bloque de cabeceras MIME que se almacena
    | (raw_headers). Mucho menor que max_body_bytes: un bloque de cabeceras
    | nunca debería acercarse a ese tamaño salvo un caso patológico de listas
    | de Cc/Bcc enormes.
    */
    'max_header_bytes' => env('EMAIL_LOG_MAX_HEADER_BYTES', 64 * 1024),

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
    |--------------------------------------------------------------------------
    | Papelera de registros
    |--------------------------------------------------------------------------
    | Días que un registro borrado desde el listado (EmailLogController::
    | destroy()/bulkDestroy(), ahora SoftDeletes) permanece recuperable en la
    | papelera antes de que `php artisan email-logs:prune` lo borre de forma
    | definitiva (ver PruneEmailLogsCommand::pruneTrash()). Independiente de
    | `retention_days`: ese es el límite de antigüedad general del histórico
    | (por created_at) y sigue borrando de forma directa/definitiva, sin pasar
    | por esta papelera — ver el docblock de pruneTrash() para el porqué.
    */
    'trash_retention_days' => env('EMAIL_LOG_TRASH_RETENTION_DAYS', 30),

    /*
    | Registros por página en el listado (valor por defecto) y opciones que
    | ofrece el selector de la UI.
    */
    // 15 por defecto: la columna de la lista del workspace es estrecha y con
    // 25 filas obligaba a bajar bastante para llegar al pie de paginación.
    // Sigue siendo configurable por Settings ('helpdeskemaillog.per_page'),
    // que tiene prioridad sobre este valor.
    'per_page' => env('EMAIL_LOG_PER_PAGE', 15),
    'per_page_options' => [15, 25, 50, 100],

    /*
    | Mapa entity_type => nombre de ruta para enlazar la entidad relacionada
    | desde la vista previa. La ruta recibe el entity_id como primer parámetro.
    | Solo se genera el enlace si la ruta existe (Route::has); si no, se muestra
    | texto plano. Ejemplo:
    |   'Ticket' => 'manager.helpdesk.tickets.show',
    |   'Order'  => 'ecommerce.orders.show',
    */
    'entity_routes' => [
        'Modules\\HelpdeskTickets\\Models\\Ticket' => 'manager.helpdesk.tickets.show',
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
        'Modules\\Document\\Entities\\Document' => 'Documento',
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
    | Píxel de apertura
    |--------------------------------------------------------------------------
    | Si es false, LogEmailQueued deja de insertar el <img> de seguimiento de
    | apertura en el HTML de los envíos que lo soportan (hoy, "Emails
    | enviados" de HelpdeskTickets). No afecta al histórico ya registrado en
    | email_log_opens ni a la redirección de clics (click tracking), que se
    | gobierna aparte.
    */
    'pixel_tracking_enabled' => env('EMAIL_LOG_PIXEL_TRACKING_ENABLED', true),

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

    /*
    |--------------------------------------------------------------------------
    | Payload de webhooks de proveedor (auditoría/depuración)
    |--------------------------------------------------------------------------
    | EmailProviderWebhookController guarda el payload crudo de cada evento
    | verificado (bounce/complaint/delivered/open) en
    | email_provider_events.payload, para poder depurar por qué un evento no
    | correlacionó y para poder reprocesarlo (ver Settings → Eventos de
    | webhook). Antes de guardarse se aplican dos límites, mismo criterio que
    | store_body/max_body_bytes con el cuerpo de los emails:
    |
    |  1. Tamaño máximo en bytes (webhook_payload_max_bytes). Si el JSON
    |     serializado lo supera, se sustituye por un marcador
    |     {"_truncated": true, "original_size": N} en vez de cortar el JSON a
    |     medias (partir un documento JSON por bytes lo dejaría inválido, a
    |     diferencia de un body de texto).
    |  2. Claves eliminadas recursivamente (webhook_payload_redact_keys). NO
    |     se toca el destinatario/message-id/IP/user-agent: son justo lo que
    |     hay que comparar cuando algo no correlaciona, así que se conservan
    |     tal cual. Lo que sí se quita es material sin valor de depuración
    |     que puede traer PII de negocio ajena a este evento: firmas
    |     criptográficas ya verificadas antes de llegar aquí
    |     (Signature/SigningCertURL de SNS) o bolsas de variables arbitrarias
    |     que la aplicación que originó el envío pudo haber adjuntado
    |     (user-variables de Mailgun, Metadata/Tag de Postmark).
    */
    'webhook_payload_max_bytes' => env('EMAIL_LOG_WEBHOOK_PAYLOAD_MAX_BYTES', 64 * 1024),

    'webhook_payload_redact_keys' => [
        'signature', 'Signature', 'SigningCertURL',
        'user-variables', 'Metadata', 'Tag', 'tags',
    ],

    /*
    | Días que se conservan los eventos de webhook de proveedor
    | (email_provider_events) antes de que `email-logs:prune` los borre.
    | Independiente de retention_days/trash_retention_days: estos eventos no
    | son el email en sí, son el registro de auditoría del webhook.
    */
    'webhook_events_retention_days' => env('EMAIL_LOG_WEBHOOK_EVENTS_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Reputación de dominio (SPF/DKIM/DMARC) y tasas de rebote/queja
    |--------------------------------------------------------------------------
    | SPF/DMARC se consultan por DNS TXT (dns_get_record, PHP puro, sin
    | dependencia nueva) — cacheados este número de horas para no repetir la
    | consulta en cada carga del panel.
    */
    'reputation_dns_cache_hours' => env('EMAIL_LOG_REPUTATION_DNS_CACHE_HOURS', 24),

    /*
    | DKIM no es autodescubrible sin conocer el selector — se prueban estos
    | selectores comunes además de los configurados por dominio, pero un
    | selector no encontrado se reporta como "no verificable", nunca como
    | "DKIM ausente" (evita un falso negativo).
    */
    'reputation_common_dkim_selectors' => ['default', 'selector1', 'selector2', 'google', 'k1', 'mandrill', 'smtp'],

    /*
    | Ventana de días sobre la que se calculan las tasas de rebote/queja
    | (EmailLog::reputationStats()).
    */
    'reputation_window_days' => env('EMAIL_LOG_REPUTATION_WINDOW_DAYS', 30),

    /*
    | Umbrales de alerta (%). "warning" solo colorea el dashboard; "critical"
    | además dispara ReputationThresholdBreached (con debounce, ver
    | CheckEmailReputationCommand) al comando diario email-logs:check-reputation.
    */
    'bounce_rate_warning_pct' => env('EMAIL_LOG_BOUNCE_RATE_WARNING_PCT', 2.0),
    'bounce_rate_critical_pct' => env('EMAIL_LOG_BOUNCE_RATE_CRITICAL_PCT', 5.0),
    'complaint_rate_warning_pct' => env('EMAIL_LOG_COMPLAINT_RATE_WARNING_PCT', 0.05),
    'complaint_rate_critical_pct' => env('EMAIL_LOG_COMPLAINT_RATE_CRITICAL_PCT', 0.1),
];
