<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/catprodvalidar.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function ExistePathCategory($producto, $id_cat)
{

    if (($id_cat > 2) && ($id_cat < 4000)) {

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

$rowsp = Db::getInstance()->ExecuteS('select id_product from aalv_product');

foreach ($rowsp as $prod) {

    $producto = $prod['id_product'];

    $rows = Db::getInstance()->ExecuteS('select * from aalv_category_product where id_product='.$producto);

    foreach ($rows as $catprod) {

        if (! ExistePathCategory($catprod['id_product'], $catprod['id_category'])) {
            addsql('DELETE FROM aalv_category_product where id_product='.$catprod['id_product'].' and id_category='.$catprod['id_category'].';');

            // echo "<br/>No existe ".$catprod["id_category"];
        }

    }
}

$tiempo_final = microtime(true);

echo 'acaba '.($tiempo_final - $tiempo_inicial);
