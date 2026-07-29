<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

header('Content-Type: text/plain');

$rows = Db::getInstance()->ExecuteS('select  id_product from aalv_product_import ORDER BY id_product limit '.((int) Tools::getValue('id')).',1');

foreach ($rows as $row) {

    $p = new Product($row['id_product']);
    $p->update();
    echo $p->name[1];
}
