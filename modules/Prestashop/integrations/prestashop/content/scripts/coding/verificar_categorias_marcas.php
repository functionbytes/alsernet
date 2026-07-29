<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../config/config.inc.php';
// include _PS_ADMIN_DIR_ . '/../init.php';

$datos = Db::getInstance()->getValue("select value from aalv_manufacturer_category_exclude");

$marcas = Db::getInstance()->getValue("select GROUP_CONCAT(id_parent_manufacturer) from aalv_manufacturer_category_related");

$datosv2 = Db::getInstance()->executeS("select
                                            amcr.id_category,
                                            acl.name AS name_category,
                                            am.name AS name_marca,
                                            amcr.id_category_manufacturer,
                                            amc.id_category AS id_marca
                                        from
                                            aalv_manufacturer_category_related amcr
                                            left join aalv_category_lang acl on acl.id_category = amcr.id_category
                                            left join aalv_manufacturer am on am.id_manufacturer = amcr.id_manufacturer
                                            left join aalv_manufacturer_category amc on amc.id_manufacturer = amcr.id_manufacturer
                                        WHERE
                                            amcr.id_category NOT IN (".$datos.")
                                            AND acl.id_lang = 1");

// $link = new Link();
echo "id_category;name_category;name_marca<br>";
foreach ($datosv2 as $value) {
    $exp = explode(',',$value['id_category']);
    if(count($exp) == 1){
        // for ($i=1; $i < 6; $i++) {
        //     # code...
        //     $url = $link->getCategoryLink($value['id_category_manufacturer'], NULL, $i);
        //     $url_marca = $link->getCategoryLink($value['id_marca'], NULL, $i);
        // }
        echo $value['id_category_manufacturer'].' => '.$value['name_category'].' => '.$value['name_marca']."<br>";
        echo "<br>";
    }
    else{
        foreach ($exp as $val) {
            $valor = Db::getInstance()->getValue("select value from aalv_manufacturer_category_exclude where value LIKE '%,".$val.",%'");
            if(!$valor){
                echo $val.' => '.$value['name_category'].' => '.$value['name_marca']."<br>";
            }
        }
    }
}

// $id_category = [
//     99999,
//     100020
// ]; // El ID de la categoría que deseas eliminar

// foreach ($id_category as $value) {
//     # code...
//     $category = new Category($value);

//     if (Validate::isLoadedObject($category)) {
//         $category->delete();
//         echo "Categoría eliminada exitosamente. => ".$value."<br>";;
//     } else {
//         echo "Error: No se pudo cargar la categoría.<br>";
//     }
//     // dump($value);
//     // die();
// }



// for ($i=1; $i < 6; $i++) {
//     # code...
//     $url = $link->getCategoryLink(85856, NULL, $i);
//     // $url_marca = $link->getCategoryLink(85853, NULL, $i);
//     echo ""eliminar" => "".$url." <br>";",
// }

// dump($url);
