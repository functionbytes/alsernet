<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

// include ('/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php');
// $http = false;
//  $entities = true;
//   $ignore_port = true;

// $httpHost = '';
//         if (array_key_exists('HTTP_HOST', $_SERVER)) {
//             $httpHost = $_SERVER['HTTP_HOST'];
//         }

//         $host = (isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $httpHost);
//         if ($ignore_port && $pos = strpos($host, ':')) {
//             $host = substr($host, 0, $pos);
//         }
//         if ($entities) {
//             $host = htmlspecialchars($host, ENT_COMPAT, 'UTF-8');
//         }
//         if ($http) {
//             $host = (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $host;
//         }
// //echo $_SERVER['HTTP_X_FORWARDED_HOST']."/n";
// echo $_SERVER['HTTP_HOST']."\n";
// echo Configuration::get('PS_SSL_ENABLED')."\n";
// echo $_SERVER['HTTP_HOST']."\n";
// echo $host."\n";

// // die();
// $dbcon = connectBD();

// $sql = Db::getInstance()->ExecuteS("select 0 as producto ,id_articulo, unidades_oferta, etiqueta, estado_gestion,activo, es_segunda_mano, externo_disponibilidad, codigo_proveedor, precio_costo_proveedor, tarifa_proveedor, es_arma, es_arma_fogueo, es_cartucho, categoria, familia, subfamilia, grupo  from aalv_combinaciones_import aci
// UNION
// select 1 as producto,id_articulo, unidades_oferta, etiqueta, estado_gestion,activo, es_segunda_mano, externo_disponibilidad, codigo_proveedor, precio_costo_proveedor, tarifa_proveedor, es_arma, es_arma_fogueo, es_cartucho, categoria, familia, subfamilia, grupo from aalv_combinacionunica_import aci

// ");

// $nn = 0;
// foreach ($sql as $value) {
//     # code...

//     $sql_antigua = "SELECT
//                         *
//                     FROM
//                         producto stoc
//                     WHERE
//                         stoc.idarticulo = ".$value['id_articulo'];

//     $data = mysqli_query($dbcon, $sql_antigua);
//     $re = mysqli_fetch_array($data);

//     // Arreglo para almacenar las diferencias
//     $diferencias = [];

//     // Comparar cada valor con el valor de la base de datos
//     if ($re['unidades_oferta'] != $value['unidades_oferta']) {
//         $diferencias[] = "unidades_oferta";
//     }
//     if ($re['etiqueta'] != $value['etiqueta']) {
//         $diferencias[] = "etiqueta";
//     }
//     if ($re['estado_gestion'] != $value['estado_gestion']) {
//         $diferencias[] = "estado_gestion";
//     }
//     if ($re['activo'] != $value['activo']) {
//         $diferencias[] = "activo";
//     }
//     if ($re['es_segunda_mano'] != $value['es_segunda_mano']) {
//         $diferencias[] = "es_segunda_mano";
//     }
//     if ($re['externo_disponibilidad'] != $value['externo_disponibilidad']) {
//         $diferencias[] = "externo_disponibilidad";
//     }
//     if ($re['codigo_proveedor'] != $value['codigo_proveedor']) {
//         $diferencias[] = "codigo_proveedor";
//     }
//     if ($re['es_arma'] != $value['es_arma']) {
//         $diferencias[] = "es_arma";
//     }
//     if ($re['es_arma_fogueo'] != $value['es_arma_fogueo']) {
//         $diferencias[] = "es_arma_fogueo";
//     }
//     if ($re['es_cartucho'] != $value['es_cartucho']) {
//         $diferencias[] = "es_cartucho";
//     }
//     if ($re['categoria'] != $value['categoria']) {
//         $diferencias[] = "categoria";
//     }
//     if ($re['familia'] != $value['familia']) {
//         $diferencias[] = "familia";
//     }
//     if ($re['subfamilia'] != $value['subfamilia']) {
//         $diferencias[] = "subfamilia";
//     }
//     if ($re['grupo'] != $value['grupo']) {
//         $diferencias[] = "grupo";
//     }

//     // Si hay diferencias, mostrar cuáles son
//     if (count($diferencias) > 0) {
//         echo "\nPara el id_articulo ".$value['id_articulo']." los siguientes valores no coinciden: " . implode(", ", $diferencias) . "\n";
//     } else {
//         echo ".";
//         $nn++;
//         if($nn == 100){
//             echo "\n";
//             $nn = 0;
//         }
//         // echo "Para el id_articulo ".$value['id_articulo']." todos los valores coinciden.<br>";
//     }

// }

// $sql = Db::getInstance()->ExecuteS("SELECT sp.id_product, sp.id_product_attribute, sp.id_country, COUNT(*) AS total_duplicates
// FROM aalv_specific_price sp
// WHERE (`from` <= NOW() OR `from` = '0000-00-00 00:00:00')
//   AND (`to` >= NOW() OR `to` = '0000-00-00 00:00:00')
// GROUP BY sp.id_product, sp.id_product_attribute, sp.id_country
// HAVING total_duplicates > 1;");

// $dbcon = connectBD();
// $fn = 0;
// foreach ($sql as $value) {
//     # code...

//     if($value['id_country'] == 15){

//         $sqlv2 = Db::getInstance()->ExecuteS("select aspi.id_tarifa_cabecera from aalv_specific_price asp
//     left join aalv_specific_price_import aspi on aspi.id_specific_price = asp.id_specific_price
//     where asp.id_product = ".$value['id_product']." and asp.id_product_attribute = ".$value['id_product_attribute']." and asp.id_country = ".$value['id_country']);
//         foreach ($sqlv2 as $valuev2) {
//             # code...
//             $sql_antigua = "select * from tarifa_cabecera where idtarifa_cabecera = ".$valuev2['id_tarifa_cabecera'];

//             $data = mysqli_query($dbcon, $sql_antigua);

//             if ($data) {
//                 $re = mysqli_fetch_array($data);
//                 if(is_null($re)){
//                     // buscamos id_specific_price en tabla aalv_specific_price_import
//                     $buscar_specific_price = Db::getInstance()->ExecuteS("select id_specific_price from aalv_specific_price_import aspi where id_tarifa_cabecera = ".$valuev2['id_tarifa_cabecera']);
//                     if(count($buscar_specific_price) > 1){
//                         foreach ($buscar_specific_price as $value_key) {
//                             # code...
//                             $datos_existe = Db::getInstance()->ExecuteS("select * from aalv_specific_price asp where id_specific_price = ".$value_key['id_specific_price']);
//                             if(count($datos_existe) == 0){
//                                 // dump($value_key);
//                                 Db::getInstance()->Execute("DELETE from aalv_specific_price_import where id_tarifa_cabecera = ".$valuev2['id_tarifa_cabecera']." and id_specific_price in (".$value_key['id_specific_price'].")");
//                             }
//                         }
//                         dump("REVISAR PORQUE EXISTE mas de 2 specific_price_import");
//                         dump($valuev2['id_tarifa_cabecera']);
//                         // die();
//                     }elseif(count($buscar_specific_price) == 1){
//                         // Buscamos id_specific_price en tabla aalv_specific_price
//                         $buscar_specific_price = Db::getInstance()->ExecuteS("select * from aalv_specific_price asp where id_specific_price = ".$buscar_specific_price[0]['id_specific_price']);
//                         if(count($buscar_specific_price) == 1){
//                             Db::getInstance()->Execute("DELETE FROM aalv_specific_price WHERE id_specific_price = ".$buscar_specific_price[0]['id_specific_price']);
//                             Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera = ".$valuev2['id_tarifa_cabecera']);
//                             Db::getInstance()->Execute("DELETE FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera = ".$valuev2['id_tarifa_cabecera']);
//                             dump('('.$fn.') ELIMINADO => '.$valuev2['id_tarifa_cabecera']);
//                         }elseif(count($buscar_specific_price) > 1){
//                             dump('revisar porque existe dos precios');die();
//                         }else{
//                             dump('revisar que pasa cuando no existe');die();
//                         }
//                     }else{
//                         dump("REVISAR PORQUE NO EXISTE specific_price_import");
//                         dump($valuev2['id_tarifa_cabecera']);
//                         die();
//                     }

//                     $fn++;

//                 }
//             }
//         }
//     }

//     if($fn == 1000){
//         dump('fin');
//         die();
//     }
// }

// $modelos = Db::getInstance()->ExecuteS("select id_modelo from aalv_product_import api
// left join aalv_product ap on ap.id_product = api.id_product
// where ap.active = 1 and ap.id_category_default Between 2 and 11");
// $valores = [];
// foreach ($modelos as $key => $value) {
//     # code...

//     // buscamos la navegacion de esos modelos
//     $sql_antigua = "select * from perfiles_nav where id_modelo = ".$value['id_modelo'];
//     $data = mysqli_query($dbcon, $sql_antigua);
//     while ($re = mysqli_fetch_array($data)) {
//         # code...

//         // Buscamos en ps si existe, sino lo tenemos que agregar
//         $categoria = Db::getInstance()->ExecuteS("SELECT * FROM aalv_category_import WHERE id_origen = ".$re['id_valor']);
//         if(count($categoria) == 0){
//             if (!in_array($re['id_valor'], $valores)) {
//                 $valores[] = $re['id_valor'];
//                 // if($re['principal'] == 0){
//                     dump($re['id_valor']. ' => '.$value['id_modelo']);

//                 // }
//             }
//         }
//     }
// }

// $limit = "0,100000";

// $tarifas = Db::getInstance()->ExecuteS('select * from aalv_integracion_cambios aic where tabla in ("v_sinc_tarifa_cabecera","v_sinc_tarifa_linea") and processed = 1 and data != "null" and tipo in (1,2) and fecha_confirmacion != "0000-00-00 00:00:00"
// order by aic.fecha_confirmacion DESC limit '.$limit);
// $array = [];
// $ff = 0;
// $suma = 0;
// foreach ($tarifas as $key => $value) {
//     # code...
//     echo ".";
//     $ff++;
//     $data = json_decode($value['data'], true);
//     if($value['tabla'] == 'v_sinc_tarifa_cabecera'){

//         $tarifa_cabecera = Db::getInstance()->ExecuteS("select * from aalv_tarifa_cabecera_import atci where id_tarifa_cabecera = ".$value['fila']);
//         if(count($tarifa_cabecera) == 0){
//             if($data['estado']){
//                 $estado_gestion = Db::getInstance()->ExecuteS("select aci.estado_gestion from aalv_combinaciones_import aci where aci.id_articulo = ".$data['idarticulo']."
//                 union
//                 select acis.estado_gestion from aalv_combinacionunica_import acis where acis.id_articulo = ".$data['idarticulo']);

//                 if($estado_gestion[0]['estado_gestion'] != 0
//                     // && $value['fila'] != 102815252
//                     // && $value['fila'] != 102815251
//                     // && $value['fila'] != 100551135
//                     // && $value['fila'] != 100551136
//                     // && $value['fila'] != 100544741
//                     // && $value['fila'] != 100544742
//                     // && $value['fila'] != 100544738
//                     // && $value['fila'] != 100544739
//                     // && $value['fila'] != 100544735
//                     // && $value['fila'] != 101952016
//                     // && $value['fila'] != 101952017
//                     // && $value['fila'] != 101902719
//                     // && $value['fila'] != 102400941
//                     // && $value['fila'] != 102400942
//                     // && $value['fila'] != 101192568
//                     // && $value['fila'] != 101192567
//                     // && $value['fila'] != 103540213
//                     // && $value['fila'] != 103551106
//                     // && $value['fila'] != 100486040
//                     // && $value['fila'] != 100486039
//                     ){
//                     Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$value['id']);
//                     dump("tabla => v_sinc_tarifa_cabecera; fila => ".$value['fila']);

//                 }
//             }

//         }

//         $specific_price_import_cabecera = Db::getInstance()->ExecuteS("select * from aalv_specific_price_import aspi where id_tarifa_cabecera = ".$value['fila']);
//         if(count($specific_price_import_cabecera) == 0){
//             if($data['estado']){
//                 $fecha_str = explode("T", $data['finicio']);
//                 if($fecha_str[0] == '2025-06-02'){
//                     dump('aca => '.$value['fila']);
//                     $busca_linea = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios aic where data like '%".$value['fila']."%' and tabla = 'v_sinc_tarifa_linea' order by aic.fecha_confirmacion desc");
//                     $nn = 0;
//                     foreach ($busca_linea as $val) {
//                         # code...
//                         $datav2 = json_decode($val['data'], true);
//                         if($datav2['idtarifa_cabecera'] == $value['fila']){
//                             $nn++;
//                             if($nn > 1){
//                                 dump('Buscar datos mas de 1');
//                                 dump($data);die();
//                             }
//                             $array[] = $datav2['idtarifa_linea'];
//                             Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$val['id']);
//                             // dump($val);
//                         }
//                     }
//                 }elseif($value['fila'] != 105087976
//                         && $value['fila'] != 104831061){
//                     dump($value);
//                     dump($data);
//                     dump("tabla => specific_price_import");
//                     dump("fila => ".$value['fila']);
//                     dump("------------");die();
//                 }

//             }
//         }
//     }

//     if($value['tabla'] == 'v_sinc_tarifa_linea'){
//         if($data['estado']){
//             if (in_array($value['fila'], $array)) {
//                 continue;
//             }
//             $specific_price_import_linea = Db::getInstance()->ExecuteS("select * from aalv_specific_price_import aspi where id_tarifa_linea = ".$value['fila']);
//             if(count($specific_price_import_linea) == 0){
//                 dump("tabla => specific_price_import_linea");
//                 dump("fila => ".$value['fila']);
//                 dump("------------");die();
//             }
//         }
//     }

//     if($ff == 100){
//         $ff = 0;
//         $suma = $suma + 100;
//         echo $suma.'-100000';
//         echo "\n";
//     }

// }

// $tarifas = Db::getInstance()->ExecuteS("SELECT id
// FROM aalv_integracion_cambios where tabla='v_sinc_tarifa_cabecera' and processed = 1 and tipo <> '3' and data != 'null' and fecha_confirmacion > '2025-03-01'
// and SUBSTRING_INDEX( SUBSTRING_INDEX(data, '\"idtarifa_cabecera\":', -1), ',', 1 ) not in (select id_tarifa_cabecera from aalv_tarifa_cabecera_import atci) and
// TRIM(BOTH ' }' FROM SUBSTRING_INDEX(SUBSTRING_INDEX(data, '\"estado\":', -1), ',', 1)) = 'true'");
// foreach ($tarifas as $value) {
//     # code...
//     $buscar = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios where id = ".$value['id']);
//     $data = json_decode($buscar[0]['data'], true);
//     $tarifa_cabecera = Db::getInstance()->ExecuteS("select * from aalv_tarifa_cabecera_import atci where id_tarifa_cabecera = ".$buscar[0]['fila']);
//     if(count($tarifa_cabecera) == 0){
//         if($data['estado']){
//             $estado_gestion = Db::getInstance()->ExecuteS("select aci.estado_gestion from aalv_combinaciones_import aci where aci.id_articulo = ".$data['idarticulo']."
//                 union
//                 select acis.estado_gestion from aalv_combinacionunica_import acis where acis.id_articulo = ".$data['idarticulo']);

//             if($estado_gestion[0]['estado_gestion'] != 0){
//                 Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$buscar[0]['id']);
//                 dump("tabla => v_sinc_tarifa_cabecera; fila => ".$buscar[0]['fila']);
//             }
//         }
//     }

//     $specific_price_import_cabecera = Db::getInstance()->ExecuteS("select * from aalv_specific_price_import aspi where id_tarifa_cabecera = ".$buscar[0]['fila']);
//     if(count($specific_price_import_cabecera) == 0){
//         if($data['estado']){
//             $fecha_str = explode("T", $data['finicio']);
//             if($fecha_str[0] == '2025-06-02'){
//                 dump('aca => '.$buscar[0]['fila']);
//                 $busca_linea = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios aic where data like '%".$buscar[0]['fila']."%' and tabla = 'v_sinc_tarifa_linea' order by aic.fecha_confirmacion desc");
//                 $nn = 0;
//                 foreach ($busca_linea as $val) {
//                     # code...
//                     $datav2 = json_decode($val['data'], true);
//                     if($datav2['idtarifa_cabecera'] == $buscar[0]['fila']){
//                         $nn++;
//                         if($nn > 1){
//                             dump('Buscar datos mas de 1');
//                             dump($data);die();
//                         }
//                         Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$val['id']);
//                     }
//                 }
//             }else{
//                 dump("tarifa_linea => fila => ".$buscar[0]['fila']);
//                 $busca_lineav2 = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios aic where data like '%".$buscar[0]['fila']."%' and tabla = 'v_sinc_tarifa_linea' order by aic.fecha_confirmacion desc");
//                 $nn = 0;
//                 foreach ($busca_lineav2 as $vale) {
//                     # code...
//                     $datav3 = json_decode($vale['data'], true);
//                     if($datav3['idtarifa_cabecera'] == $buscar[0]['fila']){
//                         $nn++;
//                         if($nn > 1){
//                             dump('Buscar datos mas de 1');
//                             dump($data);die();
//                         }
//                         Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$vale['id']);
//                     }
//                 }
//             }
//         }
//     }
// }

// $datos = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios aic where tabla = 'v_sinc_tarifa_cabecera' and id > 17806939 and `data` like '%\"finicio\":\"2025-06-02%' and tipo = 1 and processed = 1
// ORDER BY
//                                         CASE
//                                             WHEN tabla LIKE '%v_sinc_w_modelo%' THEN 1
//                                             WHEN tabla LIKE '%v_sinc_w_producto%' THEN 2
//                                             WHEN tabla LIKE '%v_sinc_w_valores_prod%' THEN 3
//                                             WHEN tabla LIKE '%v_sinc_w_valores_nav%' THEN 4
//                                             ELSE 5
//                                         END,
//                                         id
// ");

// foreach ($datos as $key => $value) {
//     # code...
//     $linea = Db::getInstance()->ExecuteS("select * from aalv_integracion_cambios aic where data like '%\"idtarifa_cabecera\":".$value['fila']."%' and tipo = 1 and tabla = 'v_sinc_tarifa_linea'");
//     if(count($linea) > 1){
//         dump('revisar');
//         foreach ($linea as $va) {
//             # code...
//             dump($va['data']);
//             dump('');
//         }
//         dump('');
//         die();
//     }

//     if(count($linea) == 0){
//         continue;
//     }

//     $data = json_decode($value['data'], true);
//     if(!$data['estado']){
//         continue;
//     }
//     //Revisamos si existe la tarifa cabecera
//     $tarifa_cabecera = Db::getInstance()->ExecuteS("SELECT * FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$value['fila']);
//     if(count($tarifa_cabecera) == 0){
//         dump('CABECERA');
//         dump($tarifa_cabecera);
//         dump($value);

//         Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$value['id']);
//         die();
//     }

//     // Revisamos la tarifa linea
//     $test1 = Db::getInstance()->ExecuteS("SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea=".$linea[0]['fila']);
//     if(count($test1) == 0){
//         dump('TEST 1');
//         // Ya llega aca es que no esta creado nada
//         Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$linea[0]['id']);
//         continue;
//     }

//     // ahora revisamos si el specific_price existe
//     $test2 = Db::getInstance()->ExecuteS("select * from aalv_specific_price asp where id_specific_price =".$test1[0]['id_specific_price']);
//     if(count($test2) == 0){
//         dump('TEST 2');
//         // dump($test2);
//         // dump($value);
//         // dump($linea);
//         // dump("update aalv_integracion_cambios set processed = 0 where id = ".$linea[0]['id']);
//         // Ya llega aca es porque no existe precio
//         Db::getInstance()->Execute("DELETE FROM aalv_specific_price_import WHERE id_specific_price=" . $test1[0]['id_specific_price']);
//         Db::getInstance()->Execute("update aalv_integracion_cambios set processed = 0 where id = ".$linea[0]['id']);
//         // die();
//         continue;
//     }

//     if($tarifa_cabecera[0]['id_product'] != $test2[0]['id_product'] && $tarifa_cabecera[0]['id_attribute'] != $test2[0]['id_product_attribute']){

//         dump($tarifa_cabecera);
//         dump($test2);
//         die();
//     }

// }

// $sql = Db::getInstance()->ExecuteS("SELECT sp.id_product, sp.id_product_attribute, p.active
// FROM aalv_product_attribute sp
// INNER JOIN (
//   -- Subconsulta: solo los id_product con una sola combinación
//   SELECT id_product
//   FROM aalv_product_attribute
//   GROUP BY id_product
//   HAVING COUNT(*) = 1
// ) AS single_sp USING (id_product)
// INNER JOIN aalv_product p USING (id_product)
// WHERE p.active = 0 order by sp.id_product desc");

// foreach ($sql as $value) {
//     # code...
//     $specific_price = Db::getInstance()->ExecuteS("select * from aalv_specific_price where id_product = ".$value['id_product']." and id_product_attribute = 0");
//     if(count($specific_price) != 0){
//         dump($value['id_product']);
//         // dump($specific_price);
//         // dump("update aalv_specific_price set id_product_attribute = ".$value['id_product_attribute']." where id_product = ".$value['id_product']." and id_product_attribute = 0");
//         // Db::getInstance()->Execute("update aalv_specific_price set id_product_attribute = ".$value['id_product_attribute']." where id_product = ".$value['id_product']." and id_product_attribute = 0");
//         // die();
//     }
// }

// $sql = Db::getInstance()->ExecuteS("SELECT p.id_product, p.active AS active_product, ps.active AS active_shop
// FROM aalv_product p
// INNER JOIN aalv_product_shop ps ON p.id_product = ps.id_product
// WHERE p.active <> ps.active");

// $sql = Db::getInstance()->ExecuteS("SELECT pa.id_product
// FROM aalv_product_attribute AS pa
// INNER JOIN aalv_product AS p
//   ON p.id_product = pa.id_product
// LEFT JOIN aalv_combinaciones_import AS cu
//   ON cu.id_product_attribute = pa.id_product_attribute
// WHERE p.active = 1
// GROUP BY pa.id_product
// HAVING COUNT(*) = SUM(CASE WHEN cu.estado_gestion = 0 THEN 1 ELSE 0 END);");

// $sql = Db::getInstance()->ExecuteS("SELECT p.id_product
// FROM aalv_product AS p
// INNER JOIN aalv_combinacionunica_import AS c
//   ON c.id_product = p.id_product
// WHERE p.active = 1
//   AND c.estado_gestion = 0;");

// foreach ($sql as $value) {
//     # code...
//     // die();
//     dump("id_product => ".$value['id_product']);
//     $product = new Product($value['id_product']);
//     $product->active = false;
//     $product->update();
//     // die();
// }

// $variable = Db::getInstance()->ExecuteS("select aci.id_articulo from aalv_stock_available asa
// inner join aalv_combinaciones_import aci on aci.id_product_attribute = asa.id_product_attribute
// where
// aci.estado_gestion = 1
// and aci.etiqueta not like '%OCULTO WEB%'
// and aci.etiqueta not like '%CSW%'
// and asa.quantity = 0
// and aci.externo_disponibilidad = 1 ");

// $variable = Db::getInstance()->ExecuteS("select aci.id_articulo  from aalv_stock_available asa
// inner join aalv_combinacionunica_import aci on aci.id_product = asa.id_product
// where
// aci.estado_gestion = 1
// and aci.etiqueta not like '%OCULTO WEB%'
// and aci.etiqueta not like '%CSW%'
// and asa.quantity = 0
// and aci.externo_disponibilidad = 1");

// foreach ($variable as $key => $value) {
//     # code...
//     dump($value['id_articulo']);
//     Product::alsernetNewVisibilidad($value['id_articulo']);
// }
