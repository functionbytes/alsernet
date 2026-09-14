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

// copyImg($manufacturer->id, null, $img_url, 'manufacturers');

function CrearAyuda($ayuda, $dbh)
{

    $titulo = ''.$ayuda['titulo'];
    $texto = ''.$ayuda['texto'];
    $enlace = ''.$ayuda['enlace'];
    $activo = ''.$ayuda['activo'];

    if ($activo == 'si') {
        $activo = '1';
    } else {
        $activo = '0';
    }

    Db::getInstance()->Execute("INSERT INTO aalv_ayudas(titulo, texto, enlace, activo, idorigen) VALUES ('".$titulo."','".$texto."','".$enlace."',".$activo.','.$ayuda['id'].')');

}

function CrearAyudaMod($ayuda, $dbh)
{

    $id_modelo = ''.$ayuda['id_modelo'];
    $id_ayuda = ''.$ayuda['id_ayuda'];
    $fila = ''.$ayuda['id'];

    $idprod = Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$id_modelo);

    $idayuda = Db::getInstance()->getValue('SELECT id FROM aalv_ayudas WHERE idorigen='.$id_ayuda);

    if ((''.$idprod != '') && (''.$idayuda != '')) {

        Db::getInstance()->Execute('INSERT INTO aalv_ayudas_prod(id_product, id_ayuda, orden, fila) VALUES ('.$idprod.','.$idayuda.',0,'.$fila.')');

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

Db::getInstance()->Execute('truncate table aalv_ayudas');
Db::getInstance()->Execute('truncate table aalv_ayudas_prod');

$rows = getdatarows($dbh, 'SELECT id,titulo,texto,enlace,activo FROM ayudas');
foreach ($rows as $row) {
    CrearAyuda($row, $dbh);
}

$rows = getdatarows($dbh, 'SELECT id, id_modelo, id_ayuda FROM ayudas_mod');
foreach ($rows as $row) {
    CrearAyudaMod($row, $dbh);
}

echo 'Proceso acabado';
