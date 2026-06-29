<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Radicado Configuration
    |--------------------------------------------------------------------------
    |
    | Configure el prefijo y formato de los números de radicado.
    | Formato: {prefix}-{year}-{consecutive}
    | Ejemplo: peticiones-2026-000001
    |
    */

    'radicado_prefix' => env('ATTENTION_RADICADO_PREFIX', 'peticiones'),

    /*
    |--------------------------------------------------------------------------
    | File Upload Settings
    |--------------------------------------------------------------------------
    |
    | Configuración para archivos adjuntos en solicitudes peticiones.
    |
    */

    'files' => [
        // Máximo número de archivos por solicitud
        'max_attachments' => 5,

        // Tamaño máximo por archivo en KB (10MB = 10240 KB)
        'max_file_size' => 10240,

        // Tipos MIME permitidos
        'allowed_mimes' => [
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',

            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',

            // Text
            'txt' => 'text/plain',
            'csv' => 'text/csv',

            // Compressed
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
        ],

        // Colecciones de media library
        'collections' => [
            'attachments' => 'Archivos adjuntos del ciudadano',
            'resolutions' => 'Archivos de respuesta/resolución',
            'internal' => 'Documentos internos',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Captcha Settings
    |--------------------------------------------------------------------------
    |
    | Habilita captcha en el formulario público de peticiones.
    | Requiere configurar el módulo Captcha (site_key + secret).
    |
    */

    'captcha' => [
        'enabled' => env('ATTENTION_CAPTCHA_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de notificaciones y emails.
    |
    */

    'notifications' => [
        // Habilitar/deshabilitar envío de emails
        'enabled' => env('ATTENTION_NOTIFICATIONS_ENABLED', true),

        // Email de copia para todas las notificaciones
        'cc_email' => env('ATTENTION_CC_EMAIL', null),

        // Enviar confirmación al ciudadano al radicar
        'send_confirmation' => true,

        // Enviar notificación al asignar a usuario
        'send_assignment' => true,

        // Enviar respuesta al resolver
        'send_resolution' => true,

        // Plantillas de email (views)
        'templates' => [
            'confirmation' => 'attention::emails.confirmation',
            'assigned' => 'attention::emails.assigned',
            'resolution' => 'attention::emails.resolution',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    |
    | Reglas de negocio y validaciones del módulo.
    |
    */

    'rules' => [
        // Longitud mínima del asunto
        'subject_min_length' => 10,

        // Longitud mínima de la descripción
        'description_min_length' => 20,

        // Longitud mínima de la resolución
        'resolution_min_length' => 50,

        // Días para responder una solicitud
        'response_days' => 15,

        // Permitir solicitudes anónimas
        'allow_anonymous' => true,

        // Campos obligatorios para solicitudes no anónimas
        'required_citizen_fields' => [
            'customer_firstname',
            'customer_lastname',
            'customer_email',
        ],

        // Formato de celular (expresión regular)
        'cellphone_format' => '/^[0-9]{10}$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de estados del ciclo de vida de una solicitud.
    |
    */

    'status' => [
        'received' => [
            'label' => 'Recibido',
            'color' => 'blue',
            'icon' => 'inbox',
        ],
        'in_process' => [
            'label' => 'En Proceso',
            'color' => 'yellow',
            'icon' => 'clock',
        ],
        'resolved' => [
            'label' => 'Resuelto',
            'color' => 'green',
            'icon' => 'check-circle',
        ],
        'closed' => [
            'label' => 'Cerrado',
            'color' => 'gray',
            'icon' => 'archive',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de paginación por defecto.
    |
    */

    'pagination' => [
        // Registros por página (default)
        'per_page' => 15,

        // Máximo registros por página
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Satisfaction Survey
    |--------------------------------------------------------------------------
    |
    | Configuración de encuesta de satisfacción.
    |
    */

    'satisfaction' => [
        // Habilitar encuestas de satisfacción
        'enabled' => true,

        // Escala de calificación (1 a N)
        'rating_scale' => [
            'min' => 1,
            'max' => 5,
        ],

        // Labels para cada calificación
        'rating_labels' => [
            1 => 'Muy insatisfecho',
            2 => 'Insatisfecho',
            3 => 'Neutral',
            4 => 'Satisfecho',
            5 => 'Muy satisfecho',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de caché para mejorar rendimiento.
    |
    */

    'cache' => [
        // Tiempo de caché en segundos (0 = deshabilitado)
        'ttl' => env('ATTENTION_CACHE_TTL', 0),

        // Prefijo para keys de caché
        'prefix' => 'attention:',

        // Cachear estadísticas
        'cache_stats' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de seguridad.
    |
    */

    'security' => [
        // Rate limiting para endpoints públicos (requests por minuto)
        'rate_limit_public' => 10,

        // Rate limiting para endpoints administrativos
        'rate_limit_admin' => 60,

        // Habilitar logs de auditoría detallados
        'audit_logs' => true,

        // Ofuscar información sensible en logs
        'obfuscate_sensitive_data' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de integraciones con otros sistemas.
    |
    */

    'integrations' => [
        // Habilitar integración con sistema de gestión documental
        'document_management' => false,

        // Habilitar integración con CRM
        'crm' => false,

        // Webhook URL para notificaciones externas
        'webhook_url' => env('ATTENTION_WEBHOOK_URL', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Settings
    |--------------------------------------------------------------------------
    |
    | Configuración de interfaz de usuario.
    |
    */

    'ui' => [
        // Mostrar código de radicado en formato corto (últimos 6 dígitos)
        'show_short_radicado' => false,

        // Formato de fecha para mostrar al usuario
        'date_format' => 'd/m/Y H:i',

        // Zona horaria para mostrar fechas
        'timezone' => 'America/Bogota',

        // Colores del tema
        'theme_colors' => [
            'primary' => '#3B82F6',
            'success' => '#10B981',
            'warning' => '#F59E0B',
            'danger' => '#EF4444',
            'info' => '#6366F1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Hours Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de jornada laboral para cálculo de SLA en días hábiles.
    | Según Ley 1437/2011 (CPACA) - Código de Procedimiento Administrativo
    |
    */

    'business_hours' => [
        // Hora de inicio de jornada laboral (formato 24h)
        'start' => env('ATTENTION_BUSINESS_START', '08:00'),

        // Hora de fin de jornada laboral (formato 24h)
        'end' => env('ATTENTION_BUSINESS_END', '18:00'),

        // Excluir horario de almuerzo del cálculo
        'exclude_lunch' => env('ATTENTION_EXCLUDE_LUNCH', false),

        // Hora de inicio de almuerzo (si exclude_lunch = true)
        'lunch_start' => env('ATTENTION_LUNCH_START', '12:00'),

        // Hora de fin de almuerzo (si exclude_lunch = true)
        'lunch_end' => env('ATTENTION_LUNCH_END', '14:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Política de Retención de Datos (Normativa Colombiana)
    |--------------------------------------------------------------------------
    |
    | Según Ley General de Archivos (Ley 594/2000) y normas del Archivo
    | General de la Nación, los documentos de peticiones deben conservarse mínimo:
    | - Gestión: 5 años (acceso frecuente)
    | - Central: 10 años (acceso ocasional)
    | - Histórico: Permanente (valor histórico)
    |
    */

    'data_retention' => [
        'enabled' => env('ATTENTION_DATA_RETENTION_ENABLED', true),

        // Años en archivo de gestión (acceso normal)
        'management_archive_years' => 5,

        // Años en archivo central (archivado pero accesible)
        'central_archive_years' => 10,

        // Después de este tiempo, se marca como histórico
        'permanent_archive_years' => 15,

        // Impedir eliminación permanente antes de este plazo
        'minimum_retention_years' => 5,

        // Advertir antes de eliminar datos de este rango
        'warn_before_delete_years' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auditoría y Trazabilidad
    |--------------------------------------------------------------------------
    |
    | Configuración de logs de auditoría para cumplir con requisitos legales
    | de trazabilidad según normativa colombiana.
    |
    */

    'audit' => [
        'enabled' => true,
        'log_ip' => true,
        'log_user_agent' => true,
        'log_changes' => true,
        'retention_days' => 2555, // ~7 años
    ],

];
