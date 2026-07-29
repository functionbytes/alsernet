<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// SELECT data  FROM `aalv_integracion_cambios` WHERE `tabla` LIKE '%perfiles_nav%' AND `data` LIKE '%100048423%' and data like '%"principal":true%';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/restaurarpathcategorias.txt', 'a');
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

echo 'empieza';
$tiempo_inicial = microtime(true);

$rowsp = Db::getInstance()->ExecuteS('SELECT id_product, id_modelo FROM aalv_product_import WHERE id_product>=55053');

$i = 0;
foreach ($rowsp as $prod) {
    echo 'pasa';
    $producto = $prod['id_product'];
    $modelo = $prod['id_modelo'];

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
