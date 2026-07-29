<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function addlog($message)
{

    $stdout = fopen(dirname(__FILE__).'/imageattrasignnew.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);

}

function CrearSQLImagenes($row)
{
    $fila = $row['id_origen'];
    $product_attribute = $row['id_product_attribute'];

    // $data = "".Db::getInstance()->getValue("SELECT data FROM aalv_integracion_cambios WHERE tabla = 'v_sinc_w_producto' AND fila =".$fila." ORDER BY id desc");

    $data = ''.Db::getInstance()->getValue("SELECT data FROM aalv_integracion_cambios WHERE tabla = 'v_sinc_w_producto' AND fila =".$fila);

    if ($data != '') {

        $midata = json_decode($data, true);

        $image = $midata['imagen_seo'];

        if ($image != '') {
            // buscar imagen en product_import
            $idimage = ''.Db::getInstance()->getValue("SELECT id_image FROM aalv_image_import WHERE filename='".$image."'");

            if ($idimage != '') {

                addlog('REPLACE INTO aalv_product_attribute_image(id_product_attribute, id_image) VALUES ('.$product_attribute.','.$idimage.');');
            }

        }

    }

}

echo 'empieza';

$rows = Db::getInstance()->executeS('SELECT `id_product_attribute`,`id_origen` FROM `aalv_combinaciones_import` where id_product_attribute not in (select `id_product_attribute` from aalv_product_attribute_image) and id_product_attribute > 34462');

foreach ($rows as $row) {
    CrearSQLImagenes($row);
}

echo '<br/>acaba';

addlog('acaba');
