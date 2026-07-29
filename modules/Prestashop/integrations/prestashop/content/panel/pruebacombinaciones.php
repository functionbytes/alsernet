<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// $buscar = Db::getInstance()->ExecuteS("SELECT id_product FROM aalv_product_shop WHERE active = 1 ORDER BY id_product DESC LIMIT 50000,10000");
// // 45.546
// foreach ($buscar as $value) {
//      // Obtiene todas las combinaciones de atributos para el producto dado
//     $productAttributes = Product::getProductAttributesIds($value['id_product']);

//     // Array para almacenar las combinaciones de atributos encontradas
//     $attributeCombinations = array();

//     // Array para almacenar los IDs de product_attribute duplicados
//     $duplicateAttributes = array();

//     // Itera sobre las combinaciones de atributos
//     foreach ($productAttributes as $id_product_attribute) {
//         // Obtiene la combinación de atributos para el product_attribute actual
//         $combination = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
//                         SELECT al.*, a.*
//                         FROM aalv_product_attribute_combination pac
//                         JOIN aalv_attribute a ON (pac.id_attribute = a.id_attribute)
//                         JOIN aalv_attribute_lang al ON (pac.id_attribute = al.id_attribute AND al.id_lang=1)
//                         WHERE pac.id_product_attribute='.$id_product_attribute);
//         // $combination = ProductAttribute::getAttributeCombinations($id_product_attribute);

//         $combinationString = json_encode($combination);
//         // Verifica si la combinación ya existe en el array
//         if (in_array($combinationString, $attributeCombinations)) {
//             // Si la combinación ya existe, agrega el ID de product_attribute a la lista de duplicados
//             $duplicateAttributes[] = $id_product_attribute;
//         } else {
//             // Si la combinación no existe, agrega la combinación al array
//             $attributeCombinations[] = $combination;
//         }
//     }

//     // Muestra los IDs de product_attribute duplicados, si los hay
//     if (!empty($duplicateAttributes)) {
//         echo "Id_product : ".$value['id_product']."<br>";
//         echo "Los siguientes IDs de product_attribute tienen combinaciones duplicadas: " . implode(', ', $duplicateAttributes);
//         echo "<hr>";
//     }
// }

// $buscar = Db::getInstance()->ExecuteS(" SELECT
//                                             p.id_product,
//                                             COUNT(pa.reference) AS cantidad_referencias
//                                         FROM
//                                             aalv_product p
//                                             LEFT JOIN aalv_product_attribute pa ON p.id_product = pa.id_product
//                                         WHERE
//                                             p.active = true
//                                             AND p.reference = ''
//                                         GROUP BY p.id_product
//                                         ORDER BY p.id_product DESC");
// $nn = 0;
// foreach ($buscar as $value) {
//     // $feature = Db::getInstance()->ExecuteS("SELECT id_feature_value FROM aalv_feature_product afp WHERE id_feature = 2 AND id_product = ".$value['id_product']);
//     // if($feature[0]['id_feature_value'] != 5){
//         $referencia = Db::getInstance()->ExecuteS(" SELECT
//                                                         COUNT(atsad.id_shop) AS cantidad
//                                                     FROM
//                                                         aalv_product_attribute apa
//                                                         INNER JOIN aalv_tot_switch_attribute_disabled atsad ON atsad.id_product_attribute = apa.id_product_attribute
//                                                     WHERE
//                                                         id_product = ".$value['id_product']);
//         if($referencia[0]['cantidad'] == $value['cantidad_referencias']){
//             echo $value['id_product']."<br>";
//             $nn++;
//         }
//     // }

// }
// echo "cantidad: ".$nn;
