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

const PORC_IVA_21 = 13;
const ES_SEGUNDA_MANO_FEATURE = 1;
const ES_SEGUNDA_MANO_FINANCIACION_FEATURE = 18;
const ES_SEGUNDA_MANO_FEATURE_VALUE = 96841;
const ES_SEGUNDA_MANO_FINANCIACION_FEATURE_VALUE = 96843;
const VENTA_ESP_FEATURE = 17;
const VENTA_ESP_FEATURE_VALUE = 93179;

const VENTA_DNI_FEATURE = 23;
const VENTA_DNI_FEATURE_VALUE = 263658;

const VENTA_ESCOPETA_FEATURE = 23;
const VENTA_ESCOPETA_FEATURE_VALUE = 263659;

const VENTA_RIFLE_FEATURE = 23;
const VENTA_RIFLE_FEATURE_VALUE = 263660;

const VENTA_CORTA_FEATURE = 23;
const VENTA_CORTA_FEATURE_VALUE = 263661;

const VENTA_ESPANA_PORTUGAL_FEATURE = 22;
const VENTA_ESPANA_PORTUGAL_FEATURE_VALUE = 243754;

// $sql_txt = "    SELECT
//                     *
//                 FROM
//                     aalv_integracion_cambios
//                 where
//                     processed = 0
//                     and tabla in ('v_sinc_w_modelo','v_sinc_w_producto','v_sinc_stock_central_web')
//                 ORDER BY
//                     CASE
//                         WHEN tabla LIKE '%v_sinc_w_modelo%' THEN 1
//                         WHEN tabla LIKE '%v_sinc_w_producto%' THEN 2
//                         WHEN tabla LIKE '%v_sinc_w_valores_prod%' THEN 3
//                         WHEN tabla LIKE '%v_sinc_w_perfiles_prod%' THEN 4
//                         ELSE 5
//                     END,
//                 id limit 1000";

// $hora = (int)date('G'); // Hora 0-23 sin ceros a la izquierda

// if ($hora >= 0 && $hora <= 7) {
// Acción si es entre las 00:00 y 08:00
$sql_txt = "    SELECT
                        *
                    FROM
                        aalv_integracion_cambios
                    where
                        processed = 0
                    ORDER BY
                        CASE
                            WHEN tabla LIKE '%v_sinc_w_modelo%' THEN 1
                            WHEN tabla LIKE '%v_sinc_w_producto%' THEN 2
                            WHEN tabla LIKE '%v_sinc_w_valores_prod%' THEN 3
                            WHEN tabla LIKE '%v_sinc_w_perfiles_prod%' THEN 4
                            ELSE 5
                        END,
                    id limit 1000";
// }

$sql = Db::getInstance()->executeS($sql_txt);

// $sql = Db::getInstance()->executeS("SELECT * FROM aalv_integracion_cambios where id in (17555336,17555337,17555338,17555339)");

$procesando = Db::getInstance()->getRow('SELECT * FROM aalv_bandera_integracion WHERE id = 2');

if ($procesando['activo'] == 0) {
    Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=1, fecha = NOW() WHERE id = 2');

    /* Explicacion de Array_excluidos
    *   v_sinc_w_caracter_orden => PrestaShop no permite ordernar las caracteristicas de las combinacions
    *   v_sinc_lote => Lotes
    *   v_sinc_llote => Lotes
    *   v_sinc_lllote => Lotes
    *   v_sinc_lloteidioma => Lotes por idiomas
    *   v_sinc_tarifalote => Precios de los lotes
    *   v_sinc_w_producto_imagen => Imagenes del producto
    *   v_sinc_w_modelo_imagen => Imagenes del modelo
    *   v_sinc_w_mod_documento => Documentos del modelo
    *   v_sinc_w_mod_video => Videos del modelo
    *   v_sinc_w_caracter_prod_idioma => Talla, color en diferentes idiomas
    *   v_sinc_w_valores_prod_idioma => Características por idiomas
    *   v_sinc_w_modelo_idioma => Idiomas de los modelos
    *   v_sinc_tag_temporal => Etiquetas de un articulo
    */
    $array_excluidos = [
        'v_sinc_w_caracter_orden',
        'v_sinc_lote',
        'v_sinc_llote',
        'v_sinc_lllote',
        'v_sinc_lloteidioma',
        'v_sinc_tarifalote',
        'v_sinc_w_producto_imagen',
        'v_sinc_w_modelo_imagen',
        'v_sinc_w_mod_documento',
        'v_sinc_w_mod_video',
        'v_sinc_w_caracter_prod_idioma',
        'v_sinc_w_valores_prod_idioma',
        'v_sinc_w_modelo_idioma',
        'v_sinc_tag_temporal',
        'v_sinc_w_navegacion',
        'v_sinc_w_perfiles_nav',
        'v_sinc_w_valores_nav_idioma',
        'v_sinc_w_valores_nav',
    ];
    $update_bandera = true;
    $nn = 0;
    foreach ($sql as $value) {
        if (in_array($value['tabla'], $array_excluidos)) {
            Db::getInstance()->Execute("UPDATE aalv_integracion_cambios SET processed=2 WHERE id='".$value['id']."'");

            continue;
        }
        // dump($value);
        try {
            // Iniciar la transacción manualmente
            if (! ($value['tabla'] == 'v_sinc_w_producto' && $value['tipo'] == 1)) {
                Db::getInstance()->execute('START TRANSACTION');
            }
            echo '('.$nn.') '.date('H:i:s d/m/Y').' - TABLA ['.$value['tabla'].'] INICIADA => ';

            $nombreArchivo = dirname(__DIR__).'/integration/'.$value['tabla'].'.php';
            $nombreClase = $value['tabla'].'Class';
            $procesar = 'Procesar_'.$value['tabla'];
            $value['data'] = json_decode($value['data'], true);

            if (file_exists($nombreArchivo)) {
                include_once $nombreArchivo;
                if (class_exists($nombreClase)) {
                    $objeto = new $nombreClase;
                    if (method_exists($objeto, $procesar)) {
                        call_user_func([$objeto, $procesar], $value['data'], $value['fila'], $value['tipo']);
                    } else {
                        $auxiliares->sendmail("El método $procesar no existe en la clase $nombreClase.");
                        break;
                    }
                } else {
                    $auxiliares->sendmail("La clase $nombreClase no existe en el archivo $nombreArchivo.");
                    break;
                }
            } else {
                $auxiliares->sendmail("El archivo $nombreArchivo no existe.");
                break;
            }

            Db::getInstance()->Execute("UPDATE aalv_integracion_cambios SET processed=1 WHERE id='".$value['id']."'");

            if (! ($value['tabla'] == 'v_sinc_w_producto' && $value['tipo'] == 1)) {
                echo 'TRANSACCION ['.$value['transaccion']."] INSERTADA \n";
            }

            // Confirmar la transacción manualmente
            Db::getInstance()->execute('COMMIT');
        } catch (Exception $e) {
            // Revertir la transacción manualmente en caso de error
            $update_bandera = false;
            if (! ($value['tabla'] == 'v_sinc_w_producto' && $value['tipo'] == 1)) {
                Db::getInstance()->execute('ROLLBACK');
            }

            $auxiliares->sendmail('Error: '.$e->getMessage());
            break;
        }
        // if($value['tabla'] == 'v_sinc_tarifa_linea' && $value['tipo'] == 1){
        //     echo "Espera 1 segundo en tarifa_linea \n";
        //     sleep(1);
        // }
        $nn++;
    }

    if ($update_bandera) {
        Db::getInstance()->Execute('UPDATE aalv_bandera_integracion set activo=0, fecha=NOW() WHERE id=2');
    }
} else {
    echo "\nSe esta procesando.\n";
}
echo "\n\n\n";
