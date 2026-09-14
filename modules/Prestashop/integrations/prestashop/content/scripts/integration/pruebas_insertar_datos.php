<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

require_once dirname(__DIR__).'/integration/auxiliares.php';
$auxiliares = new auxiliares;

$tabla = 'v_sinc_w_valores_prod';

// $datos = '{"id":100009235,"nombre":"62º","id_caracteristica":100001812}';
// $datos = '{"id":100017541,"nombre":"Acero Ping Z-Z115 Wedge","id_caracteristica":4}';
$datos = '{"id":100017556,"nombre":"62º-06 T","id_caracteristica":5}';

$tipo = 1;

$fila = 100017556;
exit();

$nombreArchivo = dirname(__DIR__).'/integration/'.$tabla.'.php';
$nombreClase = $tabla.'Class';
$procesar = 'Procesar_'.$tabla;
$datos = json_decode($datos, true);

if (file_exists($nombreArchivo)) {
    include_once $nombreArchivo;
    if (class_exists($nombreClase)) {
        $objeto = new $nombreClase;
        if (method_exists($objeto, $procesar)) {
            call_user_func([$objeto, $procesar], $datos, $fila, $tipo);
        } else {
            $auxiliares->sendmail("El método $procesar no existe en la clase $nombreClase.");
        }
    } else {
        $auxiliares->sendmail("La clase $nombreClase no existe en el archivo $nombreArchivo.");
    }
} else {
    $auxiliares->sendmail("El archivo $nombreArchivo no existe.");
}
