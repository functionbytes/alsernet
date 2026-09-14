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

$URL = 'http://127.0.0.1:59000/integracion/CambiosPendientes/?destino=2&limit=500';

$content = peticionget($URL);
$jsonData = trim(stripslashes($content), '"');
$obj = json_decode($jsonData, true);
$results = $obj['results'];
dump($content);
