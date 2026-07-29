<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function peticionput($url, $data)
{

    $datos = http_build_query($data);

    // $datos = str_replace("%3A", ":", $datos );
    // $datos = str_replace("%40", "@", $datos );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Length: '.strlen($datos)]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
    $content = curl_exec($ch);

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);
    echo 'llega '.$datos.' httpcode:'.$httpcode;

    return $content;

}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function savelopd($email, $fecha, $no_info_comercial, $no_datos_a_terceros)
{

    $data = [];
    $data['cliente_email'] = $email;
    $data['cliente_faceptacion_lopd'] = $fecha;
    $data['cliente_no_info_comercial'] = $no_info_comercial;
    $data['cliente_no_datos_a_terceros'] = $no_datos_a_terceros;

    return peticionput('http://127.0.0.1:58002/api-gestion/cliente', $data);

}

echo savelopd('egarcia@addis.es', '2022-03-01T09:43:00', 0, 0);
