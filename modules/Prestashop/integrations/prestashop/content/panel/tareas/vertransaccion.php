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

$URL = 'http://127.0.0.1:59000/integracion/TransaccionPendiente/?destino=2&transaccion='.Tools::getValue('id');
$content = peticionget($URL);

$jsonDataitem = $content; // trim(stripslashes($content),'"');

echo $jsonDataitem;

$objitem = json_decode($jsonDataitem, true);

foreach ($objitem['results'] as $valoresitem) {

    echo 'Procesando...'.$transaccion.' '.$valoresitem['tabla'].' '.$valoresitem['fila'].' '.$valoresitem['tipo'].'<br/>';

}

echo 'acaba';
