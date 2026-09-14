<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function escomunrec($id)
{

    $padre = Db::getInstance()->getValue('SELECT id_parent FROM '._DB_PREFIX_.'category WHERE id_category='.$id);
    $escomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= '.$id);

    if ($escomun == '') {

        if (($padre <= 2) || ($padre == 2821) || ($padre == 2820)) {
            return false;
        } else {
            return escomunrec($padre);
        }

    } else {
        return true;
    }

}

function ExistePathCategory2($producto, $id_cat)
{

    if (($id_cat > 2) && (! escomunrec($id_cat)) && ($id_cat != 2821) && ($id_cat != 2820)) {

        $id_padre = Db::getInstance()->getValue('SELECT id_parent FROM aalv_category WHERE id_category='.$id_cat);
        $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

        if ($existe != '') {
            return ExistePathCategory2($producto, (int) $id_padre);
        } else {
            return false;
        }
    } else {
        return true;
    }

}

$stdout = fopen(dirname(__FILE__).'/borradopatherroneo.txt', 'a');

$products = Db::getInstance()->ExecuteS('select id_product from aalv_product_import order by 1');

foreach ($products as $productitem) {

    $idproducto = $productitem['id_product'];
    $rows = Db::getInstance()->ExecuteS('select * from aalv_category_product where id_product='.$idproducto);
    foreach ($rows as $row) {
        $id_categoryps = $row['id_category'];
        if (! ExistePathCategory2($idproducto, $id_categoryps)) {
            if (! escomunrec($id_categoryps)) {

                fwrite($stdout, 'delete from aalv_category_product where id_category='.$id_categoryps.' and id_product='.$idproducto.';');
                fwrite($stdout, "\n");

                // Db::getInstance()->Execute("delete from aalv_category_product where id_category=".$id_categoryps. " and id_product=".$idproducto);
                // Db::getInstance()->Execute("delete from aalv_category_product_import where id_category=".$id_categoryps. " and id_product=".$idproducto);
            }
        }
    }
}

fclose($stdout);
echo 'acaba';
