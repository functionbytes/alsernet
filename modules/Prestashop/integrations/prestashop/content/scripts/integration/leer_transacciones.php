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

$activo = Db::getInstance()->getValue('SELECT activo FROM aalv_bandera_integracion WHERE id = 1');
if ($activo == 0) {
    try {
        // Iniciar la transacción manualmente
        // Db::getInstance()->execute("START TRANSACTION");

        Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=1, fecha=now() WHERE id=1');

        // $URL = "http://223.1.1.22:9000/integracion/CambiosPendientes/?destino=2&limit=500";
        $URL = 'http://127.0.0.1:59000/integracion/CambiosPendientes/?destino=2&limit=500';

        $content = $auxiliares->peticionget($URL);
        $jsonData = $content;
        $obj = json_decode($jsonData, true);
        $results = $obj['results'];
        $keys = array_column($results, 'primer_idcambiotabla');
        array_multisort($keys, SORT_ASC, $results);
        $nn = 0;
        foreach ($results as $valores) {
            // if($valores["transaccion"] == '5.16.1423405_1' || $valores["transaccion"] == '5.16.1423405_2'){
            //     continue;
            // }
            $update = false;
            $transaccion = $valores['transaccion'];
            echo '('.$nn.')Lee la transaccion => '.$transaccion."\n";

            $exite_transacciones = Db::getInstance()->executeS("SELECT * FROM aalv_integracion_transacciones WHERE id = '".$transaccion."'");

            if (count($exite_transacciones) == 0) {
                $update = true;
                Db::getInstance()->Execute("INSERT INTO aalv_integracion_transacciones
                        (id, url_transaccion, fecha_creacion, primerid, total_cambios, fecha_confirmacion)
                    VALUES
                        ('".$valores['transaccion']."','".$valores['url_transaccion']."','".$valores['fecha_creacion']."',".$valores['primer_idcambiotabla'].','.$valores['total_cambios'].",'0000-00-00 00:00:00')");
            }

            $contentitem = $auxiliares->peticionget($valores['url_transaccion'].'&limit=5000');
            // Verificamos si la cadena contiene comillas dobles al principio o al final
            if (substr($contentitem, 0, 1) === '"' || substr($contentitem, -1) === '"') {
                // Verificamos si la cadena contiene barras invertidas
                if (strpos($contentitem, '\\') !== false) {
                    // Si contiene barras invertidas, aplicamos stripslashes
                    $contentitem = stripslashes($contentitem);
                }

                // Si tiene comillas dobles al principio o al final, las eliminamos
                $contentitem = trim($contentitem, '"');
                throw new Exception('REVISAR LAS COMILLAS - '.$transaccion);
                break;
            }
            // $jsonDataitem   = trim(stripslashes($contentitem), '"');
            $objitem = json_decode($contentitem, true);
            $count = $objitem['count'];

            if ($count > 0) {
                foreach ($objitem['results'] as $valoresitem) {
                    $exite_cambios = Db::getInstance()->executeS("SELECT * FROM aalv_integracion_cambios WHERE
                        tabla = '".$valoresitem['tabla']."'
                        AND fila = ".$valoresitem['fila']."
                        AND tipo = '".$valoresitem['tipo']."'
                        AND transaccion = '".$transaccion."'");
                    if (count($exite_cambios) > 0) {
                        continue;
                    }
                    echo 'Lee los cabiso de la tabla => '.$valoresitem['tabla']."\n";

                    $datos = Db::getInstance()->Execute("INSERT INTO aalv_integracion_cambios
                            (tabla, fila, data, tipo, transaccion, fecha_confirmacion)
                        VALUES
                            ('".$valoresitem['tabla']."',
                            ".$valoresitem['fila'].",
                            '".pSQL(json_encode($valoresitem['data']))."',
                            '".$valoresitem['tipo']."',
                            '".$transaccion."',
                            NOW())");

                }

                $urlconfirm = 'http://127.0.0.1:59000/integracion/ConfirmarTransaccion/2/'.$transaccion.'/';
                $contentconfirm = $auxiliares->peticionget($urlconfirm);
                $jsonconfirmData = trim(stripslashes($contentconfirm), '"');
                $objconfirm = json_decode($jsonconfirmData, true);

                if ($update) {
                    Db::getInstance()->Execute("UPDATE aalv_integracion_transacciones SET fecha_confirmacion = NOW() WHERE id = '".$transaccion."'");
                }
                echo 'Finaliza la transaccion => '.$transaccion."\n";
            } else {
                throw new Exception('Transaccion Sin datos - '.$transaccion);
                break;
            }
            $nn++;
        }

        Db::getInstance()->Execute('UPDATE aalv_bandera_integracion SET activo = 0, fecha = NOW() WHERE id = 1');

        echo 'Proceso terminado';

        // Confirmar la transacción manualmente
        // Db::getInstance()->execute("COMMIT");
    } catch (Exception $e) {
        // Revertir la transacción manualmente en caso de error
        // Db::getInstance()->execute("ROLLBACK");
        $auxiliares->sendmail('Error: '.$e->getMessage());

    }
} else {
    echo 'en proceso';
}
