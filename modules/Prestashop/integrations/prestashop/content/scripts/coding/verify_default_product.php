<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

setlocale(LC_CTYPE, 'es.UTF16');

$where = '';
$actualizar = false;
$debug = false;
$csv = false;
$test = false;
if (is_array($argv)) {
    foreach ($argv as $parametro) {
        if (is_numeric($parametro) || strpos($parametro, ',')) {
            $where = ' where id_product IN ('.$parametro.')';
        }
        if ($parametro == 'actualizar') {
            $actualizar = true;
        }
        if ($parametro == 'debug') {
            $debug = true;
        }
        if ($parametro == 'csv') {
            $csv = true;
        }
        if ($parametro == 'test') {
            $test = true;
        }
    }
}

if ($_GET['id_product']) {
    $where = ' where id_product IN ('.$_GET['id_product'].')';
}

$query = 'SELECT id_product FROM aalv_product_import'.$where.' ORDER BY id_product DESC';

$productos = Db::getInstance()->ExecuteS($query);

foreach ($productos as $ps) {
    $stock = Db::getInstance()->ExecuteS('SELECT
                                            pa.id_product_attribute,
                                            sa.quantity,
                                            pa.reference,
                                            ap.active
                                          FROM
                                            aalv_stock_available sa
                                            LEFT JOIN aalv_product_attribute pa ON sa.id_product = pa.id_product AND sa.id_product_attribute = pa.id_product_attribute
                                            LEFT JOIN aalv_product ap on ap.id_product = pa.id_product
                                          WHERE
                                            ap.id_product = '.$ps['id_product']);
    if (count($stock) == 0) {
        continue;
    }
    foreach ($stock as $ps_stock) {
        if ($ps_stock['id_product_attribute']) {
            $id_current_default_attribute = Product::getDefaultAttribute($ps['id_product']);
            if ($ps_stock['id_product_attribute'] == $id_current_default_attribute) {
                // El modelo activo con menor precio y stock
                $stock_modelo_por_defecto = Db::getInstance()->ExecuteS('select id_product_attribute from (SELECT
                                                            pa.id_product_attribute, pr.price-pr.reduction as total_price, sa.quantity, pr.`from`
                                                        FROM
                                                            aalv_stock_available sa
                                                        LEFT JOIN aalv_product_attribute pa ON
                                                            sa.id_product = pa.id_product
                                                            AND sa.id_product_attribute = pa.id_product_attribute
                                                        LEFT JOIN aalv_product ap ON
                                                            ap.id_product = pa.id_product
                                                        LEFT JOIN aalv_specific_price pr ON
                                                            pa.id_product_attribute = pr.id_product_attribute AND ap.id_product = pr.id_product
                                                        WHERE
                                                            ap.id_product = '.$ps['id_product'].'
                                                            AND pa.id_product_attribute NOT IN (
                                                            SELECT
                                                                id_product_attribute
                                                            FROM
                                                                aalv_tot_switch_attribute_disabled
                                                            WHERE
                                                                id_shop = 1
                                                                AND id_product_attribute IN (SELECT id_product_attribute
                                                                                            FROM aalv_product_attribute
                                                                                            WHERE id_product='.$ps['id_product']."))
                                                                AND (pr.`from` = '0000-00-00 00:00:00'
                                                                    OR NOW() >= pr.`from`)
                                                                AND (pr.`to` = '0000-00-00 00:00:00'
                                                                    OR NOW() <= pr.`to`)) specific_prices
                                                        ORDER BY
                                                            total_price ASC, quantity DESC, `from` DESC
                                                        LIMIT 1");
                if ($stock_modelo_por_defecto) {
                    if ($stock_modelo_por_defecto[0]['id_product_attribute'] != $id_current_default_attribute) {
                        var_dump($ps['id_product']);
                        var_dump('Modificamos el modelo Por defecto al '.$stock_modelo_por_defecto[0]['id_product_attribute']);
                        $product = new Product($ps['id_product']);
                        $product->deleteDefaultAttributes();
                        $product->setDefaultAttribute($stock_modelo_por_defecto[0]['id_product_attribute']);
                        $product->update();
                    } else {
                        var_dump($ps['id_product']);
                        var_dump('El modelo Por defecto es correcto');
                    }
                } else {
                    var_dump($ps['id_product']);
                    var_dump('No se ha obtenido valor Por defecto.');
                }
            }
        }
    }
    echo "\n\n";
}
