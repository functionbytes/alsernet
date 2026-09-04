<?php

return [
    'name' => 'HelpdeskBirthday',

    /*
    |--------------------------------------------------------------------------
    | Cola de envio
    |--------------------------------------------------------------------------
    | Cola propia y NO 'emails': esa la comparten IA, embeddings y ERP con solo
    | 2 procesos, y una tanda de cumpleanos los ahogaria. Si se cambia este
    | valor hay que anadir la cola nueva al --queue= de docker/docker-compose.yml
    | (servicio worker-helpdesk) Y a devops/supervisor/laravel-queue-helpdesk.conf,
    | o los correos se encolan y no sale ninguno, en silencio.
    */
    'queue' => env('HELPDESK_BIRTHDAY_QUEUE', 'birthdays'),

    /*
    |--------------------------------------------------------------------------
    | Preparacion de la campana del dia
    |--------------------------------------------------------------------------
    | Hora a la que el scheduler crea la campana, resuelve el cupon y consulta
    | la audiencia. Debe ser anterior a window_start.
    */
    'prepare_at' => env('HELPDESK_BIRTHDAY_PREPARE_AT', '06:00'),

    /*
    |--------------------------------------------------------------------------
    | Ventana de envio y ritmo
    |--------------------------------------------------------------------------
    | Los correos se reparten entre window_start y window_end. El intervalo
    | entre envios se calcula con el numero real de destinatarios del dia:
    |
    |   interval = max( ventana / N , 3600 / throttle_per_hour )
    |
    | throttle_per_hour manda sobre la ventana: si no caben, la campana termina
    | mas tarde en vez de acelerar el ritmo.
    */
    /*
    |--------------------------------------------------------------------------
    | Zona horaria de negocio
    |--------------------------------------------------------------------------
    | La app corre en UTC (config('app.timezone')), pero las horas de esta
    | config son horas de OFICINA: "enviar de 9 a 2" significa las 9 de la
    | mañana en España, no las 9 UTC (que en verano son las 11 allí).
    |
    | Todas las horas de este archivo y del panel se interpretan en esta zona;
    | lo que se guarda en BD sigue siendo UTC, como el resto de la app. Mismo
    | criterio que helpdesksla.default_business_hours.timezone.
    */
    'timezone' => env('HELPDESK_BIRTHDAY_TIMEZONE', 'Europe/Madrid'),

    'window_start' => env('HELPDESK_BIRTHDAY_WINDOW_START', '09:00'),
    'window_end' => env('HELPDESK_BIRTHDAY_WINDOW_END', '14:00'),
    'throttle_per_hour' => (int) env('HELPDESK_BIRTHDAY_THROTTLE_PER_HOUR', 600),

    /*
    | Segundo cinturon de seguridad, a nivel de worker (middleware de Spatie en
    | SendBirthdayEmailJob). El reparto por scheduled_at ya espacia los envios;
    | esto protege de una avalancha si algo reencola muchos jobs de golpe.
    */
    'throttle' => [
        'max_jobs' => (int) env('HELPDESK_BIRTHDAY_MAX_JOBS_PER_MINUTE', 30),
        'per_seconds' => 60,
    ],

    /*
    | Cuantos destinatarios vencidos se despachan como maximo en cada pasada de
    | helpdeskbirthday:dispatch-due (corre cada minuto).
    */
    'dispatch_batch_size' => (int) env('HELPDESK_BIRTHDAY_DISPATCH_BATCH', 100),

    /*
    |--------------------------------------------------------------------------
    | Guarda de seguridad
    |--------------------------------------------------------------------------
    | Si el ERP devuelve mas destinatarios que esto, la campana se aborta en vez
    | de enviar. Cubre el caso de que el manager todavia no tenga desplegado el
    | filtro 'birthday' y responda con la base entera de clientes.
    */
    'max_recipients' => (int) env('HELPDESK_BIRTHDAY_MAX_RECIPIENTS', 2000),

    /*
    |--------------------------------------------------------------------------
    | Exclusiones (configurables desde el panel de ajustes)
    |--------------------------------------------------------------------------
    | Estos son los valores por defecto; el panel los sobrescribe via Settings.
    | - commercial_optin: excluye a quien marco NO_INFORMACION_COMERCIAL_LOPD
    | - lopd_accepted: exige FACEPTACION_LOPD informada (mas restrictivo)
    | - check_suppressions: cruza contra email_suppressions antes de encolar
    */
    'exclusions' => [
        'commercial_optin' => true,
        'lopd_accepted' => false,
        'has_email' => true,
        'check_suppressions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 29 de febrero
    |--------------------------------------------------------------------------
    | A quien nacio el 29-feb se le felicita, en anos no bisiestos, el dia que
    | diga esta opcion: 'feb28' o 'mar01'.
    */
    'leap_day_policy' => env('HELPDESK_BIRTHDAY_LEAP_DAY', 'feb28'),

    /*
    |--------------------------------------------------------------------------
    | Cupon del dia
    |--------------------------------------------------------------------------
    | El codigo lo fija un admin desde el panel. Al preparar la campana se
    | consulta a gestion (ErpService::consultaBono) para traer las fechas de
    | validez y el importe reales; si el ERP no responde se usan los valores
    | configurados a mano y la campana queda marcada como coupon_source=manual.
    */
    /*
    | De dónde salen los cumpleañeros del día:
    |   'api'     → GET /api/erp/customer?birthday=MM-DD, la API de clientes del
    |               módulo Erp: filtra contra Oracle en el WHERE y tarda ~3 s.
    |   'gestion' → GET /api-gestion/cliente/?fnacimiento=…, la API de Gestión.
    |               Mismo resultado (727 cumpleañeros el día que se comparó),
    |               pero ~16 s y 880 KB de XML. Alternativa si la de arriba no
    |               está disponible.
    */
    'audience_source' => env('HELPDESK_BIRTHDAY_AUDIENCE_SOURCE', 'api'),

    /*
    | URL de la API de clientes. Por defecto la de ESTE panel (dentro de Docker
    | se alcanza por el nombre del contenedor). Ojo: el manager externo
    | (helpdeskErp.manager_url) expone la MISMA ruta pero es otra aplicación y
    | no tiene el filtro `birthday`; apuntar ahí devuelve clientes cualesquiera.
    */
    'customers_api_url' => env('HELPDESK_BIRTHDAY_CUSTOMERS_API_URL', env('SUPPLIER_ERP_INTERNAL_URL', 'http://nginx')),

    // La consulta de un día contra Gestión tarda ~16 s.
    'erp_timeout' => (int) env('HELPDESK_BIRTHDAY_ERP_TIMEOUT', 90),

    'coupon' => [
        /*
        | Tipo de bono de promoción en Gestión (IDTBONO_PROMOCION). Es lo que
        | dice QUÉ bono se emite: importe, validez y compra mínima salen de él.
        |
        | Sin este valor no se puede generar un bono por cliente
        | (POST /api-gestion/generacion-bono/), solo repartir un código único a
        | todo el mundo con 'code'. El dato lo tiene Gestión: la tabla
        | TBONO_PROMOCION no es visible para el usuario de solo lectura con el
        | que se consulta Oracle.
        */
        'bono_type_id' => (int) env('HELPDESK_BIRTHDAY_BONO_TYPE_ID', 0),

        'code' => env('HELPDESK_BIRTHDAY_COUPON_CODE', ''),
        'verification_code' => env('HELPDESK_BIRTHDAY_COUPON_CV', ''),
        'validate_against_erp' => (bool) env('HELPDESK_BIRTHDAY_COUPON_VALIDATE', true),
        'origin' => 'gestion',
    ],

    /*
    |--------------------------------------------------------------------------
    | Plantilla de correo
    |--------------------------------------------------------------------------
    | Clave en mailer_templates. La siembra BirthdayTemplatesSeeder.
    */
    'template_key' => env('HELPDESK_BIRTHDAY_TEMPLATE_KEY', 'birthday-coupon'),

    /*
    |--------------------------------------------------------------------------
    | Idioma del cliente
    |--------------------------------------------------------------------------
    | El ERP devuelve su id interno de idioma (CLIENTE_CENT.IDIDIOMA), que no
    | coincide con los ids de la tabla `langs` del módulo Mailer. Aquí se
    | declara la correspondencia id del ERP => ISO.
    |
    | VACÍO A PROPÓSITO: mientras nadie confirme los ids reales del ERP, todo
    | el mundo recibe el correo en fallback_language, que es lo que pasaba
    | antes. Rellenarlo es lo único que hace falta para que el módulo escriba
    | en portugués, inglés, etc. — la plantilla ya es multi-idioma vía
    | mailer_template_langs; solo hay que traducirla en el admin de Mailer.
    |
    | Ejemplo: [1 => 'es', 2 => 'pt', 3 => 'en']
    */
    'erp_language_map' => [],

    'fallback_language' => env('HELPDESK_BIRTHDAY_FALLBACK_LANG', 'es'),

    /*
    | Destino del botón principal del correo ({SHOP_URL} en la plantilla).
    | Sin configurar apunta a la propia app, que no es la tienda: ponlo.
    */
    'shop_url' => env('HELPDESK_BIRTHDAY_SHOP_URL', ''),

    /*
    | Back-office de PrestaShop, para el enlace "Ver el pedido" de la pantalla
    | de canjes. Sin configurar, ese enlace no se muestra.
    |
    | OJO: PrestaShop exige un token por controlador y usuario en la URL del
    | admin, y no se puede generar desde aquí. El enlace lleva al pedido pero
    | PrestaShop pedirá confirmar el token antes de mostrarlo.
    */
    'ps_admin_url' => env('HELPDESK_BIRTHDAY_PS_ADMIN_URL', 'http://localhost:8091/panel'),

    /*
    | Dias que se conservan las campanas ya cerradas y sus destinatarios.
    | 0 o menos desactiva la limpieza.
    */
    'retention_days' => (int) env('HELPDESK_BIRTHDAY_RETENTION_DAYS', 365),
];
