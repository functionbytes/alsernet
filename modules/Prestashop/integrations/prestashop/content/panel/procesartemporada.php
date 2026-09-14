<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

header('Content-Type: text/plain');

$rows = Db::getInstance()->ExecuteS("select distinct id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE etiqueta LIKE '%TEMPORADA_VERANO%' OR etiqueta LIKE '%TEMPORADA_INVIERNO%') union SELECT id_product FROM aalv_combinacionunica_import WHERE etiqueta LIKE '%TEMPORADA_VERANO%' OR etiqueta LIKE '%TEMPORADA_INVIERNO%' ORDER BY 1 limit ".((int) Tools::getValue('id')).',1');

foreach ($rows as $row) {

    $p = new Product($row['id_product']);
    $p->update();
    echo $p->name[1];
}
