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
$sql = Db::getInstance()->ExecuteS("select apa.reference, ap.id_product from aalv_product ap
left join aalv_product_attribute apa on apa.id_product_attribute = ap.cache_default_attribute
where ap.reference = '' and apa.reference is not null");

foreach ($variable as $key => $value) {
    # code...
}
