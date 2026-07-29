<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

// <add key="aDSNMYSQL" value="DRIVER={MySQL ODBC 3.51 Driver};SERVER=82.223.36.198;DATABASE=psaddis_lacasadelosaromas;UID=psaddis_aromas;PWD=1@p.i5HS1y;OPTION=3;" />

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

function Actualizarprod($datos, $dbh)
{

    $idproduct = Db::getInstance()->getValue('SELECT a.id_product FROM aalv_product_import a inner join aalv_product b on a.id_product=b.id_product WHERE a.id_modelo='.$datos['id']);

    if (''.$idproduct != '') {

        Db::getInstance()->Execute('update aalv_product set active='.$datos['activo'].' where id_product='.$idproduct);
        Db::getInstance()->Execute('update aalv_product_shop set active='.$datos['activo'].' where id_product='.$idproduct);

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id, nombre, activo FROM modelo');
foreach ($rows as $row) {
    Actualizarprod($row, $dbh);
}

echo 'Proceso acabado';
