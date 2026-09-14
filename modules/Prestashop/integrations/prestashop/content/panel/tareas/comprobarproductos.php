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

function Verproductos($datos, $dbh)
{

    $idproduct = Db::getInstance()->getValue('SELECT a.id_product FROM aalv_product_import a inner join aalv_product b on a.id_product=b.id_product WHERE a.id_modelo='.$datos['id']);

    if (''.$idproduct != '') {

        echo '<br>'.$datos['id'].' '.$datos['nombre'].' activo '.$datos['activo'];

        // ver categorias a las que pertenece el modelo
        $perfilesnav = getdatarows($dbh, 'SELECT id_valor, principal FROM perfiles_nav where id_modelo='.$datos['id'].' order by id');
        foreach ($perfilesnav as $perfilesnavitem) {
            echo '<br>'.$perfilesnavitem['id_valor'].' '.$perfilesnavitem['principal'];
        }

    }

}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarez_migracion_db';
    $dbh = new PDO($dsn, 'alvarez_migracion_dbu', 'N4p42#l6d');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = getdatarows($dbh, 'SELECT id, nombre, activo FROM modelo order by id limit 20');
foreach ($rows as $row) {
    Verproductos($row, $dbh);
}

echo 'Proceso acabado';
