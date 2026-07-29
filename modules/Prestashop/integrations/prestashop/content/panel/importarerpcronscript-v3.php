<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300000);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

require_once dirname(__DIR__).'/integracion/auxiliares.php';
$auxiliares = new auxiliares;

const PORC_IVA_21 = 12;
const ES_SEGUNDA_MANO_FEATURE = 1;
const ES_SEGUNDA_MANO_FINANCIACION_FEATURE = 18;
const ES_SEGUNDA_MANO_FEATURE_VALUE = 96841;
const ES_SEGUNDA_MANO_FINANCIACION_FEATURE_VALUE = 96843;
const PONER_DESDE_FEATURE = 9;
const PONER_DESDE_FEATURE_VALUE = 12626;

$excluir_envio_mail = [
    '8.11.1277361',
    '3.39.1272937',
    // "5.18.1257785"
];

// 62408

// REVISAR EL ID_MODELO => 100054289
// REVISAR EL ID_MODELO => 100015865
// REVISAR EL ID_MODELO => 100021347
// REVISAR EL ID_MODELO => 100000049
// REVISAR EL ID_MODELO => 100054287
// REVISAR EL ID_MODELO => 34571
// REVISAR EL ID_MODELO => 31926
// REVISAR EL ID_MODELO => 100003250
// REVISAR EL ID_MODELO => 34334
// REVISAR EL ID_MODELO => 100055320
// REVISAR EL ID_MODELO => 34334

// articulo de id 100143823
// articulo de id 100143828
// articulo de id 100143820
// articulo de id 100143824
// articulo de id 100143822
// articulo de id 100143821
// articulo de id 100143827
// articulo de id 100260424
// articulo de id 100260426
// articulo de id 39520
// articulo de id 100103201
// articulo de id 100260420
// articulo de id 100260418
// articulo de id 100260419
// articulo de id 100260421
// articulo de id 100260422
// articulo de id 100260423
// articulo de id 100260417
// articulo de id 100143825
// articulo de id 100143826
// articulo de id 100143829
// articulo de id 100260370
// articulo de id 100163592
// articulo de id 100260371
// 55018
// articulo de id 100260416
// 100260374
// articulo de id 100260373
// articulo de id 100260372
// id articulo => 39855
// id articulo => 100109148
// articulo de id 300064154
// articulo de id 300064153
// id articulo => 100001936

// [Procesar_v_sinc_tarifa_cabecera] - [117] Viene tarifa cabecera 103808978 antes de la creacion del articulo de id 100143822
// [Procesar_v_sinc_tarifa_cabecera] - [117] Viene tarifa cabecera 103808979 antes de la creacion del articulo de id 100143822
// [Procesar_v_sinc_tarifa_cabecera] - [110] Viene tarifa cabecera 103522725 antes de la creacion del articulo de id 100143822
// [Procesar_v_sinc_tarifa_cabecera] - [110] Viene tarifa cabecera 103522723 antes de la creacion del articulo de id 100143821
// [Procesar_v_sinc_tarifa_cabecera] - [117] Viene tarifa cabecera 103808975 antes de la creacion del articulo de id 100143820
// [Procesar_v_sinc_tarifa_cabecera] - [110] Viene tarifa cabecera 103254548 antes de la creacion del articulo de id 100143820
// [Procesar_v_sinc_tarifa_cabecera] - [117] Viene tarifa cabecera 103808976 antes de la creacion del articulo de id 100143821
// [Procesar_v_sinc_tarifa_cabecera] - [117] Viene tarifa cabecera 103808974 antes de la creacion del articulo de id 100143820
// [Procesar_v_sinc_tarifa_cabecera] - [106] Se creo tarifa cabecera 103501036, con el id articulo => 100103201

//
// REVISAR COMBINACIONES Y COMBINACIONES UNICA DEL ID PRODCUT 62409
// REVISAR COMBINACIONES Y COMBINACIONES UNICA DEL ID PRODCUT 61242
// REVISAR COMBINACIONES Y COMBINACIONES UNICA DEL ID PRODCUT 62459

$procesando = Db::getInstance()->getRow('SELECT * FROM aalv_bandera_integracion');

if ($procesando['activo'] == 0) {
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=1, fecha=now() WHERE id=1');
    /* Explicacion de Array_excluidos
    *   v_sinc_w_caracter_orden => PrestaShop no permite ordernar las caracteristicas de las combinacions
    */
    $array_excluidos = ['v_sinc_w_caracter_orden'];

    $start_time = microtime(true);
    $URL = 'http://127.0.0.1:59000/integracion/CambiosPendientes/?destino=2&limit=500';
    $validar = @file_get_contents($URL);
    if (! $validar) {
        $auxiliares->sendmail('Sin conexion en la integración.');
        exit();
    }
    $content = $auxiliares->peticionget($URL);
    $end_time = microtime(true);
    $elapsed = $end_time - $start_time;
    $elapsed_time_ms_inmi = $elapsed * 1000;
    echo 'Tiempo transcurrido GET INICIAL: '.formatElapsedTime($elapsed_time_ms_inmi)."\n\n";
    $jsonData = $content;
    $obj = json_decode($jsonData, true);
    $results = $obj['results'];

    $tiempo_total = 0;

    foreach ($results as $value) {
        if (in_array($value['transaccion'], $excluir_envio_mail)) {
            continue;
        }
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');
            echo date('H:i:s d/m/Y').' - TRANSACCION ['.$value['transaccion'].'] INICIADA => ';
            Db::getInstance()->Execute("INSERT INTO aalv_integracion_transacciones
                                            (id, url_transaccion, fecha_creacion, primerid, total_cambios, fecha_confirmacion)
                                        VALUES
                                            ('".$value['transaccion']."','".$value['url_transaccion']."','".$value['fecha_creacion']."',".$value['primer_idcambiotabla'].','.$value['total_cambios'].",'0000-00-00 00:00:00')");

            $start_time = microtime(true);
            $contentitem = $auxiliares->peticionget($value['url_transaccion']);
            $end_time = microtime(true);
            $elapsed = $end_time - $start_time;
            $elapsed_time_ms = $elapsed * 1000;
            $tiempo_total = $tiempo_total + $elapsed_time_ms;
            $jsonDataitem = trim(stripslashes($contentitem), '"');
            $objitem = json_decode($jsonDataitem, true);
            $sumar_tiempo = 0;
            if (! is_array($objitem)) {
                $objitem = json_decode($contentitem, true);
                if (! is_array($objitem)) {
                    $auxiliares->sendmail('ERROR en la Transaccion ['.$value['transaccion'].'], mirar los datos que llegan');

                    continue;
                }
            }
            foreach ($objitem['results'] as $valoresitem) {

                Db::getInstance()->Execute("INSERT INTO aalv_integracion_cambios
                                                (tabla, fila, data, tipo, transaccion, fecha_confirmacion)
                                            VALUES
                                                ('".$valoresitem['tabla']."',".$valoresitem['fila'].",'".pSQL(json_encode($valoresitem['data']))."','".$valoresitem['tipo']."','".$value['transaccion']."','0000-00-00 00:00:00')");
                $nombreArchivo = dirname(__DIR__).'/integracion/'.$valoresitem['tabla'].'.php';
                $nombreClase = $valoresitem['tabla'].'Class';
                $procesar = 'Procesar_'.$valoresitem['tabla'];

                if (in_array($valoresitem['tabla'], $array_excluidos)) {
                    continue;
                }

                if (file_exists($nombreArchivo)) {
                    include_once $nombreArchivo;
                    if (class_exists($nombreClase)) {
                        $objeto = new $nombreClase;
                        if (method_exists($objeto, $procesar)) {
                            $start_time = microtime(true);
                            call_user_func([$objeto, $procesar], $valoresitem['data'], $valoresitem['fila'], $valoresitem['tipo']);
                            $end_time = microtime(true);
                            $elapsed = $end_time - $start_time;
                            $elapsed_time_ = $elapsed * 1000;
                            $sumar_tiempo = $sumar_tiempo + $elapsed_time_;
                            $tiempo_total = $tiempo_total + $elapsed_time_;
                        } else {
                            $auxiliares->sendmail("El método $procesar no existe en la clase $nombreClase.");
                        }
                    } else {
                        $auxiliares->sendmail("La clase $nombreClase no existe en el archivo $nombreArchivo.");
                    }
                } else {
                    $auxiliares->sendmail("El archivo $nombreArchivo no existe.");
                }
            }
            Db::getInstance()->Execute("UPDATE aalv_integracion_cambios set fecha_confirmacion=now() where transaccion='".$value['transaccion']."'");
            Db::getInstance()->Execute("UPDATE aalv_integracion_transacciones set fecha_confirmacion=now() where id='".$value['transaccion']."'");
            echo 'TRANSACCION ['.$value['transaccion'].'] INSERTADA => CAMBIOS TOTAL ['.$value['total_cambios']."] \n";
            $auxiliares->peticionget($value['url_confirmacion']);

            // Confirmar la transacción manualmente
            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            // Revertir la transacción manualmente en caso de error
            Db::getInstance()->execute('ROLLBACK');
            echo 'Error: '.$e->getMessage()."\n";
            // $auxiliares->sendmail("Error: " . $e->getMessage());
            // die();
        }
    }
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=0, fecha=now() WHERE id=1');
} else {
    echo "\nSe esta procesando.\n";
}
echo "\n\n\n";

function formatElapsedTime($elapsed_time_ms)
{
    $hours = floor($elapsed_time_ms / 3600000);
    $minutes = floor(($elapsed_time_ms % 3600000) / 60000);
    $seconds = floor((($elapsed_time_ms % 3600000) % 60000) / 1000);
    $milliseconds = round((($elapsed_time_ms % 3600000) % 60000) % 1000);

    return "{$hours}h {$minutes}m {$seconds}s {$milliseconds}ms";
}
