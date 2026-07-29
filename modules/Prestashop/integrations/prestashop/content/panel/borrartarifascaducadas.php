<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$sql = "SELECT id_specific_price FROM aalv_specific_price where now()>`to`  and `to`<>'0000-00-00 00:00:00'";

$rows = Db::getInstance()->executeS($sql);

foreach ($rows as $row) {
    $idtarifacabecera = ''.Db::getInstance()->getValue('SELECT id_tarifa_cabecera FROM aalv_specific_price_import where id_specific_price='.$row['id_specific_price']);

    if ($idtarifacabecera != '') {
        $sqldel = 'DELETE FROM aalv_tarifa_cabecera_import where id_tarifa_cabecera='.$idtarifacabecera;
        Db::getInstance()->execute($sqldel);
    }

    $sqldel2 = 'DELETE FROM aalv_specific_price_import where id_specific_price='.$row['id_specific_price'];
    Db::getInstance()->execute($sqldel2);
    $sqldel3 = 'DELETE FROM aalv_specific_price where id_specific_price='.$row['id_specific_price'];
    Db::getInstance()->execute($sqldel3);

}

echo 'acaba';
