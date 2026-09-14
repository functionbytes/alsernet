<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

setlocale(LC_CTYPE, 'es.UTF16');
$dbcon = connectBD();

$where = '';
$actualizar = false;
$debug = false;
$csv = false;
$test = false;
if (is_array($argv)) {
    foreach ($argv as $parametro) {
        if (is_numeric($parametro) || strpos($parametro, ',')) {
            $where = ' where id_product IN ('.$parametro.')';
        }
        if ($parametro == 'actualizar') {
            $actualizar = true;
        }
        if ($parametro == 'debug') {
            $debug = true;
        }
        if ($parametro == 'csv') {
            $csv = true;
        }
        if ($parametro == 'test') {
            $test = true;
        }
    }
}

if ($_GET['id_product']) {
    $where = ' where id_product IN ('.$_GET['id_product'].')';
}

echo "Referencia;id_product;id_attribute;Estado gestión;Producto activo;Modelo activo;Visibilidad Prod.;Visibilidad Mod.\n";

$query = 'SELECT id_product FROM aalv_product_import'.$where.' ORDER BY id_product DESC';

$productos = Db::getInstance()->ExecuteS($query);

foreach ($productos as $ps) {
    $stock = Db::getInstance()->ExecuteS('SELECT
                                            pa.id_product_attribute,
                                            sa.quantity,
                                            pa.reference,
                                            ap.active
                                          FROM
                                            aalv_stock_available sa
                                            LEFT JOIN aalv_product_attribute pa ON sa.id_product = pa.id_product AND sa.id_product_attribute = pa.id_product_attribute
                                            LEFT JOIN aalv_product ap on ap.id_product = pa.id_product
                                          WHERE
                                            ap.id_product = '.$ps['id_product']);
    if (count($stock) == 0) {
        // Producto
        $stock = Db::getInstance()->ExecuteS('SELECT
                                                ap.reference,
                                                asa.quantity,
                                                ap.active
                                              FROM
                                                aalv_product ap
                                                left join aalv_stock_available asa on asa.id_product = ap.id_product
                                              WHERE
                                                ap.id_product = '.$ps['id_product']);
    }

    foreach ($stock as $ps_stock) {
        $actualizo_stock = 0;
        $sql_antigua = 'SELECT
                            stoc.stock_actual,
                            prod.externo_disponibilidad,
                            prod.etiqueta,
                            prod.estado_gestion,
                            prod.activo AS producto_activo,
                            mode.activo AS modelo_activo
                        FROM
                            control_stock stoc
                            LEFT JOIN producto prod on prod.referencia = stoc.referencia
                            LEFT JOIN modelo mode on mode.id = prod.id_modelo
                        WHERE stoc.referencia = ';

        if ($ps_stock['reference'] != '') {
            $sql_antigua .= "'".$ps_stock['reference']."'";
        } else {
            PrestaShopLogger::addLog('Producto sin referencia',
                1, null, 'Product', $ps_stock['id_product'], false);

            continue;
        }

        $datos = mysqli_query($dbcon, $sql_antigua);
        if (mysqli_num_rows($datos) == 0) {
            if ($debug) {
                echo "Producto sin registros de stock en web antigua\n";
            }
            PrestaShopLogger::addLog('Producto sin registros de stock en web antigua',
                1, null, 'Product', $ps_stock['id_product'], false);
            // continue;
        }
        $web_antigua = mysqli_fetch_array($datos, MYSQLI_ASSOC);

        $TMP = '';
        $diferencia = 0;
        $product = new Product($ps['id_product']);
        $get_visibilidad_producto = Product::getVisibilidad($ps['id_product'], false, $debug) ? 1 : 0;
        $get_visibilidad_modelo = Product::getVisibilidad($ps['id_product'], $ps_stock['id_product_attribute'], $debug) ? 1 : 0;
        $control_stock = Product::controlStock($ps['id_product'], $ps_stock['id_product_attribute'], $web_antigua['stock_actual'], $debug);
        if (! $product->active && $web_antigua['estado_gestion'] > 0) {

            echo $ps_stock['reference'].';'.
            $ps['id_product'].';'.
            $ps_stock['id_product_attribute'].';'.
            $web_antigua['estado_gestion'].';'.
            $web_antigua['producto_activo'].';'.
            $web_antigua['modelo_activo'].';'.
            $get_visibilidad_producto.';'.
            $get_visibilidad_modelo.
            "\n";
        }

    }

}

function connectBD()
{

    return $dbcon;
}

function closeBD($dbcon)
{
    mysqli_close($dbcon);
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, 'alsernet:May.8006763');
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}
