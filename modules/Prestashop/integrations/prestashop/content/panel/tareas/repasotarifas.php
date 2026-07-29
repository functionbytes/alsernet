<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// header("Content-Type: text/plain");

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/logtarifas.txt', 'a');
    fwrite($stdout, ','.$message);
    fclose($stdout);
}

$sql = 'SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import where id_tarifa_cabecera not in (select id_tarifa_cabecera from aalv_specific_price_import)';

$rows = Db::getInstance()->ExecuteS($sql);

foreach ($rows as $row) {

    $idtarifacabecera = $row['id_tarifa_cabecera'];

    $sql = "SELECT id FROM `aalv_integracion_cambios` WHERE `tabla` = 'v_sinc_tarifa_linea' and data like '%idtarifa_cabecera\":".$idtarifacabecera."%' ORDER BY `id` desc";

    $idlinea = Db::getInstance()->getValue($sql);
    addlog($idlinea);
}
echo 'acaba';
