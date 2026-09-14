<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$URL = 'http://127.0.0.1:59000/integracion/CambiosPendientes/?destino=1';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$content = curl_exec($ch);
curl_close($ch);

$jsonData = trim(stripslashes($content), '"');
$obj = json_decode($jsonData, true);

$results = $obj['results'];

foreach ($obj['results'] as $valores) {

    echo $valores['transaccion'].'<br/>';
    echo $valores['url_transaccion'].'<br/>';
    echo $valores['url_confirmacion'].'<br/>';
    echo $valores['fecha_creacion'].'<br/>';
    echo $valores['primer_idcambiotabla'].'<br/>';
    echo $valores['total_cambios'].'<br/>';

}

// Db::getInstance()->Execute('');

dump($obj);
