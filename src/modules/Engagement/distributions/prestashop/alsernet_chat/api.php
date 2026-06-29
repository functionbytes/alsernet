<?php

/**
 * Alsernet Chat — PrestaShop API endpoint (pull-on-demand)
 *
 * Receives signed POST requests from the Alsernet panel and returns
 * customer data on demand: profile, orders, returns, vouchers, cart,
 * messages, addresses, order detail, and order returns initiation.
 *
 * Auth:
 *   - HMAC-SHA256 of the raw body using ALSERNET_CHAT_WEBHOOK_SECRET
 *   - Signature in header `X-Alsernet-Signature`
 *   - Action in header `X-Alsernet-Action` (also accepted as body field)
 *
 * Lookup:
 *   - { "lookup": { "email": "...", "external_id": 123 } }
 *
 * Response:
 *   { "ok": true, "data": ... }
 *   { "ok": false, "error": "..." }
 */

require_once dirname(__FILE__).'/../../config/config.inc.php';

header('Content-Type: application/json; charset=utf-8');

$rawBody = file_get_contents('php://input');
$secret = (string) Configuration::get('ALSERNET_CHAT_WEBHOOK_SECRET');
$received = (string) ($_SERVER['HTTP_X_ALSERNET_SIGNATURE'] ?? '');

if (! $secret || ! $received || ! hash_equals(hash_hmac('sha256', $rawBody, $secret), $received)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'invalid signature']);
    exit;
}

$payload = json_decode($rawBody, true) ?: [];
$action = (string) (
    $_SERVER['HTTP_X_ALSERNET_ACTION']
    ?? $payload['action']
    ?? ''
);
$lookup = (array) ($payload['lookup'] ?? []);
$email = isset($lookup['email']) ? trim((string) $lookup['email']) : null;
$externalId = isset($lookup['external_id']) ? (int) $lookup['external_id'] : null;

try {
    $idCustomer = alsernet_resolve_customer($email, $externalId);

    $result = match ($action) {
        'customer.profile' => alsernet_customer_profile($idCustomer),
        'customer.orders' => alsernet_customer_orders($idCustomer),
        'customer.returns' => alsernet_customer_returns($idCustomer),
        'customer.vouchers' => alsernet_customer_vouchers($idCustomer),
        'customer.cart' => alsernet_customer_cart($idCustomer),
        'customer.messages' => alsernet_customer_messages($idCustomer),
        'customer.addresses' => alsernet_customer_addresses($idCustomer),
        'order.detail' => alsernet_order_detail((int) ($payload['order_id'] ?? 0), $idCustomer),
        'order.start_return' => alsernet_order_start_return(
            (int) ($payload['order_id'] ?? 0),
            (array) ($payload['items'] ?? []),
            $idCustomer
        ),
        default => null,
    };

    if ($result === null) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'error' => 'unknown action or customer not found']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => $result]);
} catch (Throwable $e) {
    PrestaShopLogger::addLog('Alsernet API error: '.$e->getMessage(), 3);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'internal error']);
}

/* ============================================================
 * Helpers
 * ============================================================ */

function alsernet_resolve_customer(?string $email, ?int $externalId): ?int
{
    if ($externalId) {
        $row = Db::getInstance()->getRow(
            'SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE id_customer = '.(int) $externalId.' AND deleted = 0'
        );
        if ($row) {
            return (int) $row['id_customer'];
        }
    }

    if ($email) {
        // Note: Db::getRow() already appends LIMIT 1 — do not add it manually.
        $row = Db::getInstance()->getRow(
            'SELECT id_customer FROM '._DB_PREFIX_.'customer WHERE email = "'.pSQL($email).'" AND deleted = 0'
        );
        if ($row) {
            return (int) $row['id_customer'];
        }
    }

    return null;
}

function alsernet_customer_profile(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $idLang = (int) Context::getContext()->language->id;

    $customer = Db::getInstance()->getRow(
        'SELECT c.id_customer, c.firstname, c.lastname, c.email, c.id_default_group, c.newsletter,
                c.optin, c.active, c.date_add, c.date_upd, c.birthday, c.id_lang, c.id_gender,
                cgl.name AS group_name
         FROM '._DB_PREFIX_.'customer c
         LEFT JOIN '._DB_PREFIX_.'group_lang cgl ON cgl.id_group = c.id_default_group AND cgl.id_lang = '.$idLang.'
         WHERE c.id_customer = '.(int) $idCustomer
    );

    if (! $customer) {
        return null;
    }

    $stats = Db::getInstance()->getRow(
        'SELECT COUNT(*) AS orders_count,
                COALESCE(SUM(total_paid_tax_incl), 0) AS total_spent,
                MIN(date_add) AS first_order_at,
                MAX(date_add) AS last_order_at
         FROM '._DB_PREFIX_.'orders
         WHERE id_customer = '.(int) $idCustomer.' AND valid = 1'
    );

    return [
        'id' => (int) $customer['id_customer'],
        'firstname' => $customer['firstname'],
        'lastname' => $customer['lastname'],
        'email' => $customer['email'],
        'birthday' => $customer['birthday'] !== '0000-00-00' ? $customer['birthday'] : null,
        'gender' => (int) $customer['id_gender'],
        'language_id' => (int) $customer['id_lang'],
        'newsletter' => (bool) $customer['newsletter'],
        'optin' => (bool) $customer['optin'],
        'active' => (bool) $customer['active'],
        'group' => [
            'id' => (int) $customer['id_default_group'],
            'name' => $customer['group_name'] ?? null,
        ],
        'created_at' => $customer['date_add'],
        'updated_at' => $customer['date_upd'],
        'metrics' => [
            'orders_count' => (int) $stats['orders_count'],
            'total_spent' => (float) $stats['total_spent'],
            'first_order_at' => $stats['first_order_at'] ?: null,
            'last_order_at' => $stats['last_order_at'] ?: null,
        ],
    ];
}

function alsernet_customer_orders(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $idLang = (int) Context::getContext()->language->id;

    $orders = Db::getInstance()->executeS(
        'SELECT o.id_order, o.reference, o.total_paid_tax_incl, o.total_products,
                o.total_shipping_tax_incl, o.total_discounts_tax_incl, o.payment,
                o.current_state, o.date_add, o.date_upd, o.id_currency,
                cur.iso_code AS currency_iso,
                osl.name AS state_name, os.color AS state_color
         FROM '._DB_PREFIX_.'orders o
         LEFT JOIN '._DB_PREFIX_.'order_state_lang osl ON osl.id_order_state = o.current_state AND osl.id_lang = '.$idLang.'
         LEFT JOIN '._DB_PREFIX_.'order_state os ON os.id_order_state = o.current_state
         LEFT JOIN '._DB_PREFIX_.'currency cur ON cur.id_currency = o.id_currency
         WHERE o.id_customer = '.(int) $idCustomer.'
         ORDER BY o.date_add DESC
         LIMIT 50'
    );

    return ['orders' => array_map('alsernet_format_order_summary', $orders ?: [])];
}

function alsernet_format_order_summary(array $o): array
{
    return [
        'id' => (int) $o['id_order'],
        'reference' => $o['reference'],
        'total' => (float) $o['total_paid_tax_incl'],
        'subtotal' => (float) $o['total_products'],
        'shipping' => (float) $o['total_shipping_tax_incl'],
        'discount' => (float) $o['total_discounts_tax_incl'],
        'currency' => $o['currency_iso'] ?: 'EUR',
        'payment' => $o['payment'],
        'state_id' => (int) $o['current_state'],
        'state_name' => $o['state_name'],
        'state_color' => $o['state_color'],
        'created_at' => $o['date_add'],
        'updated_at' => $o['date_upd'],
    ];
}

function alsernet_customer_returns(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $idLang = (int) Context::getContext()->language->id;

    $returns = Db::getInstance()->executeS(
        'SELECT r.id_order_return, r.id_order, r.state, r.question, r.date_add, r.date_upd,
                o.reference AS order_reference,
                ors.color AS state_color, orsl.name AS state_name
         FROM '._DB_PREFIX_.'order_return r
         INNER JOIN '._DB_PREFIX_.'orders o ON o.id_order = r.id_order
         LEFT JOIN '._DB_PREFIX_.'order_return_state ors ON ors.id_order_return_state = r.state
         LEFT JOIN '._DB_PREFIX_.'order_return_state_lang orsl ON orsl.id_order_return_state = r.state AND orsl.id_lang = '.$idLang.'
         WHERE r.id_customer = '.(int) $idCustomer.'
         ORDER BY r.date_add DESC'
    );

    if (! $returns) {
        return ['returns' => []];
    }

    $ids = array_column($returns, 'id_order_return');
    $idsList = implode(',', array_map('intval', $ids));

    $details = Db::getInstance()->executeS(
        'SELECT rd.id_order_return, rd.id_order_detail, rd.product_quantity,
                od.product_name, od.product_reference
         FROM '._DB_PREFIX_.'order_return_detail rd
         INNER JOIN '._DB_PREFIX_.'order_detail od ON od.id_order_detail = rd.id_order_detail
         WHERE rd.id_order_return IN ('.$idsList.')'
    );

    $detailsByReturn = [];
    foreach ($details ?: [] as $d) {
        $detailsByReturn[(int) $d['id_order_return']][] = [
            'order_detail_id' => (int) $d['id_order_detail'],
            'product_name' => $d['product_name'],
            'reference' => $d['product_reference'],
            'quantity' => (int) $d['product_quantity'],
        ];
    }

    return [
        'returns' => array_map(function (array $r) use ($detailsByReturn) {
            $rid = (int) $r['id_order_return'];

            return [
                'id' => $rid,
                'order_id' => (int) $r['id_order'],
                'order_reference' => $r['order_reference'],
                'state_id' => (int) $r['state'],
                'state_name' => $r['state_name'],
                'state_color' => $r['state_color'],
                'reason' => $r['question'],
                'created_at' => $r['date_add'],
                'updated_at' => $r['date_upd'],
                'items' => $detailsByReturn[$rid] ?? [],
            ];
        }, $returns),
    ];
}

function alsernet_customer_vouchers(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $vouchers = Db::getInstance()->executeS(
        'SELECT cr.id_cart_rule, cr.code, cr.description, cr.date_from, cr.date_to,
                cr.reduction_percent, cr.reduction_amount, cr.reduction_currency,
                cr.minimum_amount, cr.quantity, cr.quantity_per_user, cr.active,
                cr.free_shipping
         FROM '._DB_PREFIX_.'cart_rule cr
         WHERE cr.id_customer = '.(int) $idCustomer.'
         ORDER BY cr.date_to DESC
         LIMIT 100'
    );

    $now = date('Y-m-d H:i:s');

    return [
        'vouchers' => array_map(function (array $v) use ($now) {
            $isExpired = $v['date_to'] && $v['date_to'] < $now;

            return [
                'id' => (int) $v['id_cart_rule'],
                'code' => $v['code'],
                'description' => $v['description'],
                'reduction_percent' => (float) $v['reduction_percent'],
                'reduction_amount' => (float) $v['reduction_amount'],
                'free_shipping' => (bool) $v['free_shipping'],
                'minimum_amount' => (float) $v['minimum_amount'],
                'quantity' => (int) $v['quantity'],
                'quantity_per_user' => (int) $v['quantity_per_user'],
                'date_from' => $v['date_from'],
                'date_to' => $v['date_to'],
                'active' => (bool) $v['active'] && ! $isExpired,
                'expired' => $isExpired,
            ];
        }, $vouchers ?: []),
    ];
}

function alsernet_customer_cart(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $cart = Db::getInstance()->getRow(
        'SELECT id_cart, id_currency, id_lang, date_add, date_upd
         FROM '._DB_PREFIX_.'cart
         WHERE id_customer = '.(int) $idCustomer.'
         ORDER BY date_upd DESC'
    );

    if (! $cart) {
        return ['cart' => null];
    }

    $idCart = (int) $cart['id_cart'];
    $idLang = (int) $cart['id_lang'];

    $items = Db::getInstance()->executeS(
        'SELECT cp.id_product, cp.quantity, p.reference, p.price,
                pl.name, pl.link_rewrite
         FROM '._DB_PREFIX_.'cart_product cp
         INNER JOIN '._DB_PREFIX_.'product p ON p.id_product = cp.id_product
         LEFT JOIN '._DB_PREFIX_.'product_lang pl ON pl.id_product = cp.id_product AND pl.id_lang = '.$idLang.'
         WHERE cp.id_cart = '.$idCart
    );

    $total = 0.0;
    $products = [];
    foreach ($items ?: [] as $it) {
        $price = (float) $it['price'];
        $qty = (int) $it['quantity'];
        $total += $price * $qty;
        $products[] = [
            'id' => (int) $it['id_product'],
            'name' => $it['name'],
            'reference' => $it['reference'],
            'quantity' => $qty,
            'price' => $price,
        ];
    }

    return [
        'cart' => [
            'id' => $idCart,
            'created_at' => $cart['date_add'],
            'updated_at' => $cart['date_upd'],
            'products' => $products,
            'total' => $total,
            'item_count' => count($products),
        ],
    ];
}

function alsernet_customer_messages(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $messages = Db::getInstance()->executeS(
        'SELECT cm.id_customer_message, cm.id_customer_thread, cm.message, cm.date_add,
                ct.email, ct.id_order, ct.status
         FROM '._DB_PREFIX_.'customer_thread ct
         INNER JOIN '._DB_PREFIX_.'customer_message cm ON cm.id_customer_thread = ct.id_customer_thread
         WHERE ct.id_customer = '.(int) $idCustomer.'
         ORDER BY cm.date_add DESC
         LIMIT 50'
    );

    return [
        'messages' => array_map(fn (array $m) => [
            'id' => (int) $m['id_customer_message'],
            'thread_id' => (int) $m['id_customer_thread'],
            'order_id' => $m['id_order'] ? (int) $m['id_order'] : null,
            'subject' => $m['id_order'] ? 'Pedido #'.$m['id_order'] : 'Mensaje general',
            'status' => $m['status'],
            'message' => $m['message'],
            'created_at' => $m['date_add'],
        ], $messages ?: []),
    ];
}

function alsernet_customer_addresses(?int $idCustomer): ?array
{
    if (! $idCustomer) {
        return null;
    }

    $addresses = Db::getInstance()->executeS(
        'SELECT a.id_address, a.alias, a.firstname, a.lastname, a.company, a.address1,
                a.address2, a.postcode, a.city, a.phone, a.phone_mobile,
                a.id_country, a.id_state,
                cl.name AS country_name, sl.name AS state_name
         FROM '._DB_PREFIX_.'address a
         LEFT JOIN '._DB_PREFIX_.'country_lang cl
             ON cl.id_country = a.id_country AND cl.id_lang = '.(int) Context::getContext()->language->id.'
         LEFT JOIN '._DB_PREFIX_.'state sl ON sl.id_state = a.id_state
         WHERE a.id_customer = '.(int) $idCustomer.' AND a.deleted = 0
         ORDER BY a.date_upd DESC'
    );

    return [
        'addresses' => array_map(fn (array $a) => [
            'id' => (int) $a['id_address'],
            'alias' => $a['alias'],
            'firstname' => $a['firstname'],
            'lastname' => $a['lastname'],
            'company' => $a['company'] ?: null,
            'address' => trim($a['address1'].' '.$a['address2']),
            'postcode' => $a['postcode'],
            'city' => $a['city'],
            'state' => $a['state_name'] ?: null,
            'country' => $a['country_name'] ?: null,
            'phone' => $a['phone'] ?: $a['phone_mobile'] ?: null,
        ], $addresses ?: []),
    ];
}

function alsernet_order_detail(int $idOrder, ?int $idCustomer): ?array
{
    if (! $idOrder) {
        return null;
    }

    $idLang = (int) Context::getContext()->language->id;

    $order = Db::getInstance()->getRow(
        'SELECT o.*, osl.name AS state_name, os.color AS state_color, cur.iso_code AS currency_iso
         FROM '._DB_PREFIX_.'orders o
         LEFT JOIN '._DB_PREFIX_.'order_state_lang osl ON osl.id_order_state = o.current_state AND osl.id_lang = '.$idLang.'
         LEFT JOIN '._DB_PREFIX_.'order_state os ON os.id_order_state = o.current_state
         LEFT JOIN '._DB_PREFIX_.'currency cur ON cur.id_currency = o.id_currency
         WHERE o.id_order = '.$idOrder
    );

    if (! $order) {
        return null;
    }

    if ($idCustomer && (int) $order['id_customer'] !== $idCustomer) {
        return null;
    }

    $lines = Db::getInstance()->executeS(
        'SELECT id_order_detail, product_id, product_name, product_reference,
                product_quantity, unit_price_tax_incl, total_price_tax_incl, image_id
         FROM '._DB_PREFIX_.'order_detail
         WHERE id_order = '.$idOrder
    );

    $tracking = Db::getInstance()->executeS(
        'SELECT tracking_number, weight, date_add
         FROM '._DB_PREFIX_.'order_carrier
         WHERE id_order = '.$idOrder
    );

    $payments = Db::getInstance()->executeS(
        'SELECT payment_method, transaction_id, amount, date_add
         FROM '._DB_PREFIX_.'order_payment
         WHERE order_reference = "'.pSQL($order['reference']).'"'
    );

    return [
        'id' => (int) $order['id_order'],
        'reference' => $order['reference'],
        'customer_id' => (int) $order['id_customer'],
        'state_id' => (int) $order['current_state'],
        'state_name' => $order['state_name'],
        'state_color' => $order['state_color'],
        'currency' => $order['currency_iso'] ?: 'EUR',
        'totals' => [
            'subtotal' => (float) $order['total_products'],
            'shipping' => (float) $order['total_shipping_tax_incl'],
            'discount' => (float) $order['total_discounts_tax_incl'],
            'tax' => (float) $order['total_paid_tax_incl'] - (float) $order['total_paid_tax_excl'],
            'total' => (float) $order['total_paid_tax_incl'],
        ],
        'created_at' => $order['date_add'],
        'updated_at' => $order['date_upd'],
        'lines' => array_map(fn (array $l) => [
            'id' => (int) $l['id_order_detail'],
            'product_id' => (int) $l['product_id'],
            'name' => $l['product_name'],
            'reference' => $l['product_reference'],
            'quantity' => (int) $l['product_quantity'],
            'unit_price' => (float) $l['unit_price_tax_incl'],
            'total' => (float) $l['total_price_tax_incl'],
        ], $lines ?: []),
        'tracking' => $tracking ?: [],
        'payments' => $payments ?: [],
    ];
}

function alsernet_order_start_return(int $idOrder, array $items, ?int $idCustomer): ?array
{
    if (! $idOrder || empty($items)) {
        return null;
    }

    $order = Db::getInstance()->getRow(
        'SELECT id_order, id_customer FROM '._DB_PREFIX_.'orders WHERE id_order = '.$idOrder
    );

    if (! $order || ($idCustomer && (int) $order['id_customer'] !== $idCustomer)) {
        return null;
    }

    Db::getInstance()->insert('order_return', [
        'id_customer' => (int) $order['id_customer'],
        'id_order' => $idOrder,
        'state' => 1,
        'question' => isset($items['reason']) ? pSQL((string) $items['reason']) : '',
        'date_add' => date('Y-m-d H:i:s'),
        'date_upd' => date('Y-m-d H:i:s'),
    ]);

    $idReturn = (int) Db::getInstance()->Insert_ID();

    $detailItems = $items['lines'] ?? $items;
    foreach ($detailItems as $line) {
        if (! is_array($line)) {
            continue;
        }
        Db::getInstance()->insert('order_return_detail', [
            'id_order_return' => $idReturn,
            'id_order_detail' => (int) ($line['order_detail_id'] ?? 0),
            'id_customization' => 0,
            'product_quantity' => (int) ($line['quantity'] ?? 1),
        ]);
    }

    return ['id' => $idReturn, 'order_id' => $idOrder, 'state' => 1];
}
