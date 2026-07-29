<?php

include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');

function addlog($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/repararfechaspreciosupd.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function addlog2($message)
{
    $d = new DateTime;
    $stdout = fopen(dirname(__FILE__).'/repararfechaspreciosdel.txt', 'a');
    fwrite($stdout, $message);
    fwrite($stdout, "\n");
    fclose($stdout);
}

$d = new DateTime;
addlog('Comienzo: '.$d->format("Y-m-d\TH:i:sP"));
addlog2('Comienzo: '.$d->format("Y-m-d\TH:i:sP"));

$sql = "SELECT fila, data FROM `aalv_integracion_cambios` WHERE `tabla` = 'v_sinc_tarifa_cabecera' AND `tipo` = '2' ORDER BY fila, id";

$rows = Db::getInstance()->ExecuteS($sql);

foreach ($rows as $row) {

    $json = json_decode($row['data'], true);

    $finicio = str_replace('T', ' ', $json['finicio']);
    $ffin = str_replace('T', ' ', $json['ffin']);
    if (''.$finicio == '') {
        $finicio = '0000-00-00 00:00:00';
    }

    if (''.$ffin == '') {
        $ffin = '0000-00-00 00:00:00';
    }

    if ($finicio >= '2022-09-01 00:00:00') {

        $tarcab = $row['fila'];

        // buscar $tarcab en specificimport

        $precioespe = ''.Db::getInstance()->getValue('SELECT `id_specific_price` FROM `aalv_specific_price_import` where `id_tarifa_cabecera`='.$tarcab);

        if ($precioespe != '') {
            $difierenfechas = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$precioespe." and `from`='".$finicio."' and `to`='".$ffin."'");

            if ($json['estado']) {
                if ($difierenfechas == '') {
                    addlog("UPDATE aalv_specific_price set `from`='".$finicio."',`to`='".$ffin."' where id_specific_price=".$precioespe.';');
                }
            } else {
                addlog2('DELETE FROM  aalv_specific_price where id_specific_price='.$precioespe.';');
            }
        }

    }
}

$d = new DateTime;
addlog('Finaliza: '.$d->format("Y-m-d\TH:i:sP"));
addlog2('Finaliza: '.$d->format("Y-m-d\TH:i:sP"));

echo 'acaba..';
