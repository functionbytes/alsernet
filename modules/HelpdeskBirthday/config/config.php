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
    |
    | NUNCA por encima de throttle.max_jobs: lo que sobra no se envia, se queda
    | dando vueltas en la cola hasta que el freno lo deja pasar. Estaba en 100
    | contra un freno de 30 y esa diferencia —70 jobs por minuto esperando— era
    | la que agotaba los intentos de los correos y los daba por fallidos.
    | Ahora el job caduca por tiempo (SendBirthdayEmailJob::retryUntil) y no por
    | intentos, asi que un descuadre ya no pierde correos; pero encolar mas de
    | lo que se puede enviar solo sirve para llenar Redis.
    */
    'dispatch_batch_size' => (int) env('HELPDESK_BIRTHDAY_DISPATCH_BATCH', 30),

    /*
    |--------------------------------------------------------------------------
    | Canjes: de donde se leen
    |--------------------------------------------------------------------------
    | 'auto'   → el bridge si esta configurado, y si no la lectura SQL directa.
    | 'bridge' → siempre por HTTP firmado (accion voucher.redemptions).
    | 'sql'    → siempre leyendo la BD de PrestaShop (necesita HELPDESK_PS_DB).
    |
    | El bridge es la via de produccion: no exige que webadmin alcance la base
    | de la tienda. El SQL directo sirve mientras compartan MariaDB.
    */
    'redemption_source' => env('HELPDESK_BIRTHDAY_REDEMPTION_SOURCE', 'auto'),

    /*
    | Como se reconoce un bono de cumpleanos entre todos los cupones de la
    | tienda: por el nombre que PrestaShop guarda al aplicarlo. El literal real
    | es "Cheque cumpleanos generado desde la web"; se busca un trozo para no
    | depender de tildes ni de un cambio de redaccion.
    |
    | Se filtra por nombre y NO por lista de codigos porque 90 dias de campanas
    | son mas de 50.000 codigos, y porque asi aparecen tambien los canjes que no
    | se logra atribuir — que son informacion, no ruido.
    */
    'voucher_name_like' => env('HELPDESK_BIRTHDAY_VOUCHER_NAME_LIKE', 'cumplea'),

    /*
    | Timeout para las llamadas de canjes al bridge. Mas alto que el resto de
    | llamadas de HelpdeskPrestashop porque cada una trae hasta 500 canjes.
    */
    'bridge_timeout' => (int) env('HELPDESK_BIRTHDAY_BRIDGE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Caducidad de la campaña
    |--------------------------------------------------------------------------
    | Horas de gracia despues del dia del cumpleanos. Pasadas, lo que no haya
    | salido ya no sale: una felicitacion con un dia de retraso es peor que
    | ninguna, y el bono llevaria dias corriendo.
    |
    | La gracia existe para que un atasco de ultima hora (worker caido a las
    | 23:00) todavia se pueda resolver por la manana.
    */
    /*
    | A cuanta gente se avisa cuando una campana falla. Un fallo operativo se
    | arregla igual con diez avisos que con mil cuatrocientos — y esta base
    | tiene 1.412 usuarios con rol admin, asi que sin tope la notificacion
    | ahogaba la cola que sirve el tiempo real del helpdesk.
    */
    'failure_notification_limit' => (int) env('HELPDESK_BIRTHDAY_FAILURE_NOTIFY_LIMIT', 10),

    'expire_after_hours' => (int) env('HELPDESK_BIRTHDAY_EXPIRE_AFTER_HOURS', 6),

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
        | El 4 es el bono de cumpleaños: es el que usa el script que Álvarez
        | tiene en producción, y una generación de prueba contra el ERP real
        | (4-sep-2026) devolvió lo esperado — 5 € de importe, válido un mes,
        | estado "activo".
        |
        | Sin este valor no se puede generar un bono por cliente
        | (POST /api-gestion/generacion-bono/), solo repartir un código único a
        | todo el mundo con 'code'.
        */
        'bono_type_id' => (int) env('HELPDESK_BIRTHDAY_BONO_TYPE_ID', 4),

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
    | VACÍO A PROPÓSITO: mientras nadie confirme QUÉ idioma es cada id, todo el
    | mundo recibe el correo en fallback_language. Rellenarlo a ojo seria peor
    | que dejarlo: equivocarse con el id mayoritario manda el 97% de los correos
    | en el idioma que no es.
    |
    | LO QUE YA SE SABE (medido contra el ERP real el 7-sep-2026, sobre los
    | cumpleaneros de dos dias, 181 clientes):
    |
    |   ididioma = 2  ->  97% de los clientes
    |   ididioma = 7  ->   3% de los clientes
    |
    | Solo aparecen esos dos valores, asi que el mapa completo son dos lineas.
    | Falta UN dato y no se puede sacar de aqui: a que idioma corresponde cada
    | uno. El cruce por email con PrestaShop no sirve — los correos del dump
    | estan anonimizados (anon_NNNN@dominio.com) — asi que hay que preguntarlo
    | a Microserver o mirar la tabla de idiomas en Oracle.
    |
    | Siendo la tienda espanola, lo esperable es 2 => 'es'; PERO ESO ES UNA
    | SUPOSICION y por eso no esta escrito abajo.
    |
    | Una vez confirmado, la plantilla ya es multi-idioma via
    | mailer_template_langs: solo hay que traducirla en el admin de Mailer.
    |
    | Ejemplo: [2 => 'es', 7 => 'en']
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
