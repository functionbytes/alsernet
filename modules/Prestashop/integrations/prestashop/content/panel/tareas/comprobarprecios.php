<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getfieldvalue($dbh, $sql)
{
    $rows = $dbh->query($sql);
    foreach ($rows as $row) {
        return $row[0];
    }
}

function getdatarows($dbh, $sql)
{
    return $dbh->query($sql);
}

try {

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$idarticulo = Tools::getValue('art');

$sql = 'SELECT * FROM `tarifa_linea` where idtarifa_cabecera=(select ifnull((SELECT idtarifa_cabecera  FROM `tarifa_cabecera` WHERE idarticulo='.$idarticulo.' and idregpais=1 and now()>=finicio and now()<=ffin and estado = 1 order by finicio desc limit 1),(SELECT idtarifa_cabecera FROM `tarifa_cabecera` WHERE idarticulo='.$idarticulo.' and idregpais=1 and ffin is null and finicio<=now() and estado = 1 order by finicio desc limit 1)))';

$rows = getdatarows($dbh, $sql);
foreach ($rows as $row) {
    echo '<br/>bi '.$row['baseimp'];
    echo '<br/>bianterior '.$row['baseimp_anterior'];
    echo '<br/>cabecera '.$row['idtarifa_cabecera'];
    echo '<br/>linea '.$row['idtarifa_linea'];

}
