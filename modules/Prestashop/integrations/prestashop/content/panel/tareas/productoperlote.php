<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function productopertenecelote($idproduct)
{

    $lotes = Db::getInstance()->ExecuteS('SELECT id_ps_product FROM aalv_wk_bundle_product a inner join aalv_wk_bundle_section_map b on a.id_wk_bundle_product=b.id_wk_bundle_product where b.id_wk_bundle_section in (SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product where id_product='.$idproduct.' union SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product_attribute where id_product='.$idproduct.')');

    $products = [];

    foreach ($lotes as $lote) {
        $products[] = $lote['id_ps_product'];
    }

    return $products;

}

dump(productopertenecelote(54298));
