<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// SELECT data  FROM `aalv_integracion_cambios` WHERE `tabla` LIKE '%perfiles_nav%' AND `data` LIKE '%100048423%' and data like '%"principal":true%';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/restaurarprincipal3.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function escomun($id_cat)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat='.$id_cat);

    if ($existecomun != '') {
        return true;
    } else {
        return false;
    }

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

function ExistePathCategory($producto, $id_cat)
{

    if (($id_cat > 2) && (! escomun($id_cat))) {

        $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$id_cat);
        $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

        if ($existe != '') {
            return ExistePathCategory($producto, (int) $id_padre);
        } else {
            return false;
        }
    } else {
        return true;
    }

}

function escomunnav($id_nav)
{

    $existecomun = ''.Db::getInstance()->getValue('SELECT id FROM aalv_categorias_comunes WHERE id_nav='.$id_nav);

    if ($existecomun != '') {
        return true;
    } else {
        return false;
    }

}

function ExistePathCategoryNav($producto, $id_nav)
{

    if (! escomunnav($id_nav) && ($id_nav != 0)) {

        $elemento = Db::getInstance()->getValue('SELECT id_origen FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_padre = Db::getInstance()->getValue('SELECT id_padre FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_cat = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$id_nav);

        // ver si existe id_cat  y producto en  category_import

        if (''.$id_cat != '') {
            $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

            if ($existe != '') {
                return ExistePathCategoryNav($producto, (int) $id_padre);
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

echo 'empieza';
$tiempo_inicial = microtime(true);

$rowsp = Db::getInstance()->ExecuteS('SELECT id_product, id_modelo FROM aalv_product_import WHERE id_product>=55053 and id_product in (SELECT id_product  FROM aalv_product WHERE id_category_default = 2)');

$i = 0;
foreach ($rowsp as $prod) {

    $producto = $prod['id_product'];
    $modelo = $prod['id_modelo'];

    // sacar de cada modelo su cat principal

    $datos = ''.Db::getInstance()->getValue("SELECT data  FROM aalv_integracion_cambios WHERE tabla LIKE '%perfiles_nav%' AND data LIKE '%".$modelo."%' and data like '%true%'");

    if ($datos != '') {

        $valores = json_decode($datos, true);

        $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$valores['id_valor']);

        foreach ($catimport as $catim) {
            $idcatps = $catim['id_cat'];
            $idnav = $catim['id_nav'];

            $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$producto);

            if ($existe != '') {

                if (ExistePathCategoryNav($producto, $idnav)) {
                    if (escomunrec($idcatps)) {

                        $cat = new Category($idcatps);
                        if ($cat->sport == 5) {

                            addsql('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$producto.';');
                            addsql('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$producto.';');

                        }
                    } else {
                        addsql('UPDATE aalv_product set id_category_default='.$idcatps.' where id_product='.$producto.';');
                        addsql('UPDATE aalv_product_shop set id_category_default='.$idcatps.' where id_product='.$producto.';');

                    }
                }
            }
        }
    }

    $rows = Db::getInstance()->ExecuteS('select * from aalv_category_product where id_product='.$producto);

    foreach ($rows as $catprod) {

        if (! ExistePathCategory($catprod['id_product'], $catprod['id_category'])) {

            if (! escomunrec($catprod['id_category'])) {

                addsql('DELETE FROM aalv_category_product where id_product='.$catprod['id_product'].' and id_category='.$catprod['id_category'].';');
            }
            // echo "<br/>No existe ".$catprod["id_category"];
        }

    }
}

$tiempo_final = microtime(true);

echo 'acaba '.($tiempo_final - $tiempo_inicial);
