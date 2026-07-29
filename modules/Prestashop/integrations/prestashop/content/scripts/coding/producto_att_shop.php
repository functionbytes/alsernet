<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
die();
//Se buscan todos los attribute que no existen en shop
$sql = Db::getInstance()->ExecuteS("SELECT
                    att.id_product,
                    att.id_product_attribute,
                    shop.id_product_attribute AS shop_attribute
                FROM
                    aalv_product_attribute_shop att
                    LEFT JOIN aalv_product_attribute shop ON shop.id_product_attribute  = att.id_product_attribute
                WHERE
                    shop.id_product_attribute IS NULL
                    GROUP BY att.id_product
                    ORDER BY att.id_product DESC");

foreach ($sql as $value) {


    Db::getInstance()->execute("DELETE FROM aalv_repositorio_stock WHERE id_product_attribute = ".$value['id_product_attribute']. " and id_product = ".$value['id_product']);
    Db::getInstance()->execute("DELETE FROM aalv_product_attribute_shop WHERE id_product_attribute = ".$value['id_product_attribute']. " and id_product = ".$value['id_product']);
    dump($value);
    // delete from aalv_repositorio_stock where id_product_attribute = 194855
    // delete from aalv_product_attribute_shop where id_product_attribute = 194855 and id_product = 64288
}


// Ahora al revez
$sql = Db::getInstance()->ExecuteS("SELECT
                    att.id_product,
                    att.id_product_attribute,
                    shop.id_product_attribute AS shop_attribute
                FROM
                    aalv_product_attribute att
                    LEFT JOIN aalv_product_attribute_shop shop ON shop.id_product_attribute  = att.id_product_attribute
                WHERE
                    shop.id_product_attribute IS NULL
                    GROUP BY att.id_product
                    ORDER BY att.id_product DESC");

foreach ($sql as $value) {


    Db::getInstance()->execute("DELETE FROM aalv_repositorio_stock WHERE id_product_attribute = ".$value['id_product_attribute']. " and id_product = ".$value['id_product']);
    Db::getInstance()->execute("DELETE FROM aalv_product_attribute_shop WHERE id_product_attribute = ".$value['id_product_attribute']. " and id_product = ".$value['id_product']);
    Db::getInstance()->execute("DELETE FROM aalv_combinaciones_import WHERE id_product_attribute = ".$value['id_product_attribute']);
    $combination_obj = new Combination($value['id_product_attribute']);
    $combination_obj->delete();
    dump($value);die();
    // delete from aalv_repositorio_stock where id_product_attribute = 194855
    // delete from aalv_product_attribute_shop where id_product_attribute = 194855 and id_product = 64288
}