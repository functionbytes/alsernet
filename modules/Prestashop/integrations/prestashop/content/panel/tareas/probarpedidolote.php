<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

$order = new Order(189);

$product_list = $order->getOrderDetailList();
foreach ($product_list as $product) {

    dump($product);

    $idproduct = $product['product_id'];
    // ver si es lote

    $rowslotes = Db::getInstance()->ExecuteS('SELECT * FROM aalv_wk_bundle_order_detail WHERE id_order=189 and id_ps_product='.$idproduct);
    if ($rowslotes) {
        foreach ($rowslotes as $rowlote) {
            $bundlesection = $rowlote['id_wk_bundle_section'];
            $idprodbundle = $rowlote['id_product'];
            $idprodattribute = $rowlote['id_product_attribute'];

            if ($idprodattribute == 0) {
                $ref = Db::getInstance()->getValue('SELECT reference FROM aalv_product WHERE id_product='.$idprodbundle);
            } else {
                $ref = Db::getInstance()->getValue('SELECT reference FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattribute);
            }
            $uni = $rowlote['product_qty'];

            $seclote = Db::getInstance()->getValue('SELECT idllote FROM aalv_llote_import WHERE bundle_section='.$bundlesection);
            $pre = Db::getInstance()->getValue('SELECT precio_con_impuestos FROM aalv_tarifalote_import WHERE idllote='.$seclote.' and idttarifa=1 and estado=1');

            $idlote = Db::getInstance()->getValue('SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section='.$bundlesection.')');

            $xml = $xml.'<linea><referencia>'.$ref.'</referencia><unidades>'.$uni.'</unidades><precio>'.$pre.'</precio><dto>0</dto><nota_general></nota_general><idlote>'.$idlote.'</idlote><seclote>'.$seclote.'</seclote><idcatalogo>3</idcatalogo></linea>';
        }
    } else {

        $ref = $product['product_reference'];
        $uni = $product['product_quantity'];
        $pre = $product['unit_price_tax_incl'];

        $xml = $xml.'<linea><referencia>'.$ref.'</referencia><unidades>'.$uni.'</unidades><precio>'.$pre.'</precio><dto>0</dto><nota_general></nota_general><idlote></idlote><seclote></seclote><idcatalogo>3</idcatalogo></linea>';

    }

}

echo 'xml'.$xml;
