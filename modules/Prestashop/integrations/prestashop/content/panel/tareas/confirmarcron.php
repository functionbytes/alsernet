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

$urlconfirm = 'http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/'.$transaccion.'/';
$contentconfirm = peticionget($urlconfirm);

$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_2/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_3/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_4/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_5/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_6/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_7/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_8/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_9/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_10/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_11/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_12/');
dump($contentitem);
$contentitem = peticionget('http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/3.29.1169648_13/');
dump($contentitem);
