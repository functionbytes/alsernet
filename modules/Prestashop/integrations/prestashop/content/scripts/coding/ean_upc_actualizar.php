<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
include ('/home/alvarez/LOG_INTEGRACION/dbantigua.php');
die();
$dbcon = connectBD();

$sql = Db::getInstance()->executeS("SELECT id_articulo , 0 AS combinacion, ean, upc FROM aalv_combinacionunica_import
WHERE estado_gestion != 0");
// SELECT id_articulo , 0 AS combinacion, ean, upc FROM aalv_combinacionunica_import WHERE estado_gestion != 0
// SELECT id_articulo , 1 AS combinacion, ean, upc FROM aalv_combinaciones_import WHERE estado_gestion != 0

$total = count($sql);
$nn = 0;
$suma = 0;
foreach ($sql as $value) {

    $sql_antigua = "SELECT ean13, upc FROM producto WHERE idarticulo = " . (int)$value['id_articulo'];
    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    if ($result_antigua && $re_antigua = mysqli_fetch_array($result_antigua)) {

        // if ($re_antigua[0] != '' && $re_antigua[1] != '') {

            // Validamos si hay diferencia real para hacer el update
            if (
                $re_antigua[0] !== $value['ean'] ||
                $re_antigua[1] !== $value['upc']
            ) {
                if ($value['combinacion']) {
                    // Es combinación
                    Db::getInstance()->Execute(
                        "UPDATE aalv_combinaciones_import
                         SET ean = '" . pSQL($re_antigua[0]) . "',
                             upc = '" . pSQL($re_antigua[1]) . "'
                         WHERE id_articulo = " . (int)$value['id_articulo']
                    );
                } else {
                    // Es simple
                    Db::getInstance()->Execute(
                        "UPDATE aalv_combinacionunica_import
                         SET ean = '" . pSQL($re_antigua[0]) . "',
                             upc = '" . pSQL($re_antigua[1]) . "'
                         WHERE id_articulo = " . (int)$value['id_articulo']
                    );
                }
                echo ";";
            }
        // }
    }
    echo ".";
    $nn++;
    if($nn == 100){
        $suma = $suma + 100;
        echo " => ".$total. " / ".$suma;
        echo "\n";
        $nn = 0;
    }
}

echo "LISTO";




