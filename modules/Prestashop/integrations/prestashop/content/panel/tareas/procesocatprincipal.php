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
    $stdout = fopen(dirname(__FILE__).'/queriescatprincipal.txt', 'a');
    fwrite($stdout, $sql);
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

function escomun($id_nav)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id FROM aalv_categorias_comunes WHERE id_nav='.$id_nav);

    // echo  "<br>existecomun $existecomun";

    if ($existecomun != '') {
        return true;
    } else {
        return false;
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

function AsociarProducto($data, $dbh)
{

    $id_modelo = $data['id'];

    $id_productps = Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$id_modelo);

    if (''.$id_productps != '') {

        $catsorigen = getdatarows($dbh, 'SELECT id, id_valor, principal FROM perfiles_nav where id_modelo='.$id_modelo.' and principal=1');

        $catprincipal = 0;

        foreach ($catsorigen as $cat) {

            $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$cat['id_valor']);

            foreach ($catimport as $catim) {
                if (ExistePathCategory($id_modelo, $catim['id_nav'], $dbh)) {

                    $micat = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id='.$catim['id']);

                    if (escomunrec($micat)) {

                        $cat = new Category($micat);
                        if ($cat->sport == 5) {
                            $catprincipal = (int) $micat;
                        }
                    } else {
                        $catprincipal = (int) $micat;
                    }

                }
            }

        }

        if ($catprincipal != 0) {
            Db::getInstance()->Execute('UPDATE aalv_product set id_category_default='.$catprincipal.' where id_product='.$id_productps);
            Db::getInstance()->Execute('UPDATE aalv_product_shop set id_category_default='.$catprincipal.' where id_product='.$id_productps);
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

// $rows = getdatarows($dbh,"SELECT id FROM modelo order by id limit ". ((int)Tools::getValue("product")) .",1");

$rows = getdatarows($dbh, 'SELECT id FROM modelo order by id');

foreach ($rows as $row) {
    AsociarProducto($row, $dbh);

}

echo 'acaba';
