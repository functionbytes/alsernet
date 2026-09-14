<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

$transaccion = '6.12.948944';
echo 'Transaccion '.$transaccion.'<br/>';

$urlconfirm = 'http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/'.$transaccion.'/';
$contentconfirm = peticionget($urlconfirm);
$jsonconfirmData = trim(stripslashes($contentconfirm), '"');
$objconfirm = json_decode($jsonconfirmData, true);
// dump($objconfirm);
if ($objconfirm['estado'] == 'confirmado') {
    Db::getInstance()->Execute("Update aalv_integracion_cambios set fecha_confirmacion=now() where transaccion='".$transaccion."'");
    Db::getInstance()->Execute("Update aalv_integracion_transacciones set fecha_confirmacion=now() where id='".$transaccion."'");
    echo 'Transaccion '.$transaccion.' CONFIRMADA<br/>';
} else {
    echo 'Transaccion '.$transaccion.' NO CONFIRMADA<br/>';

}
