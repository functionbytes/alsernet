<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$catdep = new Category(7);
$categories = $catdep->getAllChildren();
$listids = [];
$listids[] = (int) $cat;
foreach ($categories as $category) {
    $listids[] = (int) $category->id;
}

$sql = "select id_manufacturer, (select b.name from aalv_manufacturer b where b.id_manufacturer=a.id_manufacturer) 'name', a.id_product from aalv_product a where a.id_product in (SELECT id_product FROM aalv_category_product WHERE id_category in (".implode(',', $listids).")) and a.visibility='both' and a.active=1 and a.id_product in (SELECT id_product FROM aalv_combinacionunica_import union select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import)) and a.id_manufacturer=218 order by name";
$manufacturers = Db::getInstance()->ExecuteS($sql);

dump($manufacturers);
