<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../config/config.inc.php');
include (dirname(__FILE__).'/../init.php');

/********************** LIMPIA PRODUCTOS */

//  $busca = Db::getInstance()->ExecuteS("  SELECT * FROM aalv_product ap WHERE id_product in (64729,
//  64617,
//  64247,
//  64249)");

// /* in (64257,64253,64252,64247,64246,64249,64250,64242));*/
// $nn = '';
// $nn2 = '';
// $nn3 = '';
// foreach ($busca as $key => $value) {
//     for ($i=0; $i < 2; $i++) {
//         Db::getInstance()->ExecuteS("UPDATE aalv_product SET active = 0 WHERE id_product = ".$value['id_product']);
//         Db::getInstance()->ExecuteS("UPDATE aalv_product_shop SET active = 0 WHERE id_product = ".$value['id_product']);

//         Db::getInstance()->ExecuteS("UPDATE aalv_product SET cache_default_attribute = 0 WHERE id_product = ".$value['id_product']);
//         Db::getInstance()->ExecuteS("UPDATE aalv_product_shop SET cache_default_attribute = 0 WHERE id_product = ".$value['id_product']);

//         $bb = Db::getInstance()->ExecuteS("SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product = ".$value['id_product']);

//         foreach ($bb as $keEy => $valuue) {
//             $nn2 .= $valuue['id_product_attribute'].',';
//             Db::getInstance()->ExecuteS("DELETE FROM aalv_combinaciones_import WHERE id_product_attribute = ".$valuue['id_product_attribute']);
//         }


//         Db::getInstance()->ExecuteS("DELETE FROM aalv_tarifa_cabecera_import WHERE id_product = ".$value['id_product']);

//         $busca2 = Db::getInstance()->ExecuteS("SELECT id_specific_price FROM aalv_specific_price WHERE id_product = ".$value['id_product']);

//         foreach ($busca2 as $ky => $val) {
//             Db::getInstance()->ExecuteS("DELETE FROM aalv_specific_price_import WHERE id_specific_price = ".$val['id_specific_price']);
//             $nn3 .= $val['id_specific_price'].',';
//         }

//         Db::getInstance()->ExecuteS("DELETE FROM aalv_specific_price WHERE id_product = ".$value['id_product']);

//         Db::getInstance()->ExecuteS("DELETE FROM aalv_repositorio_stock WHERE id_product = ".$value['id_product']);

//         Db::getInstance()->ExecuteS("DELETE FROM aalv_stock_available WHERE id_product = ".$value['id_product']);

//         Db::getInstance()->ExecuteS("DELETE FROM aalv_product_attribute_shop WHERE id_product = ".$value['id_product']);

//         Db::getInstance()->ExecuteS("DELETE FROM aalv_product_attribute WHERE id_product = ".$value['id_product']);
//         # code...
//     }
//     $nn .= $value['id_product'].',';
// }

// echo 'id_product: => '.$nn."<br><br>";
// echo 'id_product_attribute: => '.$nn2."<br><br>";
// echo 'id_specific_price => '.$nn3;
