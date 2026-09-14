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

// $modelos = [
// "100056401"
// ];

// foreach ($modelos as $value) {

//     $buscar = Db::getInstance()->ExecuteS("SELECT * FROM aalv_product_import where id_modelo = ".$value);
//     // var_dump($buscar);die();
//     if(count($buscar) == 0){
//         echo $value."<br>";
//     }
// }

$reseteo = '';

$revisar = ',63775,63776,63778,63777,63779,62956,63787,63786,63785,63784,63783,63782,63781,63780,63024,62420,62204,61466,61106,61094,60942,60923,60917,57052,55440,55344,54594,54132,54128,52876,52744,52710,52703,51616,47272
,46198,46197,46196,46192,46188,46185,46173,46171,46169,46167,46166,46165,46164,46157,46156,46141,45552,45059,44869,44818,44815,44814,44811,44810,44802,44801,44799,44792,44689,44600,44464,44409,44390,44375,43551,43522,54050,52088
,43321,43271,43266,43218,41796,63788';

$productos_a_excliur = '56764,55050,57934,57946,57950,57952,57954,61373,61569,52930,52938,61789,61863,62667,63543';

$buscar = Db::getInstance()->ExecuteS('
    SELECT
        asp.id_product,
        asp.id_product_attribute
    FROM
        aalv_product_attribute asp
        LEFT JOIN aalv_specific_price apa ON apa.id_product_attribute = asp.id_product_attribute
    WHERE
        apa.id_product_attribute IS NULL
        and asp.id_product_attribute != 0
        and asp.id_product not in ('.$reseteo.$productos_a_excliur.$revisar.')
    ORDER BY id_product DESC ');

// foreach ($buscar as $value) {
// echo $value['id_product']. ' - ' .$value['id_product_attribute'].'<br>';
// $cantidad = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price WHERE id_product = '.$value['id_product'].' AND id_product_attribute = 0');
// if(count($cantidad) == 2){
//     $sql = "UPDATE PS_specific_price set id_product_attribute = ".$value['id_product_attribute']." WHERE id_specific_price IN (";
//     foreach ($cantidad as $key => $val) {
//         if($key == 0){
//             $concat = $val['id_specific_price'];
//         }else{
//             $concat .= ','.$val['id_specific_price'];
//         }
//     }
//     $sql .= $concat.');';

//     $precio = Db::getInstance()->ExecuteS("SELECT id_tarifa_cabecera FROM aalv_specific_price_import aspi WHERE id_specific_price IN (".$concat.")");
//     $otrosql = "UPDATE PS_tarifa_cabecera_import SET id_attribute = ".$value['id_product_attribute']." WHERE id_tarifa_cabecera IN (";
//     foreach ($precio as $ke => $vl) {
//         if($ke == 0){
//             $concat2 = $vl['id_tarifa_cabecera'];
//         }else{
//             $concat2 .= ','.$vl['id_tarifa_cabecera'];
//         }
//     }
//     $otrosql .= $concat2.');';
//     echo $sql."<br>";
//     echo $otrosql."<br>";
// }
// }
