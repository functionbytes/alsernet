<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';
setlocale(LC_CTYPE, 'es.UTF16');

// Registro de altas confirmadas y no confirmadas

$ruta_altas = '/var/www/clients/client1/web1/home/alvarezadmin/VOLCADO_DIARIO_NO_DOUBLE_CHECK/ALTAS.txt';
$ruta_bajas = '/var/www/clients/client1/web1/home/alvarezadmin/VOLCADO_DIARIO_NO_DOUBLE_CHECK/BAJAS.txt';

$altas = '';
$bajas = '';

$query = "SELECT * FROM aalv_susc_newsletter WHERE fecha >= '".date('Y-m-d H:i:s', strtotime('-1 day'))."'";
// $query = "SELECT * FROM aalv_susc_newsletter WHERE fecha >= '2025-05-26 00:00:00'";
$correos = Db::getInstance()->ExecuteS($query);

foreach ($correos as $r) {
    if ($r['lopd'] == 0) {
        echo 'No confirmado: '.$r['email'].' => '.$r['lopd']."\n";
        $altas .= $r['email'].';'.$r['ids_alta_baja'].';'.$r['id_lang']."\n";
    } else {
        echo 'BAJA: '.$r['email'].' => '.$r['baja']."\n";
        $bajas .= $r['email'].';'.$r['ids_alta_baja'].';'.$r['id_lang']."\n";
    }
}
file_put_contents($ruta_altas, $altas);
file_put_contents($ruta_bajas, $bajas);

exit;

// ---------------------------------
// RECUPERAR BAJAS POR ERROR
// ---------------------------------

// Consulta newsletter alsernet
/*
$query = "select id_susc_newsletter, trim(email) email, cliente_no_info_comercial, cliente_no_datos_a_terceros from aalv_susc_newsletter where email in (select
correos.email
from
(
select
    altas.email,
    count(altas.email) as contador
from
    (
    select
        a.id_susc_newsletter,
        trim(a.email) email,
        a.cliente_no_info_comercial
        a.cliente_no_datos_a_terceros
    from
        aalv_susc_newsletter a
    where
        trim(a.email) LIKE '%@%'
    group by
        trim(a.email),
        a.cliente_no_info_comercial
    order by
        trim(a.email),
        a.id_susc_newsletter DESC) as altas
group by
    altas.email
order by
    contador desc) as correos
where
correos.contador <3
and correos.contador >1) group by trim(email), cliente_no_info_comercial order by trim(email), id_susc_newsletter DESC";
*/

// Consulta newsletter Addis
/*
$query = "select id_datos_rgpd, trim(email) email, check_rgpd, check_terceros from aalv_datos_rgpd where email in (select
correos.email
from
(
select
    altas.email,
    count(altas.email) as contador
from
    (
    select
        a.id_datos_rgpd,
        trim(a.email) email,
        a.check_rgpd,
        a.check_terceros
    from
        aalv_datos_rgpd a
    where
        trim(a.email) LIKE '%@%'
    group by
        trim(a.email),
        a.check_rgpd
    order by
        trim(a.email),
        a.id_datos_rgpd DESC) as altas
group by
    altas.email
order by
    contador desc) as correos
where
correos.contador <3
and correos.contador >1) group by trim(email), check_rgpd order by trim(email), id_datos_rgpd DESC";

$correos = Db::getInstance()->ExecuteS($query);

foreach ($correos as $r) {
    $correo_nuevo = trim($r['email']);
    if ($correo_anterior != $correo_nuevo && $r['check_rgpd']) {
        //echo $correo_nuevo."\n";
        if ($r['check_rgpd'] == 1) {
            $cliente_no_info_comercial = 0;
        }else{
            $cliente_no_info_comercial = 1;
        }

        if ($r['check_terceros'] == 1) {
            $no_datos_a_terceros = 0;
        }else{
            $no_datos_a_terceros = 1;
        }

        $url = "http://127.0.0.1:58002/api-gestion/cliente/?email=".$correo_nuevo;
        $data = peticionget($url);
        if ($data == "Not Found") {
            echo "No existe: ".$correo_nuevo."\n";
        }else{
            $data = json_encode(new SimpleXMLElement($data));
            $datos_cliente = json_decode($data, TRUE);
            if ($datos_cliente["no_informacion_comercial_lopd"]==1 && $cliente_no_info_comercial==0) {
                echo "nueva alta: ".$correo_nuevo."\n";
            }
            $resp1 = AlvarezERP::savelopd($correo_nuevo, str_replace(" ","T",date('Y-m-d H:i:s')), $cliente_no_info_comercial, $no_datos_a_terceros);
        }

    }
    $correo_anterior = $correo_nuevo;

}
*/
