<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function Procesar($row)
{

    $idmodelo = $row['fila'];
    $valores = json_decode($row['data'], true);

    $idproducto = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

    if ($idproducto != '') {

        $product = new Product($idproducto);
        if (($valores['texto_productos_no_vendibles'] != '') || ($valores['precio_consultar_ficha'] == 1) || ($valores['venta_telefono'] == 1)) {
            $product->available_for_order = false;
            $product->show_price = false;
            $product->update();
            echo 'pasa 1';
        } else {
            $product->available_for_order = true;
            $product->show_price = true;
            $product->update();
            echo 'pasa 2';
        }

    }
}

$rowsp = Db::getInstance()->ExecuteS("SELECT * FROM `aalv_integracion_cambios` where tabla='v_sinc_w_modelo' and fila in (SELECT id_modelo FROM aalv_product_import where id_product in (SELECT id_product  FROM `aalv_product` WHERE `available_for_order` = 0 AND `show_price` = 0)) ORDER BY id asc");

foreach ($rowsp as $prod) {

    Procesar($prod);
}

echo 'acaba';
