<?php

include dirname(__FILE__).'/../config/config.inc.php';

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/logtarifasfalse.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

$sql = "SELECT *  FROM `aalv_integracion_cambios` WHERE `tabla` = 'v_sinc_tarifa_cabecera' AND `data` LIKE '%estado\":false%' ORDER BY `id` limit ".((int) Tools::getValue('id')).',1';

$rows = Db::getInstance()->ExecuteS($sql);

foreach ($rows as $row) {

    $row['data'] = str_replace('u00c1', 'Á', $row['data']);
    $row['data'] = str_replace('u00d1', 'Ñ', $row['data']);
    $row['data'] = str_replace('u00d3', 'Ó', $row['data']);

    $row['fila'] = (int) $row['fila'];
    $row['tipo'] = (int) $row['tipo'];
    $row['data'] = json_decode($row['data'], true);

    $data = $row['data'];

    $idtarifa_cabecera = $data['idtarifa_cabecera'];
    $idarticulo = $data['idarticulo'];

    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo);
    $idprodattrps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo='.$idarticulo);

    if ($idprodps == '') {
        if ($idprodattrps != '') {
            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattrps);
        }
    } else {
        $idprodattrps = '0';
    }

    if ($data['idregpais'] == 2) {
        $codpais = 15; // portugal
    } else {
        $codpais = 0;
    }

    $finicio = str_replace('T', ' ', $data['finicio']);
    $ffin = str_replace('T', ' ', $data['ffin']);

    if (''.$finicio == '') {
        $finicio = '0000-00-00 00:00:00';
    }

    if (''.$ffin == '') {
        $ffin = '0000-00-00 00:00:00';
    }

    $sql = 'DELETE FROM aalv_specific_price where id_country='.$codpais." and `from`='".$finicio."' and `to`='".$ffin."' and id_product=".$idprodps.' and id_product_attribute='.$idprodattrps.';';

    addlog($sql);

}
echo 'acaba';
