<?php

ini_set('max_execution_time', 1760000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
exit();
$ids = [64247, 64913, 65103, 65105, 65109, 65111, 65305, 65306, 65307, 65309, 65310, 65311, 65313, 65314, 65317, 65318, 65390, 65388, 65392, 65394, 65399, 65865, 65876, 65878, 65885, 65883, 67004, 67006, 67008, 67572, 67573, 67574, 67576, 67579];
//
// Validamos si la integracion esta funcionando
$procesando = Db::getInstance()->getRow('SELECT activo FROM aalv_bandera_integracion WHERE id = 2');

if ($procesando['activo'] == 0) {
    echo "Inicia el proceso\n";
    // Si no esta funcionando la desactivamos para empezar a eliminar
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion SET activo = 1, fecha = NOW() WHERE id = 2');
    foreach ($ids as $value) {

        $product = new Product($value);

        if (Validate::isLoadedObject($product)) {

            // Eliminamos el producto con la class de PrestaShop
            $product->deleteAlsernet();

            // Eliminamos tablas alternativas
            Db::getInstance()->Execute('DELETE FROM aalv_attachment_import          WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_category_product_import    WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_image_import               WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_perfiles_prod_import       WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_product_import             WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_tarifa_cabecera_import     WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_repositorio_stock          WHERE id_product = '.$value);
            Db::getInstance()->Execute('DELETE FROM aalv_combinacionunica_import    WHERE id_product = '.$value);

            echo 'Eliminado producto => '.$value."\n";
        }
    }
    // dump('fff');die();
    // Validamos articulos que no existan.
    // $dato_combinacion = Db::getInstance()->ExecuteS("SELECT id_product_attribute FROM aalv_combinaciones_import aci GROUP BY id_product_attribute");
    // foreach ($dato_combinacion as $value) {
    //     $id_product_attribute = Db::getInstance()->ExecuteS("SELECT * FROM aalv_product_attribute apa WHERE id_product_attribute = ".$value['id_product_attribute']);
    //     if(count($id_product_attribute) == 0){
    //         Db::getInstance()->Execute("DELETE FROM aalv_combinaciones_import WHERE id_product_attribute = ".$value['id_product_attribute']);
    //         echo "Eliminado attribute => ".$value['id_product_attribute']."\n";
    //     }
    // }

    // Una vez finalizado, la volvemos a poner en marcha
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion SET activo = 0, fecha = NOW() WHERE id = 2');
    echo "Proceso terminado\n";
} else {
    echo "\nSe esta procesando.\n";
}
echo 'listo';
