<?php

return [
    'name' => 'HelpdeskErp',

    /*
     | URL base del proyecto manager (sin trailing slash).
     | Ejemplo: https://manager.test  o  http://192.168.1.3
     | La API ERP está en {manager_url}/api/erp/...
     */
    'manager_url' => env('ERP_MANAGER_URL', ''),

    /*
     | Token Sanctum emitido por el proyecto manager para el bridge Alsernet.
     | Generarlo en manager con:  php artisan erp:issue-bridge-token
     */
    'bridge_token' => env('ERP_BRIDGE_TOKEN', ''),

    /*
     | TTL en segundos para el contexto del cliente cuando se encuentra (default: 10 min).
     */
    'cache_ttl' => env('HELPDESK_ERP_CACHE_TTL', 600),

    /*
     | TTL en segundos para negative cache cuando el cliente no se encuentra (default: 1 min).
     | Evita golpear el manager repetidamente para emails inexistentes.
     */
    'miss_ttl' => env('HELPDESK_ERP_MISS_TTL', 60),

    /*
     | Segundos antes de la expiración real en los que se considera el caché stale
     | y se lanza un RefreshErpContextJob en background (stale-while-revalidate).
     */
    'stale_grace' => env('HELPDESK_ERP_STALE_GRACE', 60),

    /*
     | Timeout HTTP para llamadas a la API del manager (segundos).
     */
    'http_timeout' => env('HELPDESK_ERP_HTTP_TIMEOUT', 15),

    // Búsqueda de clientes (buscador externo): las búsquedas por nombre/
    // apellidos recorren CLIENTE_CENT entera (~14 s sin índice), no caben en
    // http_timeout.
    'search_timeout' => env('HELPDESK_ERP_SEARCH_TIMEOUT', 40),

    /*
     | Circuit breaker: nº de fallos consecutivos del manager (conexión rechazada
     | o timeout) tras los que se corta el tráfico ERP. Con el manager caído cada
     | request cuelga hasta http_timeout; el breaker evita arrastrar el inbox.
     */
    'circuit_failure_threshold' => env('HELPDESK_ERP_CIRCUIT_FAILURE_THRESHOLD', 5),

    /*
     | Circuit breaker: segundos que permanece abierto (se saltan las llamadas y
     | se devuelve contexto vacío) antes de reintentar contra el manager.
     |
     | Debe cubrir con margen el peor caso para ACUMULAR el umbral de fallos:
     | Cache::add() fija el TTL de la ventana solo en el primer fallo, y los
     | incrementos posteriores NO lo renuevan (HasCircuitBreaker::recordFailure()).
     | Con http_timeout=15s y circuit_failure_threshold=5, el manager caído tarda
     | hasta 5×15=75s en generar los 5 fallos que abren el breaker — con una
     | ventana de 30s (el valor anterior) la clave de caché expiraba y el
     | contador volvía a cero antes de llegar al umbral, así que el breaker
     | JAMÁS llegaba a abrirse de verdad con una caída real (solo en los tests,
     | que fallan al instante con Http::fake() en vez de colgar 15s). Bug real
     | encontrado 4-sep-2026: WarmErpCacheJob (5 emails secuenciales, timeout
     | 60s) llevaba media hora reventando su propio timeout sin que el breaker
     | interviniera nunca, monopolizando el único worker que atiende
     | 'notifications' (ver reference_helpdesk_erp_warmcache_infinite_loop).
     */
    'circuit_open_seconds' => env('HELPDESK_ERP_CIRCUIT_OPEN_SECONDS', 120),

    /*
     | TTL en segundos para la línea temporal agregada del cliente (ERP + PrestaShop + Helpdesk).
     | TTL corto: solo evita re-agregar las tres fuentes en visitas repetidas (default: 2 min).
     | El webhook orders-ready invalida esta entrada al terminar el escaneo Oracle.
     */
    'timeline_cache_ttl' => env('HELPDESK_ERP_TIMELINE_CACHE_TTL', 120),

    /*
     | Máximo de pedidos ERP a recuperar.
     */
    'orders_limit' => 10,

    /*
     | Máximo de facturas ERP a recuperar.
     */
    'invoices_limit' => 5,

    /*
     | Secret HMAC compartido con el proyecto manager para validar webhooks
     | que notifican que el escaneo de pedidos Oracle ha terminado.
     | Generar con: openssl rand -hex 32
     */
    'webhook_secret' => env('ERP_WEBHOOK_SECRET', ''),

    /*
     | Segundos que espera la sonda de salud antes de dar el ERP por caído.
     |
     | Estaba fijada en 3 y una búsqueda real tarda entre 3,4 y 6,5 segundos
     | contra Oracle (medido el 7-sep-2026), así que la sonda expiraba siempre y
     | el panel daba el ERP por degradado de forma permanente mientras
     | funcionaba con normalidad.
     |
     | 10 segundos cubren ese rango con margen sin dejar la sonda colgada: por
     | encima de eso el ERP está de verdad para pocas cosas, y ahí "degradado"
     | es la respuesta correcta.
     */
    'health_timeout' => env('HELPDESK_ERP_HEALTH_TIMEOUT', 10),

    /*
     | Enfriamiento, en minutos, antes de volver a buscar en el ERP un cliente
     | que ya se buscó y no apareció. Sin esto, cada correo de un remitente que
     | no es cliente (proveedores, notificaciones, spam que pasa el filtro)
     | vuelve a consultar el manager. La cola helpdesk-erp comparte procesos de
     | supervisor con otras veinte colas, así que este freno importa.
     |
     | El estado vive en helpdesk_customers.erp_lookup_status/erp_lookup_at.
     | Un reintento pedido a mano por el agente lo ignora.
     */
    'lookup_cooldown_minutes' => env('HELPDESK_ERP_LOOKUP_COOLDOWN', 1440),

    /*
     | Enfriamiento más corto cuando el intento anterior falló por caída del
     | ERP en vez de por no existir el cliente: ahí el reintento sí tiene
     | sentido pronto.
     */
    'lookup_error_cooldown_minutes' => env('HELPDESK_ERP_LOOKUP_ERROR_COOLDOWN', 30),

    /*
     | Gestión dentro del chat (ErpChatService): caché por sección y cliente.
     | Segundos según el estado normalizado de la respuesta del manager.
     | - ok: listas y fichas del cliente.
     | - detail: detalle de pedido / albarán / factura (cambian poco).
     | - blocked: sección sin GRANT en Oracle; no cambia hasta que el DBA actúe.
     | - unavailable: endpoint inexistente (404) en esta versión del manager.
     | - down: manager caído o timeout; corto para reintentar pronto.
     | Una respuesta "loading" (escaneo de pedidos en curso) nunca se cachea.
     */
    'chat_ttl' => [
        'ok' => (int) env('HELPDESK_ERP_CHAT_TTL_OK', 300),
        'detail' => (int) env('HELPDESK_ERP_CHAT_TTL_DETAIL', 1800),
        'blocked' => (int) env('HELPDESK_ERP_CHAT_TTL_BLOCKED', 600),
        'unavailable' => (int) env('HELPDESK_ERP_CHAT_TTL_UNAVAILABLE', 300),
        'down' => (int) env('HELPDESK_ERP_CHAT_TTL_DOWN', 30),
    ],

    /*
     | Primera página de pedidos que trae el resumen del panel (overview).
     */
    'chat_overview_orders_limit' => (int) env('HELPDESK_ERP_CHAT_OVERVIEW_ORDERS', 10),

    /*
     | Días de antelación con los que se avisa de que un vale o bono caduca.
     */
    'chat_expiry_warning_days' => (int) env('HELPDESK_ERP_CHAT_EXPIRY_DAYS', 7),

    /*
     | Descripciones de respaldo de los códigos del ERP. El manager ya las
     | resuelve contra Oracle (campos *_description); estas solo se usan si una
     | versión anterior del manager no las trae o la tabla no es legible.
     | Valores copiados de las tablas de Oracle el 24-sep-2026.
     */
    'chat_codes' => [
        'warehouse' => [
            '1' => 'POCOMACO', '2' => 'MONTERROSO', '3' => 'TIENDA CAPITAN HAYA', '4' => 'TIENDA DIEGO DE LEON',
            '5' => 'TIENDA POCOMACO', '6' => 'ONLINE', '100000003' => 'POCOMACO-C.H.', '100000004' => 'POCOMACO-D.L',
            '100000020' => 'GOLF CAMPOMAR',
        ],
        'origin' => [
            '1' => 'TELEFONO', '2' => 'FAX', '3' => 'CUPON', '4' => 'INTERNET', '5' => 'EMAIL', '6' => 'DEVOLUCIONES',
            '100000000' => 'MADRID', '100000020' => 'MONTERROSO', '100000040' => 'RECLAMACIÓN', '100000041' => 'REPOSICIÓN',
            '100000060' => 'RECUPERADOS', '100000080' => 'EMPLEADO', '100000100' => 'LICENCIAS', '100000120' => 'CLICK TO CALL',
            '100000140' => 'TELEVISION', '100000160' => 'AMAZON', '100000180' => 'BAJADA', '100000200' => 'CONCURSO',
            '100000201' => 'TIENDA POCOMACO', '100000220' => 'REPARACIONES', '100000240' => 'REMARKETING',
            '100000260' => 'FINANCIADO', '100000280' => 'FINSI',
        ],
        'catalog' => [
            '1' => 'Caza', '3' => 'Golf', '5' => 'Pesca', '7' => 'Nautica', '10' => 'Hipica', '14' => 'Esqui',
            '15' => 'Submarinismo', '20' => 'Arqueria', '24' => 'Taller caza Madrid', '28' => 'Curso Buceo',
            '30' => 'Licencias', '31' => 'Padel', '100000000' => 'Varios', '100000020' => 'Lotería', '100000040' => 'Generico',
        ],
        // PEDIDOCLIESTADO (estado del pedido en la lista).
        'order_status' => [
            '0' => 'Anulado', '1' => 'Creación', '2' => 'Revisión transportista', '3' => 'Aceptación financiera',
            '4' => 'Pendiente de mercancía', '5' => 'Listo para servir', '6' => 'Sirviéndose', '7' => 'Servido',
            '8' => 'Incidencia', '9' => 'Aceptación financiera reservando', '10' => 'Servido parcialmente',
            '11' => 'Pendiente transferencia',
        ],
        // ALBARANCLI_CENTRAL.TIPO: TIPOALBARANCLI no tiene los tipos 1/3/4 (venta,
        // devolución, abono), que son los que usa la tienda.
        'delivery_type' => [
            '1' => 'Venta', '3' => 'Devolución', '4' => 'Abono', '5' => 'Genérico', '7' => 'Ventas TPV antiguo',
            '9' => 'Devolución agencia', '10' => 'Anticipo pedido TPV',
        ],
    ],

    /*
     | Seguimiento de envíos: plantillas de URL por transportista ({tracking}
     | se sustituye por el número de envío, codificado). El normalizador de
     | /orders/{id}/shipping rellena tracking_url si el manager trae
     | transportista y número pero no la URL. Hoy carrier sale null (la tabla
     | de envíos de IDENVIO no tiene GRANT): queda listo para cuando lo tenga.
     |
     | - carrier_ids: IDTRANSPORTISTA de Oracle (tabla TRANSPORTISTA) => plantilla.
     | - aliases: texto dentro del nombre del transportista => plantilla (gana
     |   el alias más largo: "correos express" antes que "correos").
     */
    'tracking' => [
        'templates' => [
            'seur' => 'https://www.seur.com/livetracking/?segOnlineIdentificador={tracking}&segOnlineIdioma=es',
            'mrw' => 'https://www.mrw.es/seguimiento_envios/MRW_resultados_consultas.asp?modo=nacional&envio={tracking}',
            'correos' => 'https://www.correos.es/es/es/herramientas/localizador/envios/detalle?tracking-number={tracking}',
            'correosexpress' => 'https://s.correosexpress.com/c?n={tracking}',
            'gls' => 'https://gls-group.com/ES/es/seguimiento-envio/?match={tracking}',
            'ctt' => 'https://www.cttexpress.com/localizador-de-envios/?sc={tracking}',
            'dhl' => 'https://www.dhl.com/es-es/home/tracking/tracking-express.html?tracking-id={tracking}',
            'ups' => 'https://www.ups.com/track?loc=es_ES&tracknum={tracking}',
            'nacex' => 'https://www.nacex.es/seguimientoDetalle.do?agencia_origen=&numero_albaran={tracking}',
            'inpost' => 'https://inpost.es/seguimiento-del-envio/?number={tracking}',
            'schenker' => 'https://www.dbschenker.com/app/tracking-public/?refNumber={tracking}',
        ],
        'carrier_ids' => [
            '4' => 'seur',
            '9' => 'ups',
            '21' => 'correosexpress',
            '100000001' => 'nacex',
            '100000045' => 'mrw',
            '100000164' => 'correosexpress',
            '100000165' => 'correosexpress',
            '100000223' => 'schenker',
            '100000283' => 'inpost',
        ],
        'aliases' => [
            'seur' => 'seur',
            'mrw' => 'mrw',
            'correos' => 'correos',
            'correos express' => 'correosexpress',
            'correosexpress' => 'correosexpress',
            'chronoexpres' => 'correosexpress',
            'gls' => 'gls',
            'ctt' => 'ctt',
            'ctt express' => 'ctt',
            'tourline' => 'ctt',
            'dhl' => 'dhl',
            'ups' => 'ups',
            'nacex' => 'nacex',
            'inpost' => 'inpost',
            'schenker' => 'schenker',
        ],
    ],
];
