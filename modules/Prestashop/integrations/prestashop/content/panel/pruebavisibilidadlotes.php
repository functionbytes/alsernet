<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$datos_productos = Db::getInstance()->ExecuteS('SELECT DISTINCT id_ps_product id_product  from aalv_alsernet_lotes_copia WHERE active = 0');

foreach ($datos_productos as $key) {
    $id_product = $key['id_product'];

    $producto_combinacion = Db::getInstance()->ExecuteS('   SELECT
                                                            aci.id_articulo,
                                                            aci.id_product_attribute,
                                                            apa.id_product
                                                        FROM
                                                            aalv_combinaciones_import aci
                                                            LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                        WHERE
                                                            apa.id_product = '.$id_product);

    foreach ($producto_combinacion as $value) {
        StockAvailable::setQuantity($id_product, $value['id_product_attribute'], 0, 1, false);
        Db::getInstance()->execute('UPDATE aalv_repositorio_stock SET quantity = 0, quantity_pocomaco = 0 WHERE id_product = '.$id_product.' AND id_product_attribute = '.$value['id_product_attribute']);
        echo "PRODUCTO COMBINACIONES --> $id_product"."\n";
    }
    if (! count($producto_combinacion)) {
        StockAvailable::setQuantity($id_product, 0, 0, 1, false);
        Db::getInstance()->execute('UPDATE aalv_repositorio_stock SET quantity = 0, quantity_pocomaco = 0 WHERE id_product = '.$id_product);
        echo "PRODUCTO SIMPLE --> $id_product"."\n";
    }

    $product = new Product($id_product);
    $product->visibility = 'none';
    $product->update();
    peticionget('https://'.$_SERVER['SERVER_NAME'].'/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product='.$id_product);

}
function peticionget($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, 'alsernet:May.8006763');
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}
