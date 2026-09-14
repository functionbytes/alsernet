<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$sql = 'SELECT id_articulo FROM `aalv_combinacionunica_import` WHERE id_product not in (select id_product from aalv_repositorio_stock) union SELECT id_articulo FROM `aalv_combinaciones_import` WHERE id_product_attribute not in (select id_product_attribute from aalv_repositorio_stock)';

$rows = Db::getInstance()->executeS($sql);

$ids = [];

foreach ($rows as $row) {

    echo '<br> '.$row['id_articulo'].'-'.Db::getInstance()->getValue("SELECT id  FROM `aalv_integracion_cambios` WHERE `tabla` = 'v_sinc_stock_central_web' AND `data` LIKE '%".$row['id_articulo']."%' ORDER BY id  DESC");
    $ids[] = ''.Db::getInstance()->getValue("SELECT id  FROM `aalv_integracion_cambios` WHERE `tabla` = 'v_sinc_stock_central_web' AND `data` LIKE '%".$row['id_articulo']."%' ORDER BY id  DESC");
}

echo implode(',', $ids);
