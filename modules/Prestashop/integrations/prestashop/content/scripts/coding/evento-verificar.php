<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}

include dirname(__FILE__).'/../config/config.inc.php';
// include(dirname(__FILE__) . '/../init.php');

$archivo = 'evento-verificar.csv'; // Nombre del archivo CSV
$archivo_procesado = 'evento-verificar-procesado.csv'; // Nombre del archivo CSV de salida

$etiqueta = 'REVER242';

// Intentar abrir el archivo
$handle = fopen($archivo, 'r');

// Verificar si se abrió correctamente
if ($handle === false) {
    exit('No se pudo abrir el archivo.');
}

// Abrir el archivo de salida en modo escritura
$handle_procesado = fopen($archivo_procesado, 'a');

// Verificar si se abrió correctamente
if ($handle_procesado === false) {
    exit('No se pudo abrir el archivo de salida.');
}

// Iterar sobre cada línea del archivo
while (($line = fgets($handle)) !== false) {
    // sleep(10);
    // Separar los campos por coma

    $datos = explode(';', $line);
    // dump($datos);die();
    // CODIGO_1;ESTADO;DESCRIPCION;FCREACION;PVP;PVP RECOM PROV;IDPROVEEDOR;NOMBRE;UNIDADES;CATALOGOS_IMPRESOS;ETIQUETA;FECHA_TARIFA_PRO;IDPRODUCTO_WEB;IDMODELO_WEB

    $buscar = Db::getInstance()->executeS('SELECT * FROM aalv_combinacionunica_import aci WHERE id_origen = '.$datos[12]);
    if (count($buscar) == 0) {
        $buscar = Db::getInstance()->executeS('SELECT * FROM aalv_combinaciones_import aci where id_origen = '.$datos[12]);
        if (count($buscar) == 0) {
            dump($datos);
            exit();
        }
    }

    if (! in_array($etiqueta, explode(', ', $buscar[0]['etiqueta']))) {

        echo "El valor '$etiqueta' no existe en el array";
        dump($buscar);
        echo '<br>';
        dump($datos);
        exit();
    }

    // Escribir la línea procesada en el archivo de salida
    fputcsv($handle_procesado, $datos, ';');

    // Eliminar la línea del archivo original
    // Leer todas las líneas del archivo original excepto la línea actual
    $lines = file($archivo);
    // dump($lines[0]);die();
    unset($lines[0]); // Eliminar la primera línea (la que ya hemos procesado)

    // Escribir el contenido actualizado de vuelta al archivo original
    file_put_contents($archivo, $lines);
}

// Cerrar el archivo
fclose($handle);
fclose($handle_procesado);
