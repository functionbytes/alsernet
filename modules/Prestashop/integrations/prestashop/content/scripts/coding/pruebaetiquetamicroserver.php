<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

$sql = Db::getInstance()->getValue('select etiqueta from aalv_combinaciones_import aci WHERE id_articulo = '.$_GET['idarticulo'].'
union
select etiqueta from aalv_combinacionunica_import aci2 WHERE id_articulo = '.$_GET['idarticulo']);

echo json_encode($sql);
