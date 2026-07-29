<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../config/config.inc.php');
include (dirname(__FILE__).'/../init.php');

/*
*   Actualiza los contadores de las Marcas
*/
$lang = 1; //es-ES
$deportes = Category::getHomeCategories(1);

foreach ($deportes as $deporte) {

    $cat = $deporte['id_category'];

    $catdep = new Category($cat);

    $categories = $catdep->getAllChildren();

    $listids = [];

    $listids[] = (int)$cat;
    foreach ($categories as $category) {
        $listids[] = (int)$category->id;
    }

    $sql = "select count(distinct id_manufacturer) from aalv_product where id_product in (SELECT id_product FROM aalv_category_product WHERE id_category in (".implode(",",$listids).")) and active=1 and visibility='both' and id_product in (SELECT id_product FROM aalv_combinacionunica_import union select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import)) and id_manufacturer<>0";


    $nummarcas = Db::getInstance()->getValue($sql);

    Db::getInstance()->ExecuteS("INSERT INTO aalv_alsernet_marcas_categoria (id_category, count_manufacturer) VALUES (".$deporte['id_category'].",".$nummarcas.")
    ON DUPLICATE KEY UPDATE count_manufacturer=".$nummarcas);

}

/*
* Actualiza los contadores de las categorias del Blog
*/

$id_categoria_blog = Db::getInstance()->ExecuteS("SELECT id_category FROM aalv_ybc_blog_category");

foreach ($id_categoria_blog as $value) {
    $num_blog = Db::getInstance()->getValue("SELECT DISTINCT
                                                count(*)
                                            FROM
                                                `aalv_ybc_blog_post` p
                                                INNER JOIN `aalv_ybc_blog_post_shop` ps ON (p.id_post=ps.id_post AND ps.id_shop='1')
                                                LEFT JOIN `aalv_ybc_blog_post_lang` pl ON p.id_post = pl.id_post
                                                LEFT JOIN `aalv_ybc_blog_post_category` pc ON p.id_post = pc.id_post
                                                LEFT JOIN `aalv_customer` c ON (c.id_customer=p.added_by AND p.is_customer=1)
                                                LEFT JOIN `aalv_employee` e ON (e.id_employee=p.added_by AND p.is_customer=0)
                                                LEFT JOIN `aalv_ybc_blog_employee` ybe ON ((ybe.id_employee=c.id_customer AND ybe.is_customer=1) OR (ybe.id_employee=e.id_employee AND ybe.is_customer=0))
                                                LEFT JOIN `aalv_ybc_blog_comment` pcm on (pcm.id_post=p.id_post)
                                            WHERE
                                                (p.enabled=1 OR p.enabled=-1)
                                                AND (ybe.status>=0 OR ybe.status is NULL OR e.id_profile=1)
                                                AND pl.id_lang = 1
                                                AND pc.id_category=".$value['id_category']."
                                                AND p.enabled=1");

    Db::getInstance()->ExecuteS("INSERT INTO aalv_alsernet_blog_categoria (id_category, count_blog) VALUES (".$value['id_category'].",".$num_blog.")
    ON DUPLICATE KEY UPDATE count_blog=".$num_blog);
}