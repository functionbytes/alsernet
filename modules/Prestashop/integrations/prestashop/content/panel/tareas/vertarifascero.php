<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/productossintarifa.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function verpreciocero($idproduct, $idproductattribute)
{
    $sql = 'SELECT 1  FROM `aalv_specific_price` WHERE `id_country` = 0 and id_product='.$idproduct.' and id_product_attribute='.$idproductattribute." AND (`from` = '0000-00-00 00:00:00' OR now() >= `from`) AND (`to` = '0000-00-00 00:00:00' OR now() <= `to`)";
    $existe = ''.Db::getInstance()->getValue($sql);
    if ($existe == '') {

        $sql = 'SELECT 1  FROM `aalv_specific_price` WHERE `id_country` = 0 and id_product='.$idproduct." and id_product_attribute=0 AND (`from` = '0000-00-00 00:00:00' OR now() >= `from`) AND (`to` = '0000-00-00 00:00:00' OR now() <= `to`)";
        $existe = ''.Db::getInstance()->getValue($sql);

        if ($existe == '') {
            addlog($idproduct.';'.$idproductattribute);

        }
    }
}

$sql = "SELECT  b.id_product, a.id_product_attribute FROM aalv_combinaciones_import a inner join aalv_product_attribute b on a.id_product_attribute=b.id_product_attribute inner join aalv_product c on b.id_product=c.id_product and c.active=1 and c.visibility='both' where a.id_product_attribute not in (select id_product_attribute from aalv_tot_switch_attribute_disabled) 
union select a.id_product,0 from aalv_combinacionunica_import a inner join aalv_product b on a.id_product=b.id_product and b.active=1 and b.visibility='both' where a.id_product not in (select id_ps_product from aalv_wk_bundle_product) ORDER BY 1,2";

$productos = Db::getInstance()->ExecuteS($sql);

foreach ($productos as $producto) {
    verpreciocero($producto['id_product'], $producto['id_product_attribute']);
}

echo 'acaba';
