<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

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

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/restaurarcategoryproductimport.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function escomunrec($id)
{

    $padre = Db::getInstance()->getValue('SELECT id_parent FROM '._DB_PREFIX_.'category WHERE id_category='.$id);
    $escomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= '.$id);

    if ($escomun == '') {

        if ($padre <= 2) {
            return false;
        } else {
            return escomunrec($padre);
        }

    } else {
        return true;
    }

}

function ExistePathCategory($id_modelo, $id_nav, $dbh)
{

    if ($id_nav != 0) {
        $elemento = getfieldvalue($dbh, 'select elemento from navegacion where id='.$id_nav);
        $id_padre = getfieldvalue($dbh, 'select id_padre from navegacion where id='.$id_nav);

        // ver si existe elemento (id_valor) e id_modelo en perfiles_nav

        if (''.$elemento != '') {
            $existe = ''.getfieldvalue($dbh, 'select id from perfiles_nav where id_valor='.$elemento.' and id_modelo='.$id_modelo);

            if ($existe != '') {
                return ExistePathCategory($id_modelo, (int) $id_padre, $dbh);
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return true;
    }

}

function padreorigen($catim, $lista)
{
    if ($catim['id_padre'] == 0) {
        return in_array($catim['id_origen'], $lista);
    } else {

        $catrec = Db::getInstance()->getRow('SELECT * FROM aalv_category_import WHERE id_nav='.$catim['id_padre']);

        if (in_array($catim['id_origen'], $lista)) {
            return padreorigen($catrec, $lista);
        } else {
            return false;
        }

    }
}

function CrearCategoryProductImport($datos, $dbh)
{

    $catsorigen = getdatarows($dbh, 'SELECT id, id_valor, principal FROM perfiles_nav where id_modelo='.$datos['id_modelo']);

    foreach ($catsorigen as $cat) {

        $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$cat['id_valor']);

        foreach ($catimport as $catim) {

            if (ExistePathCategory($datos['id_modelo'], $catim['id_nav'], $dbh)) {

                $micat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id='.$catim['id']);
                addsql('REPLACE INTO aalv_category_product_import(id_category, id_product, fila) VALUES ('.$micat.','.$datos['id_product'].','.$cat['id'].');');

            }

        }

    }
}

try {

    $dsn = 'mysql:host=127.0.0.1;dbname=alvarezps_migracion_db';
    $dbh = new PDO($dsn, 'alvarezps_migracion_dbu', 'eyb54%X45');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$rows = Db::getInstance()->ExecuteS('select id_product, id_modelo from aalv_product_import');

foreach ($rows as $row) {

    CrearCategoryProductImport($row, $dbh);
}
echo 'acaba';
