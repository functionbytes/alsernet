<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// $id_product = 48287;

// $idlote = 100002791;

// $idllote = 100008907;

// $idlllote = 100014645;

// Db::getInstance()->ExecuteS("update aalv_product set active = 0 where id_product = ".$id_product);
// Db::getInstance()->ExecuteS("update aalv_product_shop set active = 0 where id_product = ".$id_product);

// $id_wk_bundle_product = Db::getInstance()->ExecuteS("SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product = ".$id_product);

// $bundle_section = Db::getInstance()->ExecuteS("select bundle_section from aalv_llote_import where idllote = ".$idllote);

// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_product WHERE id_ps_product = ".$id_product);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_specific_price WHERE id_product = ".$id_product);

// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_product_shop WHERE id_wk_bundle_product = ".$id_wk_bundle_product[0]['id_wk_bundle_product']);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_product  = ".$id_wk_bundle_product[0]['id_wk_bundle_product']);

// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_section WHERE id_wk_bundle_section = ".$bundle_section[0]['bundle_section']);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_section_lang WHERE id_wk_bundle_section = ".$bundle_section[0]['bundle_section']);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_section_shop WHERE id_wk_bundle_section  = ".$bundle_section[0]['bundle_section']);

// foreach ($idlllote as $key => $value) {
//     $id_bundle_sub_product = Db::getInstance()->ExecuteS("SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote = ".$idlllote);
//     Db::getInstance()->ExecuteS("DELETE FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute = ".$id_bundle_sub_product[0]['id_bundle_sub_product']);
//     Db::getInstance()->ExecuteS("DELETE FROM aalv_lllote_import WHERE idlllote = ".$idlllote);
// }

// Db::getInstance()->ExecuteS("DELETE FROM aalv_lote_import WHERE idlote = ".$idlote);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_llote_import WHERE idllote = ".$idllote);
// Db::getInstance()->ExecuteS("DELETE FROM aalv_tarifalote_import where idllote  IN (".$idllote.")");

// var_dump($id_wk_bundle_product[0]['id_wk_bundle_product']);
// echo "<br>";
// var_dump($bundle_section[0]['bundle_section']);
// echo "<br>";
// var_dump($id_bundle_sub_product[0]['id_bundle_sub_product']);
