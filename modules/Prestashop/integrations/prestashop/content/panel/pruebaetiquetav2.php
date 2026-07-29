<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');
include '/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php';

$sql = Db::getInstance()->executeS("select id_articulo, aci.externo_disponibilidad , aci.etiqueta , aci.estado_gestion
from aalv_combinacionunica_import aci where aci.etiqueta not like '%SEGUNDA MANO%' and id_articulo <= 300025214 order by id_articulo desc");
$dbcon = connectBD();
$nn = 0;
$sum = 0;
foreach ($sql as $value) {
    // code...

    $sql_antigua = 'select externo_disponibilidad, etiqueta, estado_gestion from producto where idarticulo = '.$value['id_articulo'];
    $data = mysqli_query($dbcon, $sql_antigua);
    $re = mysqli_fetch_array($data, MYSQLI_ASSOC);

    $update = 'UPDATE aalv_combinacionunica_import SET ';

    $set = '';
    if ((int) $value['externo_disponibilidad'] != (int) $re['externo_disponibilidad']) {
        $set .= 'externo_disponibilidad='.$re['externo_disponibilidad'].',';
    }
    if ((int) $value['estado_gestion'] != (int) $re['estado_gestion']) {
        $set .= 'estado_gestion='.$re['estado_gestion'].',';
    }

    if ($value['etiqueta'] != '' || $re['etiqueta'] != '') {
        $exp_ps = explode(', ', $value['etiqueta']);
        $exp_antigua = explode(', ', $re['etiqueta']);

        if (array_count_values($exp_ps) != array_count_values($exp_antigua)) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
        } elseif (! empty(array_diff($exp_ps, $exp_antigua)) || ! empty(array_diff($exp_antigua, $exp_ps))) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
        } elseif (count($exp_ps) != count($exp_antigua)) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
            dump($value);
            dump($exp_ps);
            dump($exp_antigua);
            echo '---------------------';
            echo '---------------------';
            exit();
        }
    }

    if ($set != '') {
        if (substr($set, -1) === ',') {
            $set = substr($set, 0, -1).' '; // Elimina el último carácter (la coma)
        }
        $update .= $set.'WHERE id_articulo = '.$value['id_articulo'];
        dump($value);
        dump($re);
        dump($set);
        dump($update);
        exit();
        Db::getInstance()->Execute($update);
        Product::alsernetNewVisibilidad($value['id_articulo']);
        exit();
    }

    if ($nn < 150) {
        echo '.';
        $nn++;
    }
    if ($nn == 150) {
        $sum = $sum + 150;
        echo ' '.$sum.'/'.count($sql);
        echo "\n";
        $nn = 0;
    }
}
