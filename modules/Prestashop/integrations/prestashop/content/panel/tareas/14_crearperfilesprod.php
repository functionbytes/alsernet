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

function CrearPerfilprod($datos, $dbh)
{
    $orden = ''.$datos['orden'];

    if ($orden == '') {
        $orden = '0';
    }

    Db::getInstance()->Execute('INSERT INTO aalv_perfiles_prod_import(id, id_producto, id_valor, id_modelo, orden, activo) VALUES ('.$datos['id'].','.$datos['id_producto'].','.$datos['id_valor'].','.$datos['id_modelo'].','.$orden.',1)');
}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_perfiles_prod_import');
$rows = getdatarows($dbh, 'SELECT * FROM perfiles_prod');
foreach ($rows as $row) {
    CrearPerfilprod($row, $dbh);
}

echo 'Proceso acabado';
