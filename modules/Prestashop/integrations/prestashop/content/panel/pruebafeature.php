<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// /*
//  * Esta es la forma OO "oficial" de hacerlo,
//  * AUNQUE $connect_error estaba averiado hasta PHP 5.2.9 y 5.3.0.
//  */
// if ($mysqli->connect_error) {
//     die('Error de Conexión (' . $mysqli->connect_errno . ') '
//             . $mysqli->connect_error);
// }

// $sql     = "select
//                 id_modelo,
//                 categoria,
//                 familia,
//                 subfamilia,
//                 grupo
//             from
//                 producto
//             where
//                 categoria is not null

//             group by id_modelo
//             order by id_modelo desc
//             ";

// $buscar = $mysqli->query($sql);

// /* INFO
// *   11		Categoria
// *   12		Familia
// *   13		Subfamilia
// *   14		Grupo
// */
// $txt = array();

// while ($re = $buscar->fetch_array(MYSQLI_ASSOC)) {
//     $txt[$re['id_modelo']] = [
//         "11" => $re['categoria'],
//         "12" => $re['familia'],
//         "13" => $re['subfamilia'],
//         "14" => $re['grupo']
//     ];
// }

// //var_dump($txt);
// $nn = 0;
// foreach ($txt as $ke => $val) {

//     $buscar_productos = Db::getInstance()->ExecuteS("select id_product,id_modelo from aalv_product_import where id_modelo = ".$ke);

//     foreach ($buscar_productos as $key => $value) {
//         $buscar_feature = Db::getInstance()->ExecuteS("select id_feature_value, id_feature from aalv_feature_product where id_feature in (11,12,13,14) and id_product = ".$value['id_product']);
//         if(count($buscar_feature) != 0){
//             foreach ($buscar_feature as $keyy => $valuee) {
//                 $buscar = Db::getInstance()->ExecuteS("select value from aalv_feature_value_lang where id_feature_value = ".$valuee['id_feature_value']);
//                 if(count($buscar) == 0){
//                     echo "Le falta 5 idioma el id_product: ".$value['id_product']."<br>";
//                     InsertFeature_value_V2($valuee['id_feature_value'],$valuee['id_feature']);
//                     InsertFeature_value_lang($valuee['id_feature_value'],$val[$valuee['id_feature']]);
//                 }elseif(count($buscar) != 5){
//                     echo "Le falta 1 idioma el id_product: ".$value['id_product']."<br>";
//                 }
//             }
//             if(count($buscar_feature) != 4){
//                 echo count($buscar_feature)." - FALTAN FEATURE EN ID_PRODUCTO: ".$value['id_product']."<br>";
//                 BuscarAgregar($value['id_product'],$val);
//             }
//             if(count($buscar_feature) == 4){
//                 $datos_comparar = Db::getInstance()->ExecuteS(" SELECT
//                                                                     fvl.id_feature_value,
//                                                                     fvl.value AS feature_value
//                                                                 FROM
//                                                                     aalv_feature_product fp
//                                                                         LEFT JOIN aalv_feature_lang fl ON fp.id_feature = fl.id_feature AND fl.id_lang = 1
//                                                                         LEFT JOIN aalv_feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = 1
//                                                                 WHERE
//                                                                     fp.id_feature in (11,12,13,14)
//                                                                     and fp.id_product = ".$value['id_product']);

//                 $elementosRestantes = array();
//                 foreach ($datos_comparar as $subArray) {
//                     $valor = $subArray["feature_value"];
//                     $encontrado = false;

//                     foreach ($val as $indice => $valor2) {
//                         if ($valor2 == $valor) {
//                             $encontrado = true;
//                             break;
//                         }
//                     }

//                     if (!$encontrado) {
//                         // if($nn == 0){
//                         //     UpdateFeature_value_lang($subArray["id_feature_value"]);
//                         //     $nn++;
//                         // }
//                         //echo "id_feature_value: ".$subArray["id_feature_value"]."<br>";
//                         //echo "Valor: ".$valor."<br>";
//                         $elementosRestantes[$indice] = $valor;
//                     }
//                 }
//                 if(count($elementosRestantes) != 0){
//                     echo "id producto: ".$value['id_product']." => ID modelo Web ".$value['id_modelo']."<br>";
//                     var_dump($elementosRestantes);
//                     echo "<br><br>";
//                     $nn++;
//                 }
//             }
//         }else{
//             echo "Revisar este id_producto: ".$value['id_product']."<br>";
//             BuscarAgregar($value['id_product'],$val);
//         }

//     }
// }
// echo "<hr><br>".$nn;

// function InsertFeature_value($id_feature)
// {
//     Db::getInstance()->Execute("insert into aalv_feature_value (id_feature,custom) VALUE (".$id_feature.",1)");
//     return Db::getInstance()->Insert_ID();
// }

// function InsertFeature_value_V2($id_feature_value,$id_feature)
// {
//     Db::getInstance()->Execute("insert into aalv_feature_value (id_feature_value,id_feature,custom) VALUE (".$id_feature_value.",".$id_feature.",1)");
// }

// function InsertFeature_product($id_feature,$id_product,$id_feature_value)
// {
//     Db::getInstance()->Execute("insert into aalv_feature_product
//                             (id_feature,id_product,id_feature_value)
//                             VALUE
//                             (".$id_feature.",".$id_product.",".$id_feature_value.")");
// }

// function InsertFeature_value_lang($id_feature_value,$value)
// {
//     $ssql = "INSERT INTO aalv_feature_value_lang (id_feature_value,id_lang,value) VALUES ";
//     for ($i=1; $i <6 ; $i++) {
//         $ssql .= "(".$id_feature_value.",".$i.",".$value."),";
//     }
//     // Obtener la longitud del string
//     $longitud = strlen($ssql);

//     // Obtener el último parámetro
//     $ultimoParametro = substr($ssql, $longitud - 1);

//     if($ultimoParametro == ','){
//         $ssql      = substr($ssql, 0, -1);
//     }
//     $ssql .= ";";
//     Db::getInstance()->ExecuteS($ssql);
// }

// function UpdateFeature_value_lang($id_feature_value,$value)
// {
//     Db::getInstance()->Execute("update aalv_feature_value_lang set value = ".$value." where id_feature_value = ".$id_feature_value);
// }

// function BuscarAgregar($id_product,$vall)
// {
//     $buscar_feat = Db::getInstance()->ExecuteS("SELECT
//                                                     fl.id_feature,
//                                                     fl.name AS feature_name,
//                                                     fvl.value AS feature_value
//                                                 FROM
//                                                     aalv_feature_lang fl
//                                                     LEFT JOIN aalv_feature_product fp ON fp.id_feature = fl.id_feature AND fp.id_product = ".$id_product."
//                                                     LEFT JOIN aalv_feature_value_lang fvl ON fp.id_feature_value = fvl.id_feature_value AND fvl.id_lang = 1
//                                                 WHERE
//                                                     fp.id_feature IS NULL
//                                                     AND fl.id_lang = 1
//                                                 order by fl.id_feature asc");
//     foreach ($buscar_feat as $keyy => $valuue) {
//         switch ($valuue['id_feature']) {
//             //Categoria
//             case '11':
//                 $idnewsp = InsertFeature_value($valuue['id_feature']);
//                 InsertFeature_product($valuue['id_feature'],$id_product,$idnewsp);
//                 InsertFeature_value_lang($idnewsp,$vall[$valuue['id_feature']]);
//                 break;

//             //Familia
//             case '12':
//                 $idnewsp = InsertFeature_value($valuue['id_feature']);
//                 InsertFeature_product($valuue['id_feature'],$id_product,$idnewsp);
//                 InsertFeature_value_lang($idnewsp,$vall[$valuue['id_feature']]);
//                 break;

//             //Subfamilia
//             case '13':
//                 $idnewsp = InsertFeature_value($valuue['id_feature']);
//                 InsertFeature_product($valuue['id_feature'],$id_product,$idnewsp);
//                 InsertFeature_value_lang($idnewsp,$vall[$valuue['id_feature']]);
//                 break;

//             //Grupo
//             case '14':
//                 $idnewsp = InsertFeature_value($valuue['id_feature']);
//                 InsertFeature_product($valuue['id_feature'],$id_product,$idnewsp);
//                 InsertFeature_value_lang($idnewsp,$vall[$valuue['id_feature']]);
//                 break;
//         }
//     }
// }
