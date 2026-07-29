<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function NumProductos($idmarca, $deporte)
{

    $deportecategoria = Db::getInstance()->getValue('SELECT id_origen FROM aalv_category_import WHERE id_cat='.$deporte);

    $sql = 'SELECT  p.id_product FROM '._DB_PREFIX_.'product p USE INDEX (product_manufacturer) '.Shop::addSqlAssociation('product', 'p').' WHERE p.id_manufacturer = '.$idmarca." AND product_shop.visibility NOT IN ('none') AND product_shop.active = 1";

    $rows = Db::getInstance()->ExecuteS($sql);

    $i = 0;

    foreach ($rows as $row) {

        $cats = Product::getProductCategories($row['id_product']);

        foreach ($cats as $catid) {

            $categ = new Category($catid);

            if ($categ->sport == $deportecategoria) {
                $i = $i + 1;
                break;
            }

        }

    }

    return $i;

}

$rows = Db::getInstance()->ExecuteS('SELECT * FROM aalv_manufacturer_deporte');

foreach ($rows as $row) {

    $numprod = NumProductos($row['id_manufacturer'], $row['id_category_deporte']);

    echo 'Num prod '.$numprod;
    if ($numprod > 0) {
        Db::getInstance()->Execute('UPDATE aalv_manufacturer_deporte SET tiene_productos=1 WHERE id_manufacturer='.$row['id_manufacturer'].' and id_category_deporte='.$row['id_category_deporte']);
    } else {
        Db::getInstance()->Execute('UPDATE aalv_manufacturer_deporte SET tiene_productos=0 WHERE id_manufacturer='.$row['id_manufacturer'].' and id_category_deporte='.$row['id_category_deporte']);
    }

}

echo 'Proceso acabado';
