<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

//$sql_product_query = 'SELECT DISTINCT id_product FROM '._DB_PREFIX_.'product WHERE active = 1 ORDER BY id_product DESC';

$sql_product_query = 'SELECT DISTINCT id_product FROM '._DB_PREFIX_.'alsernet_cache_producto ORDER BY id_product DESC';
$products = Db::getInstance()->executeS($sql_product_query);

foreach ($products as $product) {

    $id_product = $product['id_product'];

    peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $id_product);

    $sql_delete_product_query = 'DELETE FROM '._DB_PREFIX_.'alsernet_cache_producto WHERE id_product='.$id_product;

    echo "PRODUCTO MODIFICADO ----- $id_product \n" ;

    /*$product = new Product($id_product);
    try {
        $product->update();
        echo "PRODUCTO MODIFICADO ----- $id_product \n" ;
    }
    catch (Exception $e) {
        echo "ERROR ----- $e \n" ;
    }*/

}
echo "PROCESO TERMINADO -------\n" ;

function peticionget($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);
    return $content;
}
