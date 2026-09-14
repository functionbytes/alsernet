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

function escribesql($sql)
{
    $stdout = fopen(dirname(__FILE__).'/queriescatproductnew.txt', 'a');
    fwrite($stdout, $sql);
    fwrite($stdout, "\n");
    fclose($stdout);
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

function AsociarProducto($data, $dbh)
{

    $id_modelo = $data['id'];

    $id_productps = Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$id_modelo);

    if (''.$id_productps != '') {

        $rows = Db::getInstance()->ExecuteS('select * from aalv_category_product where id_product='.$id_productps);

        foreach ($rows as $row) {

            $id_categoryps = $row['id_category'];
            $id_nav = ''.Db::getInstance()->getValue('SELECT id_nav FROM aalv_category_import WHERE id_cat='.$id_categoryps);

            echo "<br>catps $id_categoryps id_nav $id_nav";

            if ($id_nav != '') {
                if (ExistePathCategory($id_modelo, (int) $id_nav, $dbh)) {
                    echo 'existe';
                } else {
                    echo 'no existe';
                    Db::getInstance()->Execute('delete from aalv_category_product where id_category='.$id_categoryps.' and id_product='.$id_productps);
                    Db::getInstance()->Execute('delete from aalv_category_product_import where id_category='.$id_categoryps.' and id_product='.$id_productps);

                    // escribesql("delete from aalv_category_product where id_category=".$id_categoryps. " and id_product=".$id_productps.";");
                    // escribesql("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$id_productps.";");

                }
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

// $rows = getdatarows($dbh,"SELECT id FROM modelo order by id limit ". ((int)Tools::getValue("product")) .",1");

$rows = getdatarows($dbh, 'SELECT id FROM modelo order by id');

foreach ($rows as $row) {
    AsociarProducto($row, $dbh);

}

echo 'acaba';
