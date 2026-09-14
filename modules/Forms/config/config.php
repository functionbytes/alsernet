<?php

return [
    'name' => 'Forms',

    /*
     |--------------------------------------------------------------------------
     | Receptor de alsernetforms (sitio Alvarez)
     |--------------------------------------------------------------------------
     |
     | Secreto HMAC compartido con el módulo alsernetforms de PrestaShop
     | (Configuration ALSERNETFORMS_WEBHOOK_SECRET, generada en su
     | install()/upgrade). Debe copiarse el mismo valor a ambos lados.
     |
     */
    'webhook_secret' => env('FORMS_WEBHOOK_SECRET', ''),

    /*
     |--------------------------------------------------------------------------
     | Imagen Open Graph por defecto de los formularios públicos
     |--------------------------------------------------------------------------
     |
     | Fallback de Modules\Forms\Traits\HasSeo cuando el formulario no define
     | og_image propia. En el proyecto de origen esto lo servía config('Seo').
     |
     */
    'default_og_image' => env('FORMS_DEFAULT_OG_IMAGE'),

    /*
     |--------------------------------------------------------------------------
     | Publicación de formularios en la tienda (PrestaShop)
     |--------------------------------------------------------------------------
     |
     | Endpoint del api.php del módulo alsernetforms. Recibe el artefacto
     | compilado (HTML+CSS+JS) al pulsar "Publicar" y lo guarda para servirlo
     | por su cuenta. Se firma con 'webhook_secret', el mismo secreto que ya
     | comparten ambos lados para los webhooks entrantes.
     |
     */
    'store' => [
        'api_url' => env('FORMS_STORE_API_URL', ''),
        'http_timeout' => (int) env('FORMS_STORE_HTTP_TIMEOUT', 20),
        'http_connect_timeout' => (int) env('FORMS_STORE_HTTP_CONNECT_TIMEOUT', 3),
        'recaptcha_site_key' => env('FORMS_STORE_RECAPTCHA_SITE_KEY', ''),
        // Secreto del cron.php de la tienda (ALSERNETFORMS_CRON_SECURE_KEY allí).
        // Va por cabecera, nunca por parámetro de URL: acabaría en los logs.
        'cron_secret' => env('FORMS_STORE_CRON_SECRET', ''),
    ],

    'field_types' => [
        'text' => ['label' => 'Texto corto', 'icon' => 'fas fa-font', 'group' => 'basic'],
        'email' => ['label' => 'Email', 'icon' => 'fas fa-envelope', 'group' => 'basic'],
        'tel' => ['label' => 'Teléfono', 'icon' => 'fas fa-phone', 'group' => 'basic'],
        'number' => ['label' => 'Número', 'icon' => 'fas fa-hashtag', 'group' => 'basic'],
        'url' => ['label' => 'URL', 'icon' => 'fas fa-link', 'group' => 'basic'],
        'textarea' => ['label' => 'Texto largo', 'icon' => 'fas fa-align-left', 'group' => 'basic'],
        'select' => ['label' => 'Lista desplegable', 'icon' => 'fas fa-chevron-down', 'group' => 'choice'],
        'checkbox' => ['label' => 'Casillas de verificación', 'icon' => 'fas fa-check-square', 'group' => 'choice'],
        'radio' => ['label' => 'Opción única', 'icon' => 'fas fa-circle-dot', 'group' => 'choice'],
        'file' => ['label' => 'Archivo adjunto', 'icon' => 'fas fa-upload', 'group' => 'upload'],
        'date' => ['label' => 'Fecha', 'icon' => 'fas fa-calendar', 'group' => 'datetime'],
        'time' => ['label' => 'Hora', 'icon' => 'fas fa-clock', 'group' => 'datetime'],
        'datetime' => ['label' => 'Fecha y hora', 'icon' => 'fas fa-calendar-clock', 'group' => 'datetime'],
        'hidden' => ['label' => 'Campo oculto', 'icon' => 'fas fa-eye-slash', 'group' => 'advanced'],
        'rating' => ['label' => 'Valoración (estrellas)', 'icon' => 'fas fa-star', 'group' => 'advanced'],
        'calculation' => ['label' => 'Campo calculado', 'icon' => 'fas fa-calculator', 'group' => 'advanced'],
        'signature' => ['label' => 'Firma digital', 'icon' => 'fas fa-signature', 'group' => 'advanced'],
        'nps' => ['label' => 'NPS (0-10)', 'icon' => 'fas fa-gauge', 'group' => 'advanced'],
        'likert' => ['label' => 'Escala Likert', 'icon' => 'fas fa-sliders', 'group' => 'advanced'],
        'slider' => ['label' => 'Deslizador (rango)', 'icon' => 'fas fa-arrows-left-right', 'group' => 'advanced'],
        'image_choice' => ['label' => 'Elección por imagen', 'icon' => 'fas fa-images', 'group' => 'advanced'],
        'rich_text' => ['label' => 'Texto enriquecido', 'icon' => 'fas fa-bold', 'group' => 'advanced'],
        'address' => ['label' => 'Dirección', 'icon' => 'fas fa-map-marker-alt', 'group' => 'contact'],
        'section_header' => ['label' => 'Título de sección', 'icon' => 'fas fa-heading', 'group' => 'layout'],
        'html_block' => ['label' => 'Bloque HTML', 'icon' => 'fas fa-code', 'group' => 'layout'],
        'divider' => ['label' => 'Separador horizontal', 'icon' => 'fas fa-minus', 'group' => 'layout'],
        'spacer' => ['label' => 'Espacio en blanco', 'icon' => 'fas fa-arrows-up-down', 'group' => 'layout'],
        'consent' => ['label' => 'Consentimiento GDPR', 'icon' => 'fas fa-shield-check', 'group' => 'legal'],
        'newsletter_consent' => ['label' => 'Suscripción newsletter', 'icon' => 'fas fa-newspaper', 'group' => 'legal'],
        'color_picker' => ['label' => 'Selector de color', 'icon' => 'fas fa-palette', 'group' => 'advanced'],
    ],

    'field_groups' => [
        'basic' => 'Básicos',
        'choice' => 'Selección',
        'upload' => 'Archivos',
        'datetime' => 'Fecha y hora',
        'advanced' => 'Avanzados',
        'contact' => 'Contacto',
        'layout' => 'Layout',
        'legal' => 'Legal',
    ],

    'themes' => [
        'default' => 'Estándar (Bootstrap)',
        'flat' => 'Plano',
        'material' => 'Material Design',
        'card' => 'Tarjetas',
        'minimal' => 'Minimal',
        'dark' => 'Oscuro',
        'rounded' => 'Redondeado',
        'bordered' => 'Con borde',
    ],

    'default_success_message' => 'Gracias por tu mensaje. Te responderemos pronto.',

    /*
    |--------------------------------------------------------------------------
    | Mensaje de éxito por idioma
    |--------------------------------------------------------------------------
    |
    | El formulario ya se sirve en los seis idiomas de la tienda, pero el aviso
    | posterior al envío seguía en español para todos: `success_message` es una
    | sola cadena por formulario. La tienda tampoco lo tenía traducido (no hay
    | entrada de catálogo para 'Thank you, we have received your request.'), así
    | que las traducciones se declaran aquí.
    |
    | Manda lo que haya en `forms.translations[iso]['success_message']`; si no,
    | el `success_message` del propio formulario cuando el idioma es el base; y
    | si no, esta tabla. Ver Form::localizedSuccessMessage().
    |
    */
    // Idioma en el que se redactan los formularios en el panel. Fijo a
    // propósito: app.locale cambia al compilar el artefacto por idiomas.
    'base_locale' => env('FORMS_BASE_LOCALE', 'es'),

    'success_messages' => [
        'es' => 'Gracias por tu mensaje. Te responderemos pronto.',
        'en' => 'Thank you for your message. We will get back to you shortly.',
        'fr' => 'Merci pour votre message. Nous vous répondrons rapidement.',
        'pt' => 'Obrigado pela sua mensagem. Responderemos em breve.',
        'de' => 'Vielen Dank für Ihre Nachricht. Wir melden uns in Kürze bei Ihnen.',
        'it' => 'Grazie per il tuo messaggio. Ti risponderemo a breve.',
    ],

    'success_notes' => [
        'es' => 'Te responderemos al correo que nos dejaste. Si no lo ves, revisa la carpeta de correo no deseado.',
        'en' => 'We will reply to the email address you gave us. If you cannot see it, check your spam folder.',
        'fr' => 'Nous répondrons à l\'adresse électronique que vous nous avez indiquée. Si vous ne la voyez pas, vérifiez vos courriers indésirables.',
        'pt' => 'Responderemos para o email que nos indicou. Se não o vir, verifique a pasta de spam.',
        'de' => 'Wir antworten an die von Ihnen angegebene E-Mail-Adresse. Falls Sie sie nicht sehen, prüfen Sie Ihren Spam-Ordner.',
        'it' => 'Ti risponderemo all\'indirizzo email che ci hai lasciato. Se non lo vedi, controlla la cartella spam.',
    ],
    'max_fields_per_form' => 50,
    'max_file_size_mb' => 10,
    'allowed_file_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'webp', 'txt', 'csv'],
    'allowed_mime_types' => [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'text/plain',
        'text/csv',
    ],
    'throttle_submissions' => 20,
    'abandon_tracking_ttl_days' => 30,
    'min_fill_seconds' => 3,
    'auto_warm_page_cache' => false,

    'signature' => [
        'max_length' => 524288, // 512 KB de base64
    ],

    'cache' => [
        'shortcode_ttl_seconds' => 300,
        'counters_ttl_seconds' => 60,
    ],

    'pii' => [
        'field_types' => ['email', 'tel'],
        'keywords' => ['email', 'phone', 'name', 'telefon', 'nombre'],
    ],

    'import' => [
        'max_fields' => 100,
        'max_file_bytes' => 512 * 1024,
    ],

    'permissions' => [
        ['flag' => 'Forms.forms.index', 'label' => 'Ver formularios'],
        ['flag' => 'Forms.forms.create', 'label' => 'Crear formularios'],
        ['flag' => 'Forms.forms.edit', 'label' => 'Editar formularios'],
        ['flag' => 'Forms.forms.delete', 'label' => 'Eliminar formularios'],
        ['flag' => 'Forms.forms.manage', 'label' => 'Gestionar (bypass ownership)'],
        ['flag' => 'Forms.submissions.index', 'label' => 'Ver envíos'],
        ['flag' => 'Forms.submissions.edit', 'label' => 'Editar envíos'],
        ['flag' => 'Forms.submissions.export', 'label' => 'Exportar envíos'],
        ['flag' => 'Forms.submissions.delete', 'label' => 'Eliminar envíos'],
        ['flag' => 'Forms.categories.manage', 'label' => 'Gestionar categorías'],
        ['flag' => 'Forms.analytics.index', 'label' => 'Ver analíticas'],
        ['flag' => 'Forms.settings.manage', 'label' => 'Configuración avanzada'],
        ['flag' => 'Forms.inbox.index', 'label' => 'Ver inbox de formularios'],
        ['flag' => 'Forms.field-types.manage', 'label' => 'Gestionar tipos de campo'],
        ['flag' => 'Forms.templates.manage', 'label' => 'Gestionar plantillas'],
        ['flag' => 'Forms.follow-ups.manage', 'label' => 'Gestionar follow-ups'],
        ['flag' => 'Forms.access-tokens.manage', 'label' => 'Gestionar tokens de acceso'],
    ],

    'template_library' => [
        'contact' => [
            'name' => 'Formulario de contacto',
            'description' => 'Formulario básico de contacto con nombre, email y mensaje',
            'icon' => 'fas fa-envelope',
            'fields' => [
                ['key' => 'name', 'label' => 'Nombre completo', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'email', 'label' => 'Correo electrónico', 'type' => 'email', 'is_required' => true, 'width' => 'half'],
                ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'is_required' => false, 'width' => 'half'],
                ['key' => 'subject', 'label' => 'Asunto', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'message', 'label' => 'Mensaje', 'type' => 'textarea', 'is_required' => true, 'width' => 'full'],
                ['key' => 'consent', 'label' => 'Acepto la política de privacidad', 'type' => 'consent', 'is_required' => true, 'width' => 'full', 'consent_text' => 'Acepto el tratamiento de mis datos personales según la <a href="/privacy">política de privacidad</a>.'],
            ],
        ],

        'quote_request' => [
            'name' => 'Solicitud de presupuesto',
            'description' => 'Formulario para solicitar un presupuesto o cotización',
            'icon' => 'fas fa-file-invoice-dollar',
            'fields' => [
                ['key' => 'company', 'label' => 'Empresa', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'name', 'label' => 'Nombre contacto', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => true, 'width' => 'half'],
                ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'is_required' => false, 'width' => 'half'],
                ['key' => 'service', 'label' => 'Servicio requerido', 'type' => 'select', 'is_required' => true, 'width' => 'full', 'options' => [['value' => 'web', 'label' => 'Desarrollo web'], ['value' => 'app', 'label' => 'Aplicación móvil'], ['value' => 'consulting', 'label' => 'Consultoría']]],
                ['key' => 'budget', 'label' => 'Presupuesto aproximado', 'type' => 'select', 'is_required' => false, 'width' => 'half', 'options' => [['value' => '<5k', 'label' => 'Menos de 5.000€'], ['value' => '5k-20k', 'label' => '5.000€ - 20.000€'], ['value' => '>20k', 'label' => 'Más de 20.000€']]],
                ['key' => 'timeline', 'label' => 'Plazo deseado', 'type' => 'select', 'is_required' => false, 'width' => 'half', 'options' => [['value' => 'urgent', 'label' => 'Urgente (<1 mes)'], ['value' => 'normal', 'label' => 'Normal (1-3 meses)'], ['value' => 'flexible', 'label' => 'Flexible']]],
                ['key' => 'description', 'label' => 'Descripción del proyecto', 'type' => 'textarea', 'is_required' => true, 'width' => 'full'],
                ['key' => 'consent', 'label' => 'Acepto la política de privacidad', 'type' => 'consent', 'is_required' => true, 'width' => 'full', 'consent_text' => 'Acepto el tratamiento de mis datos personales.'],
            ],
        ],

        'satisfaction_survey' => [
            'name' => 'Encuesta de satisfacción',
            'description' => 'Mide la satisfacción del cliente con NPS y Likert',
            'icon' => 'fas fa-chart-bar',
            'fields' => [
                ['key' => 'nps_score', 'label' => '¿Recomendarías nuestros servicios?', 'type' => 'nps', 'is_required' => true, 'width' => 'full'],
                ['key' => 'satisfaction', 'label' => 'Valora tu experiencia', 'type' => 'rating', 'is_required' => true, 'width' => 'full', 'max_value' => 5],
                ['key' => 'comments', 'label' => 'Comentarios adicionales', 'type' => 'textarea', 'is_required' => false, 'width' => 'full'],
                ['key' => 'email', 'label' => 'Email (opcional)', 'type' => 'email', 'is_required' => false, 'width' => 'full'],
            ],
        ],

        'job_application' => [
            'name' => 'Solicitud de empleo',
            'description' => 'Formulario de candidatura para vacantes',
            'icon' => 'fas fa-briefcase',
            'fields' => [
                ['key' => 'full_name', 'label' => 'Nombre completo', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => true, 'width' => 'half'],
                ['key' => 'phone', 'label' => 'Teléfono', 'type' => 'tel', 'is_required' => true, 'width' => 'half'],
                ['key' => 'position', 'label' => 'Puesto al que aplica', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'experience', 'label' => 'Años de experiencia', 'type' => 'slider', 'is_required' => true, 'width' => 'half', 'min_value' => 0, 'max_value' => 20, 'step_value' => 1],
                ['key' => 'cv', 'label' => 'Currículum vitae (PDF)', 'type' => 'file', 'is_required' => true, 'width' => 'half'],
                ['key' => 'cover_letter', 'label' => 'Carta de presentación', 'type' => 'textarea', 'is_required' => false, 'width' => 'full'],
                ['key' => 'consent', 'label' => 'Acepto el tratamiento de mis datos', 'type' => 'consent', 'is_required' => true, 'width' => 'full', 'consent_text' => 'Acepto que mis datos sean tratados para el proceso de selección.'],
            ],
        ],

        'event_registration' => [
            'name' => 'Registro de evento',
            'description' => 'Formulario para inscripción a eventos',
            'icon' => 'fas fa-calendar-check',
            'fields' => [
                ['key' => 'name', 'label' => 'Nombre completo', 'type' => 'text', 'is_required' => true, 'width' => 'half'],
                ['key' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => true, 'width' => 'half'],
                ['key' => 'company', 'label' => 'Empresa/Institución', 'type' => 'text', 'is_required' => false, 'width' => 'half'],
                ['key' => 'dietary', 'label' => 'Restricciones alimentarias', 'type' => 'checkbox', 'is_required' => false, 'width' => 'half', 'options' => [['value' => 'vegetarian', 'label' => 'Vegetariano'], ['value' => 'vegan', 'label' => 'Vegano'], ['value' => 'gluten_free', 'label' => 'Sin gluten'], ['value' => 'none', 'label' => 'Ninguna']]],
                ['key' => 'how_heard', 'label' => '¿Cómo te enteraste?', 'type' => 'select', 'is_required' => false, 'width' => 'full', 'options' => [['value' => 'email', 'label' => 'Email'], ['value' => 'social', 'label' => 'Redes sociales'], ['value' => 'word', 'label' => 'Recomendación'], ['value' => 'web', 'label' => 'Página web']]],
                ['key' => 'consent', 'label' => 'Acepto recibir comunicaciones del evento', 'type' => 'consent', 'is_required' => true, 'width' => 'full', 'consent_text' => 'Acepto recibir información relacionada con este y futuros eventos.'],
            ],
        ],
    ],
];
