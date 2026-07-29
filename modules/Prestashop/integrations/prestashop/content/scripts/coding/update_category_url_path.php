<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

$sql_category_query = 'SELECT DISTINCT id_category FROM '._DB_PREFIX_.'category_lang acl WHERE id_shop = 1 ORDER BY id_category DESC';
$categories = Db::getInstance()->executeS($sql_category_query);

$sql_update_link_rewrite = "UPDATE "._DB_PREFIX_."category_lang SET link_rewrite = REPLACE(link_rewrite, '-', '_') WHERE link_rewrite LIKE '%\-%';";
Db::getInstance()->execute($sql_update_link_rewrite);

foreach ($categories as $categ) {

    $id_category = $categ['id_category'];
    $category = new Category($id_category);
    try {
        $category->update();
        echo "CATEGORIA MODIFICADA ----- $id_category \n" ;
    }
    catch (Exception $e) {
        echo "ERROR ----- $e \n" ;
    }


}
echo "PROCESO TERMINADO -------\n" ;
