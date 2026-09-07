<?php

/**
 * alsernetbridge — Voucher (bono) helpers
 *
 * Lecturas de solo lectura sobre el canje de bonos en la tienda, para que el
 * panel de Alsernet (webadmin, modulo HelpdeskBirthday) pueda medir cuanta
 * gente uso su bono, cuanto dinero movio y si Gestion llego a descontarlo, sin
 * conectarse directamente a la base de datos de PrestaShop.
 *
 * POR QUE NO VALE `customer.vouchers`
 * -----------------------------------
 * Esa accion filtra por `cart_rule.id_customer`, y los bonos de Gestion se
 * crean con `id_customer = 0` (CartRule::createCartRuleAlvarez no los ata a
 * nadie): devolveria una lista vacia siempre, para todos los clientes.
 *
 * CUANDO EXISTE EL BONO EN PRESTASHOP
 * -----------------------------------
 * No antes de usarse. Gestion emite el bono y el cliente lo recibe por correo,
 * pero la `cart_rule` no nace hasta que alguien teclea el codigo en el carrito,
 * y nace con `date_from` = ese mismo dia. Asi que aqui NO se puede responder
 * "existe y es valido" sobre un bono recien emitido: solo se sabe de los que ya
 * se gastaron. Quien quiera el estado de un bono sin canjear tiene que
 * preguntarselo a Gestion (GET /api-gestion/bono/{id}/), no a la tienda.
 *
 * LA `cart_rule` NO SOBREVIVE AL CANJE  <-- lo que condiciona esta consulta
 * -----------------------------------
 * PrestaShop borra la regla cuando se consume. Medido sobre la tienda real:
 * de los 1.116 cheques de cumpleanos canjeados, **solo 4 conservan su fila en
 * `cart_rule`**. Asi que unir por INNER JOIN a esa tabla —o buscar por
 * `cart_rule.code`— pierde el 99,6% de los canjes.
 *
 * Lo que siempre queda es `order_cart_rule`, atado al pedido, con el importe
 * descontado y el nombre del cupon. Por eso el ancla de la consulta es esa
 * tabla, la union con `cart_rule` es LEFT, y el codigo se reconstruye de donde
 * haya sobrevivido:
 *
 *   1. `cart_rule.code`, mientras la regla siga viva ......... 4 casos
 *   2. `marcarbono.bono` + `codigo_verificacion`, si Gestion
 *      llego a registrar el consumo ......................... 765 casos
 *   3. En ningun sitio ...................................... 363 casos
 *
 * Los del grupo 3 son canjes reales y hay que devolverlos igual: se identifican
 * por el pedido y por el email del cliente, que es lo que permite atribuirlos.
 * Devolverlos con `code` a null y dejar que quien pregunta decida es mejor que
 * esconderlos, que es lo que hacia el INNER JOIN.
 *
 * COMO SE IDENTIFICA UN BONO DE CUMPLEANOS
 * ----------------------------------------
 * Por el nombre que PrestaShop guarda en `order_cart_rule.name` — "Cheque
 * cumpleanos generado desde la web" — y no por una lista de codigos. Filtrar
 * por nombre y fecha trae todos los canjes de un periodo en una consulta;
 * pasar codigos serian decenas de miles de valores en un IN, y ademas se
 * perderian justo los canjes sin codigo recuperable. `codes` existe igualmente
 * para comprobar bonos concretos, y busca en las dos fuentes.
 *
 * Las dos funciones son lecturas puras: no escriben nada.
 */

if (! defined('_PS_VERSION_')) {
    exit;
}

/** Tope duro de filas por peticion, pase lo que pase en el payload. */
const ALSERNET_VOUCHER_MAX_LIMIT = 500;

/**
 * Canjes de bono en un rango de fechas.
 *
 * @param  array<string, mixed>  $opts  from, to, name_like, codes, limit, offset
 * @return array<string, mixed>  Siempre un array: devolver null haria que api.php
 *                               respondiera 404 "unknown action".
 */
function alsernet_voucher_redemptions(array $opts)
{
    $db = Db::getInstance();
    $pfx = _DB_PREFIX_;

    $limit = isset($opts['limit']) ? (int) $opts['limit'] : 200;
    $limit = max(1, min(ALSERNET_VOUCHER_MAX_LIMIT, $limit));
    $offset = isset($opts['offset']) ? max(0, (int) $opts['offset']) : 0;

    $where = ['ocr.deleted = 0'];

    // Fechas: se validan como Y-m-d y se comparan contra el dia completo. Sin
    // rango la consulta barreria tres anios de pedidos.
    $from = alsernet_voucher_date($opts, 'from');
    $to = alsernet_voucher_date($opts, 'to');

    if ($from !== null) {
        $where[] = 'o.date_add >= "' . pSQL($from) . ' 00:00:00"';
    }

    if ($to !== null) {
        $where[] = 'o.date_add <= "' . pSQL($to) . ' 23:59:59"';
    }

    // El discriminante habitual: el nombre del cupon tal como lo escribe la
    // tienda al aplicarlo.
    if (! empty($opts['name_like'])) {
        $where[] = 'ocr.name LIKE "%' . pSQL((string) $opts['name_like']) . '%"';
    }

    // Y, opcionalmente, unos codigos concretos.
    $codes = alsernet_voucher_codes($opts);

    if ($codes !== null) {
        if ($codes === []) {
            return ['redemptions' => [], 'total' => 0, 'has_more' => false];
        }

        // En las dos fuentes: la regla viva, o el bono que Gestion registro.
        $in = '"' . implode('","', $codes) . '"';
        $where[] = '(cr.code IN (' . $in . ') OR CONCAT(mb.bono, "-", mb.codigo_verificacion) IN (' . $in . '))';
    }

    $whereSql = implode(' AND ', $where);

    // order_cart_rule es el ancla y `orders` el unico INNER: las otras dos son
    // LEFT porque pueden no existir, y con INNER desaparecian 1.112 de 1.116.
    $joins = ' FROM ' . $pfx . 'order_cart_rule ocr'
        . ' INNER JOIN ' . $pfx . 'orders o ON o.id_order = ocr.id_order'
        . ' LEFT JOIN ' . $pfx . 'cart_rule cr ON cr.id_cart_rule = ocr.id_cart_rule'
        // Por id_cart_rule y NO por id_order: un pedido puede llevar dos bonos,
        // y unir por pedido cruzaba cada linea de descuento con cada registro de
        // Gestion. Sobre la tienda real eso inflaba 1.116 canjes a 1.132, es
        // decir, 16 canjes contados dos veces y su importe con ellos.
        . ' LEFT JOIN ' . $pfx . 'marcarbono mb ON mb.id_cart_rule = ocr.id_cart_rule AND mb.deleted = 0';

    $total = (int) $db->getValue('SELECT COUNT(*)' . $joins . ' WHERE ' . $whereSql);

    $lang = (int) Context::getContext()->language->id ?: 1;

    $rows = $db->executeS(
        'SELECT ocr.id_order_cart_rule AS line_id,
                COALESCE(cr.code, CONCAT(mb.bono, "-", mb.codigo_verificacion)) AS code,
                cr.code AS live_code,
                cr.id_cart_rule, cr.date_from, cr.date_to, cr.quantity,
                cr.reduction_amount, cr.reduction_percent,
                o.id_order, o.reference, o.total_paid, o.date_add, o.valid, o.current_state,
                c.id_customer, c.email,
                ocr.name AS voucher_name, ocr.value AS discount, ocr.value_tax_excl AS discount_excl,
                osl.name AS order_state,
                mb.bono AS erp_bono, mb.codigo_verificacion AS erp_verification,
                mb.operacion AS erp_operation, mb.erp_response, mb.importe_venta AS erp_sale_amount,
                mb.date_add AS erp_marked_at'
        . $joins
        . ' LEFT JOIN ' . $pfx . 'customer c ON c.id_customer = o.id_customer'
        . ' LEFT JOIN ' . $pfx . 'order_state_lang osl'
        . '        ON osl.id_order_state = o.current_state AND osl.id_lang = ' . $lang
        . ' WHERE ' . $whereSql
        . ' ORDER BY o.date_add DESC'
        . ' LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset
    );

    $redemptions = array_map(function (array $r) {
        // "Marcado en Gestion" es que respondiera OK, no que exista la fila:
        // marcarbono guarda tambien los intentos que el ERP rechazo.
        $response = isset($r['erp_response']) ? trim((string) $r['erp_response']) : '';

        // De donde salio el codigo, para que quien atribuye sepa cuanto fiarse:
        // 'cart_rule' es el codigo tal cual lo tecleo el cliente; 'erp' viene
        // del registro de Gestion; null es un canje real cuyo codigo ya no
        // consta en ninguna parte y solo se puede atribuir por el email.
        $codeSource = null;

        if (! empty($r['live_code'])) {
            $codeSource = 'cart_rule';
        } elseif (! empty($r['erp_bono'])) {
            $codeSource = 'erp';
        }

        return [
            // La clave de la LINEA de descuento. Es lo unico que identifica un
            // canje siempre: la cart_rule desaparece al consumirse, y un pedido
            // puede llevar dos bonos, asi que el id del pedido no basta.
            'line_id' => (int) $r['line_id'],
            'code' => $r['code'],
            'code_source' => $codeSource,
            'cart_rule_id' => (int) $r['id_cart_rule'],
            'voucher_name' => $r['voucher_name'],
            'valid_from' => $r['date_from'],
            'valid_to' => $r['date_to'],
            // 0 = ya no le quedan usos.
            'uses_left' => (int) $r['quantity'],
            'amount' => (float) $r['reduction_amount'],
            'percent' => (float) $r['reduction_percent'],

            'order_id' => (int) $r['id_order'],
            'order_reference' => $r['reference'],
            'order_total' => (float) $r['total_paid'],
            'order_date' => $r['date_add'],
            'order_valid' => (bool) $r['valid'],
            'order_state' => $r['order_state'],
            'order_state_id' => (int) $r['current_state'],

            'customer_id' => (int) $r['id_customer'],
            'customer_email' => $r['email'],

            'discount' => (float) $r['discount'],
            'discount_excl' => (float) $r['discount_excl'],

            'erp' => [
                'marked' => $response !== '' && Tools::strtolower($response) === 'ok',
                'bono' => $r['erp_bono'],
                'verification' => $r['erp_verification'],
                'operation' => $r['erp_operation'] !== null ? (int) $r['erp_operation'] : null,
                'response' => $r['erp_response'],
                'sale_amount' => $r['erp_sale_amount'] !== null ? (float) $r['erp_sale_amount'] : null,
                'marked_at' => $r['erp_marked_at'],
            ],
        ];
    }, $rows ?: []);

    return [
        'redemptions' => $redemptions,
        'total' => $total,
        'has_more' => ($offset + count($redemptions)) < $total,
    ];
}

/**
 * Estado en la tienda de unos codigos concretos.
 *
 * Responde solo lo que la tienda puede saber: si la regla existe (o sea, si
 * alguien llego a teclear el codigo), su validez y si le quedan usos. Un codigo
 * ausente NO significa "invalido", significa "aqui todavia no ha aparecido" —
 * que es el estado normal de un bono recien emitido por Gestion.
 *
 * @param  array<int, string>  $codes
 * @return array<string, mixed>
 */
function alsernet_voucher_status(array $codes)
{
    $clean = alsernet_voucher_clean_codes($codes);

    if ($clean === []) {
        return ['vouchers' => []];
    }

    $pfx = _DB_PREFIX_;
    $now = date('Y-m-d H:i:s');

    $rows = Db::getInstance()->executeS(
        'SELECT cr.code, cr.id_cart_rule, cr.date_from, cr.date_to, cr.quantity,
                cr.reduction_amount, cr.active,
                ocr.id_order, o.reference, o.date_add AS order_date
         FROM ' . $pfx . 'cart_rule cr
         LEFT JOIN ' . $pfx . 'order_cart_rule ocr ON ocr.id_cart_rule = cr.id_cart_rule AND ocr.deleted = 0
         LEFT JOIN ' . $pfx . 'orders o ON o.id_order = ocr.id_order
         WHERE cr.code IN ("' . implode('","', $clean) . '")'
    );

    $found = [];

    foreach ($rows ?: [] as $r) {
        $expired = $r['date_to'] && $r['date_to'] < $now;

        $found[$r['code']] = [
            'code' => $r['code'],
            'known' => true,
            'cart_rule_id' => (int) $r['id_cart_rule'],
            'active' => (bool) $r['active'] && ! $expired,
            'expired' => $expired,
            'uses_left' => (int) $r['quantity'],
            'amount' => (float) $r['reduction_amount'],
            'valid_from' => $r['date_from'],
            'valid_to' => $r['date_to'],
            'redeemed' => $r['id_order'] !== null,
            'order_id' => $r['id_order'] !== null ? (int) $r['id_order'] : null,
            'order_reference' => $r['reference'],
            'order_date' => $r['order_date'],
        ];
    }

    // Los que no aparecen se devuelven igualmente, para que quien pregunta no
    // tenga que adivinar si faltan porque no existen o porque se perdieron.
    $out = [];

    foreach ($clean as $code) {
        $out[] = isset($found[$code]) ? $found[$code] : [
            'code' => $code,
            'known' => false,
            'redeemed' => false,
        ];
    }

    return ['vouchers' => $out];
}

/**
 * Una fecha del payload, validada como Y-m-d. Cualquier otra cosa se ignora en
 * vez de colarse en el SQL.
 *
 * @param  array<string, mixed>  $opts
 * @return string|null
 */
function alsernet_voucher_date(array $opts, $key)
{
    if (empty($opts[$key])) {
        return null;
    }

    $value = substr((string) $opts[$key], 0, 10);
    $date = DateTime::createFromFormat('Y-m-d', $value);

    return ($date && $date->format('Y-m-d') === $value) ? $value : null;
}

/**
 * Los codigos del payload, o null si no se paso ninguno (que significa "no
 * filtres por codigo", distinto de "filtra por lista vacia").
 *
 * @param  array<string, mixed>  $opts
 * @return array<int, string>|null
 */
function alsernet_voucher_codes(array $opts)
{
    if (! isset($opts['codes'])) {
        return null;
    }

    return alsernet_voucher_clean_codes((array) $opts['codes']);
}

/**
 * @param  array<int, mixed>  $codes
 * @return array<int, string>
 */
function alsernet_voucher_clean_codes(array $codes)
{
    $clean = [];

    foreach ($codes as $code) {
        $code = trim((string) $code);

        // Formato real de los bonos de Gestion y de los cupones de campana:
        // alfanumerico, guiones y guiones bajos. Se filtra antes de pSQL para
        // que un codigo raro no llegue siquiera a la consulta.
        if ($code !== '' && preg_match('/^[A-Za-z0-9_-]{1,64}$/', $code)) {
            $clean[pSQL($code)] = pSQL($code);
        }

        if (count($clean) >= ALSERNET_VOUCHER_MAX_LIMIT * 4) {
            break;
        }
    }

    return array_values($clean);
}
