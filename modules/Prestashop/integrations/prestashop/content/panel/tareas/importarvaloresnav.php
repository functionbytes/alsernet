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

function Crearvalor($datos, $dbh)
{

    $sql = 'INSERT INTO aalv_valores_nav_import(id_origen, nombre) VALUES ('.$datos['id'].",'".$datos['nombre']."')";
    Db::getInstance()->Execute($sql);

}

try {

    $dsn = 'mysql:host=195.55.36.104;dbname=tienda';
    $dbh = new PDO($dsn, 'tiendalvad', 'Nov.299909');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id, nombre FROM valores_nav');
foreach ($rows as $row) {

    Crearvalor($row, $dbh);

}

echo 'Proceso acabado';
