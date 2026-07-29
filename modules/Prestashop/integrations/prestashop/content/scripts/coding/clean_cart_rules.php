<?php
/**
 * ISC License
 *
 * Copyright (c) 2024 idnovate.com
 * idnovate is a Registered Trademark & Property of idnovate.com, innovación y desarrollo SCP
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
 * REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
 * INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
 * LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
 * OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
 * PERFORMANCE OF THIS SOFTWARE.
 *
 * @author    idnovate
 * @copyright 2024 idnovate
 * @license   https://www.isc.org/licenses/ https://opensource.org/licenses/ISC ISC License
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
set_time_limit(0);

error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

echo date('Y-m-d H:i:s') . ' - Starting' . '<br>';

$sql = "SELECT id_cart_rule FROM " . _DB_PREFIX_ . "cart_rule acr WHERE quantity = 0 AND acr.date_upd < DATE_SUB(NOW(), INTERVAL 30 DAY)";
$cart_rules = Db::getInstance()->executeS($sql);

echo 'Remaining cart rules to remove quantity = 0 from table  ' . _DB_PREFIX_ . 'cart_rule`: ' . count($cart_rules) . '<br>';
//Eliminar cart rules que se crean via integracion
foreach ($cart_rules as $cart_rule) {
    $cartRule = new CartRule((int)$cart_rule['id_cart_rule']);
    if (Validate::isLoadedObject($cartRule)) {
        $cartRule->delete();
        echo "CART RULE ELIMINADA => " . (int)$cart_rule['id_cart_rule'] . "\n";
    } else {
        echo 'ERROR CARGANDO CART RULE => ' . (int)$cart_rule['id_cart_rule'] . "\n";
    }
}


// die();
// Count rules from already generated orders
$query = 'SELECT count(*) as count
FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON (qdrc.id_cart = c.id_cart)
LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_cart = c.id_cart)
WHERE o.id_order IS NOT null;';


$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
echo 'Remaining cart rules to remove from table ' . _DB_PREFIX_ . 'quantity_discount_rule_cart`: ' . $result['count'] . '<br>';

$last = false;
if ($result['count'] == 0) {
    $last = true;
}

// Remove rules from already generated orders
echo 'Remove rules from already generated orders' . '<br>';
$query = 'SELECT DISTINCT(qdrc.`id_cart_rule`)
FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON (qdrc.id_cart = c.id_cart)
LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.id_cart = c.id_cart)
WHERE o.id_order IS NOT null
ORDER BY c.date_add DESC
LIMIT 50;';

$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
foreach ($result as $rule) {
    echo date('Y-m-d H:i:s') . ' - Remove rule ' . (int)$rule['id_cart_rule'] . '<br>';
    $cartRule = new CartRule((int)$rule['id_cart_rule']);
    if (Validate::isLoadedObject($cartRule)) {
        $cartRule->delete();
    } else {
        echo 'Error loading cart rule ' . (int)$rule['id_cart_rule'] . '<br>';
    }
}

// Carts
// Count rules from carts olders than 30 days
$query = 'SELECT count(*) as count
FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON (qdrc.id_cart = c.id_cart)
WHERE c.date_upd < DATE_SUB(NOW(), INTERVAL 30 DAY);';

$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($query);
echo 'Remaining cart rules to remove from table ' . _DB_PREFIX_ . 'quantity_discount_rule_cart`: ' . $result['count'] . '<br>';

if ($last && $result['count'] == 0) {
    $last = true;
} else {
    $last = false;
}

echo 'Remove rules from carts olders than 30 days' . '<br>';
$query = 'SELECT c.`id_cart`, qdrc.`id_cart_rule`
FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN `' . _DB_PREFIX_ . 'cart` c ON (qdrc.id_cart = c.id_cart)
WHERE c.date_upd < DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY c.date_add ASC
LIMIT 50;';
$result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($query);
foreach ($result as $rule) {
    echo date('Y-m-d H:i:s') . ' - Remove rule ' . (int)$rule['id_cart_rule'] . ' from cart ' . (int)$rule['id_cart'] . '<br>';
    $cartRule = new CartRule((int)$rule['id_cart_rule']);
    if (Validate::isLoadedObject($cartRule)) {
        $cartRule->delete();
    } else {
        echo 'Error loading cart rule ' . (int)$rule['id_cart_rule'] . '<br>';
    }
}

//Orphanated records

/*$query = 'DELETE FROM `'._DB_PREFIX_.'quantity_discount_rule_cart`
    WHERE `id_cart_rule` NOT IN (SELECT `id_cart_rule` FROM `'._DB_PREFIX_.'cart_rule`);';*/

$query = 'DELETE qdrc FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON qdrc.id_cart_rule= cr.id_cart_rule
WHERE cr.id_cart_rule IS NULL';

Db::getInstance()->execute($query);

/*$query = 'DELETE FROM `'._DB_PREFIX_.'quantity_discount_rule_cart`
    WHERE `id_cart` NOT IN (SELECT `id_cart` FROM `'._DB_PREFIX_.'cart`);';*/

$query = 'DELETE qdrc FROM `' . _DB_PREFIX_ . 'quantity_discount_rule_cart` qdrc
LEFT JOIN ' . _DB_PREFIX_ . 'cart c ON qdrc.id_cart= c.id_cart
WHERE c.id_cart IS NULL';

Db::getInstance()->execute($query);

if ($last) {
    /*$query = 'DELETE FROM `'._DB_PREFIX_.'quantity_discount_rule_order`
    WHERE `id_cart_rule` NOT IN (SELECT `id_cart_rule` FROM `'._DB_PREFIX_.'cart_rule`);';*/

    // Don't execute this query, it restarts the times used
    /*$query = 'DELETE qdro FROM `'._DB_PREFIX_.'quantity_discount_rule_order` qdro
    LEFT JOIN '._DB_PREFIX_.'order_cart_rule ocr ON qdro.id_cart_rule = ocr.id_cart_rule
    WHERE ocr.id_cart_rule IS NULL';

    Db::getInstance()->execute($query);*/

    $query = 'DELETE crc FROM `' . _DB_PREFIX_ . 'cart_rule_combination` crc
    LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON crc.id_cart_rule_1 = cr.id_cart_rule
    WHERE cr.id_cart_rule IS NULL';

    Db::getInstance()->execute($query);

    $query = 'DELETE crc FROM `' . _DB_PREFIX_ . 'cart_rule_combination` crc
    LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON crc.id_cart_rule_2 = cr.id_cart_rule
    WHERE cr.id_cart_rule IS NULL';

    Db::getInstance()->execute($query);

    /**
     * Limpiar carritos antiguos
     */
    $query = 'DELETE FROM ' . _DB_PREFIX_ . 'cart_product WHERE id_cart IN (SELECT c.id_cart FROM ' . _DB_PREFIX_ . 'cart AS c LEFT JOIN ' . _DB_PREFIX_ . 'orders AS o ON (c.id_cart = o.id_cart)
                                                                 WHERE o.id_order IS NULL AND c.date_upd  <  DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY id_cart)';
    Db::getInstance()->execute($query);

    $query = 'DELETE FROM ' . _DB_PREFIX_ . 'cart WHERE id_cart IN (SELECT c.id_cart FROM ' . _DB_PREFIX_ . 'cart c LEFT JOIN ' . _DB_PREFIX_ . 'orders AS o ON c.id_cart = o.id_cart WHERE o.id_order IS NULL
                      AND c.date_upd  <  DATE_SUB(NOW(), INTERVAL 30 DAY) )';
    Db::getInstance()->execute($query);


    $query = 'OPTIMIZE TABLE `' . _DB_PREFIX_ . 'quantity_discount_rule_order`';

    Db::getInstance()->execute($query);

    $query = 'OPTIMIZE TABLE `' . _DB_PREFIX_ . 'cart_rule_combination`';

    Db::getInstance()->execute($query);

    $query = 'OPTIMIZE TABLE `' . _DB_PREFIX_ . 'cart_rule`';

    Db::getInstance()->execute($query);

    $query = 'DELETE crl FROM `' . _DB_PREFIX_ . 'cart_rule_lang` crl
    LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule cr ON crl . id_cart_rule = cr . id_cart_rule
    WHERE cr . id_cart_rule IS NULL';

    Db::getInstance()->execute($query);
} else {
    echo ' < script>';
    echo 'setTimeout(function () {
        ';
    echo '  window . location . reload();';
    echo '}, 1);';
    echo ' </script > ';
}

echo date('Y - m - d H:i:s') . ' - Finish' . ' < br>';

die;
