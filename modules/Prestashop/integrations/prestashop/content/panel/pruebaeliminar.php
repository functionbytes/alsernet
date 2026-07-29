<?php

ini_set('max_execution_time', 1760000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');

// $tablas = Db::getInstance()->ExecuteS("SHOW TABLES;");
// $buscar_columna = "id_product";

// $id_producto_a_eliminar = [62093];

// foreach ($id_producto_a_eliminar as $keey => $val) {
//     // $product = new Product($val);
//     // if (Validate::isLoadedObject($product)) {
//     //     // El producto existe, así que procedemos a eliminarlo
//     //     if ($product->delete()) {
//     //         echo "Producto eliminado con éxito.<br><br>";
//     //     } else {
//     //         echo "Error al eliminar el producto.<br><br>";

//     //     }
//     // } else {
//     //     echo "El producto no existe.<br><br>";

//     // }
//     # code...
//     foreach ($tablas as $key => $value) {
//         $columna = Db::getInstance()->ExecuteS("SHOW COLUMNS FROM ".$value['Tables_in_alvarez_db']." LIKE '".$buscar_columna."'");
//         if(count($columna) != 0){
//             Db::getInstance()->ExecuteS("DELETE FROM ".$value['Tables_in_alvarez_db']." WHERE ".$buscar_columna." = ".$val);
//             // echo "DELETE FROM ".$value['Tables_in_alvarez_db']."WHERE ".$buscar_columna." = ".$val."<br>";
//         }
//     }
// }

// $columna = Db::getInstance()->ExecuteS("select id_product_attribute, id_articulo from aalv_combinaciones_import aci order by id_product_attribute ASC");
$columna = Db::getInstance()->ExecuteS('select id_product from aalv_combinacionunica_import aci');
$nn = 0;
$ids = [];
$dbcon = connectBD();
$os = [
    4298, 12321, 16087, 16088, 16089, 16093, 16102, 16125, 49831,
];
// foreach ($columna as $value) {

//     $columna2 = Db::getInstance()->ExecuteS("select `to`, `from`, id_product from aalv_specific_price asp  WHERE id_product_attribute = ".$value['id_product_attribute']." and id_country = 0 order by id_specific_price DESC limit 1");
//     if(count($columna2) > 0){
//         if($columna2[0]['to'] == '0000-00-00 00:00:00'){
//             if($columna2[0]['from'] > date('Y-m-d h:i:s')){
//                 // $nn++;
//                 $col = Db::getInstance()->ExecuteS("select id_product,reference  from aalv_product_attribute apa where id_product_attribute = ".$value['id_product_attribute']);
//                 if(!in_array($col[0]['id_product'], $os)){

//                     $columna22 = Db::getInstance()->ExecuteS("  select
//                                                                     ap.active,
//                                                                     aps.active AS active_shop
//                                                                 from
//                                                                     aalv_product ap
//                                                                     left join aalv_product_shop aps on ap.id_product = aps.id_product
//                                                                 WHERE
//                                                                     ap.id_product = ".$col[0]['id_product']);

//                     if($columna22[0]['active'] == 1 && $columna22[0]['active_shop'] == 1){
//                         // dump($col[0]['id_product']);die();
//                         if (!in_array($col[0]['id_product'], $ids)) {

//                             $id_modelo = Db::getInstance()->getValue("select id_modelo from aalv_product_import api where id_product =".$col[0]['id_product']);
//                             $category = Db::getInstance()->ExecuteS("   select
//                                                                             acl.name
//                                                                         from
//                                                                             aalv_category_product acp
//                                                                             left join aalv_category_lang acl on acl.id_category = acp.id_category
//                                                                         WHERE
//                                                                             acp.id_product = ".$col[0]['id_product']."
//                                                                             and acp.id_category IN (3,4,5,6,7,8,9,10,11)
//                                                                             and acl.id_lang = 1");

//                             $ids[] = $col[0]['id_product'];
//                             echo "id_product => ".$col[0]['id_product']."\n";
//                             echo $id_modelo."\n";
//                             foreach ($category as $val) {
//                                 echo "deporte => ".$val['name']."\n";
//                             }
//                             echo "id_product_attribute => ".$value['id_product_attribute']."\n";
//                             echo "id_articulo => ".$value['id_articulo']."\n";
//                             echo "reference => ".$col[0]['reference']."\n";
//                             $nn++;
//                             echo "NN => ".$nn."\n";
//                             echo "---------------------\n";
//                             $data = mysqli_query($dbcon, "select * from tarifa_cabecera where idarticulo = ".$value['id_articulo']." and idregpais = 1 and finicio = '2024-06-27 04:00:00.0'");
//                             $re = mysqli_fetch_array($data);
//                             $re["idttarifa"] = 1;
//                             $re["codigo_iso_pais"] = '';

//                             ProcesarTarifaCabecera($re,NULL,2);
//                             $data2 = mysqli_query($dbcon, "select * from tarifa_linea where idtarifa_cabecera = ".$re['idtarifa_cabecera']);
//                             $re2 = mysqli_fetch_array($data2);
//                             ProcesarTarifaLinea($re2,$re2['idtarifa_linea'],2);
//                             // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$col[0]['id_product']);

//                         }
//                     }
//                 }
//             }
//         }
//         // if($nn == 100){
//         //     die();
//         // }
//     }
// }

foreach ($columna as $value) {

    $columna2 = Db::getInstance()->ExecuteS('select `to`, `from`, id_product from aalv_specific_price asp  WHERE id_product_attribute = '.$value['id_product_attribute'].' and id_country = 0 order by id_specific_price DESC limit 1');
    if (count($columna2) > 0) {
        if ($columna2[0]['to'] == '0000-00-00 00:00:00') {
            if ($columna2[0]['from'] > date('Y-m-d h:i:s')) {
                // $nn++;
                $col = Db::getInstance()->ExecuteS('select id_product,reference  from aalv_product_attribute apa where id_product_attribute = '.$value['id_product_attribute']);
                if (! in_array($col[0]['id_product'], $os)) {
                    // echo "id_product => ".$col[0]['id_product']."\n";
                    // echo "reference => ".$col[0]['reference']."\n";

                    $columna22 = Db::getInstance()->ExecuteS('  select
                                                                    ap.active,
                                                                    aps.active AS active_shop
                                                                from
                                                                    aalv_product ap
                                                                    left join aalv_product_shop aps on ap.id_product = aps.id_product
                                                                WHERE
                                                                    ap.id_product = '.$col[0]['id_product']);

                    // if($columna22[0]['active'] == 1 && $columna22[0]['active_shop'] == 1){
                    //     echo "id_product => ".$col[0]['id_product']."\n";
                    // echo "reference => ".$col[0]['reference']."\n";
                    //         if (!in_array($col[0]['id_product'], $ids)) {

                    $id_modelo = Db::getInstance()->getValue('select id_modelo from aalv_product_import api where id_product ='.$col[0]['id_product']);
                    $category = Db::getInstance()->ExecuteS('   select
                                                                            acl.name
                                                                        from
                                                                            aalv_category_product acp
                                                                            left join aalv_category_lang acl on acl.id_category = acp.id_category
                                                                        WHERE
                                                                            acp.id_product = '.$col[0]['id_product'].'
                                                                            and acp.id_category IN (3,4,5,6,7,8,9,10,11)
                                                                            and acl.id_lang = 1');

                    $ids[] = $col[0]['id_product'];
                    echo 'id_product => '.$col[0]['id_product']."\n";
                    echo $id_modelo."\n";
                    foreach ($category as $val) {
                        echo 'deporte => '.$val['name']."\n";
                    }
                    echo 'id_product_attribute => '.$value['id_product_attribute']."\n";
                    echo 'id_articulo => '.$value['id_articulo']."\n";
                    echo 'reference => '.$col[0]['reference']."\n";
                    $nn++;
                    echo 'NN => '.$nn."\n";
                    echo "---------------------\n";
                    // $data = mysqli_query($dbcon, "select * from tarifa_cabecera where idarticulo = ".$value['id_articulo']." and idregpais = 1 and finicio = '2024-06-27 04:00:00.0'");

                    // $re = mysqli_fetch_array($data);
                    // // dump($re);die();
                    // $re["idttarifa"] = 1;
                    // $re["codigo_iso_pais"] = '';

                    // ProcesarTarifaCabecera($re,NULL,2);
                    // $data2 = mysqli_query($dbcon, "select * from tarifa_linea where idtarifa_cabecera = ".$re['idtarifa_cabecera']);
                    // $re2 = mysqli_fetch_array($data2);
                    // ProcesarTarifaLinea($re2,$re2['idtarifa_linea'],2);
                    // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$col[0]['id_product']);

                    //         }
                    // }
                }
            }
        }
        // if($nn == 100){
        //     die();
        // }
    }
}

function ProcesarTarifaCabecera($data, $fila, $tipo)
{

    // {"idtarifa_cabecera":101536741,"estado":true,"idarticulo":100227795,"idalmacen":null,"idregpais":1,"idimppais_fecha":3,"porc_iva":"21.000","tarifa_base":true,"tarifa_calculada":false,"importe_exento":"0.0000","finicio":"2019-08-31T00:00:00","ffin":"2020-02-05T23:59:59","idtarifa_cabecera_tcalculo":2,"idproducto":null}

    try {

        if ($tipo <= 2) {

            if (! $data) {
                // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            $idtarifa_cabecera = $data['idtarifa_cabecera'];
            $idarticulo = $data['idarticulo'];

            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo);
            $idprodattrps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo='.$idarticulo);

            if ($idprodps == '') {
                if ($idprodattrps != '') {
                    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idprodattrps);
                }
            } else {
                $idprodattrps = '0';
            }

            if ($data['estado'] == false) {

                $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
                foreach ($specificprices as $idsp) {
                    // $sp = new SpecificPrice((int)$idsp);
                    // $sp->delete();
                    Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);
                }

                $codpais = getPaisPrestashop($data);

                $finicio = str_replace('T', ' ', $data['finicio']);
                $ffin = str_replace('T', ' ', $data['ffin']);

                if (''.$finicio == '') {
                    $finicio = '0000-00-00 00:00:00';
                }

                if (''.$ffin == '') {
                    $ffin = '0000-00-00 00:00:00';
                }

                $sql = 'DELETE FROM aalv_specific_price where id_country='.$codpais." and `from`='".$finicio."' and `to`='".$ffin."' and id_product=".$idprodps.' and id_product_attribute='.$idprodattrps;

                echo "\n".$sql."\n";

                Db::getInstance()->Execute($sql);

                if ($idprodps != '') {
                    procesarcombinaciones($idprodps);
                }
                Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                return 1;
            }

            $piva = (int) $data['porc_iva'];

            $porciva = getIdIva();
            // if ($piva == 21) {
            //     $porciva = PORC_IVA_21;
            // }
            if ($piva == 10) {
                $porciva = 2;
            }
            if ($piva == 4) {
                $porciva = 3;
            }
            if ($piva == 0) {
                $porciva = 0;
            }

            if ($data['idregpais'] == 1 && $data['idttarifa'] == 1 && ! $data['codigo_iso_pais']) { // Cuidado que con las tarifas de otros países también se envía idregpais=1
                // Db::getInstance()->getValue("update aalv_product_shop set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);
                // Db::getInstance()->getValue("update aalv_product set id_tax_rules_group = ".$porciva." where id_product=".$idprodps);

                if ($idprodps != '') {
                    // $prod = new Product($idprodps);

                    // if ($prod->id_tax_rules_group!=$porciva){
                    //    $prod->id_tax_rules_group = $porciva;
                    //    $prod->update();
                    // }

                    $id_tax_rules_group = Db::getInstance()->getValue('select id_tax_rules_group from aalv_product_shop where id_product='.$idprodps);
                    if ($id_tax_rules_group != $porciva) {
                        Db::getInstance()->getValue('update aalv_product_shop set id_tax_rules_group = '.$porciva.' where id_product='.$idprodps);
                        Db::getInstance()->getValue('update aalv_product set id_tax_rules_group = '.$porciva.' where id_product='.$idprodps);
                    }
                }
            }

            // ver si existe ya esa cabecera
            $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);
            if ($specificprice != '') {
                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                if ($specificprice != '') {
                    $tipo = 2;
                }
            } else {
                $tipo = 1;
            }

            if ($tipo == 2) { // update

                if ($idprodps != '') {
                    // ver si existe precio específico para la tarifa cabecera
                    $specificprices = Db::getInstance()->ExecuteS('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                    foreach ($specificprices as $idsp) {

                        $existesp = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$idsp['id_specific_price']);

                        $codpais = getPaisPrestashop($data);

                        $finicio = str_replace('T', ' ', $data['finicio']);
                        $ffin = str_replace('T', ' ', $data['ffin']);

                        if (''.$finicio == '') {
                            $finicio = '0000-00-00 00:00:00';
                        }

                        if (''.$ffin == '') {
                            $ffin = '0000-00-00 00:00:00';
                        }

                        if ($existesp != '') {
                            $sql = 'UPDATE aalv_specific_price SET id_country='.$codpais.",`from`='".$finicio."',`to`='".$ffin."' WHERE id_specific_price=".$idsp['id_specific_price'];
                            Db::getInstance()->Execute($sql);
                        } else {
                            $sql = 'REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                            VALUES ('.$idtarifa_cabecera.','.$codpais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')';
                            Db::getInstance()->Execute($sql);

                            /*
                            $sp = new SpecificPrice((int)$idsp["id_specific_price"]);



                            $sp->id_product = $idprodps;
                            $sp->id_product_attribute = $idprodattrps;
                            $sp->id_shop = 0;
                            $sp->id_group = 0;
                            $sp->id_customer = 0;
                            $sp->id_shop_group = 0;
                            $sp->id_currency = 0;


                            if ($data["idregpais"]==2){
                                $sp->id_country = 15; //portugal
                            }
                            else{
                                $sp->id_country = 0;
                            }


                            $finicio = str_replace("T", " ", $data["finicio"]);
                            $ffin = str_replace("T", " ", $data["ffin"]);

                            if ("".$finicio==""){
                                $finicio =  "0000-00-00 00:00:00";
                            }

                            if ("".$ffin==""){
                                $ffin =  "0000-00-00 00:00:00";
                            }


                            $sp->from = $finicio;
                            $sp->to = $ffin;

                            $sp->update();
                            */
                        }

                    }
                } else {
                    // correo de creacion de tarifa sin producto
                    // sendmail2("Viene tarifa cabecera ".$idtarifa_cabecera." antes de la creacion del articulo de id ".$idarticulo);
                }
            } else { // insert ¿que hacemos? no tenemos el precio, que va en las lineas ¿tabla auxiliar?

                if ($idprodps != '') {
                    $pais = getPaisPrestashop($data);

                    $finicio = str_replace('T', ' ', $data['finicio']);
                    $ffin = str_replace('T', ' ', $data['ffin']);

                    if (''.$finicio == '') {
                        $finicio = '0000-00-00 00:00:00';
                    }

                    if (''.$ffin == '') {
                        $ffin = '0000-00-00 00:00:00';
                    }

                    $sql = 'REPLACE INTO aalv_tarifa_cabecera_import(id_tarifa_cabecera, id_country, finicio, ffin, id_product, id_attribute)
                      VALUES ('.$idtarifa_cabecera.','.$pais.",'".$finicio."','".$ffin."',".$idprodps.','.$idprodattrps.')';
                    $res = Db::getInstance()->Execute($sql);
                } else {
                    // correo de creacion de tarifa sin producto
                    // sendmail2("Viene tarifa cabecera ".$idtarifa_cabecera." antes de la creacion del articulo de id ".$idarticulo);
                }
            }

            if ($idprodps != '') {

                procesarcombinaciones($idprodps);
            }

            return 1;
        }
    } catch (Exception $e) {
        return 1;
    }
}

function ProcesarTarifaLinea($data, $fila, $tipo)
{

    // {"idtarifa_linea":101539304,"estado":true,"idtarifa_cabecera":101536741,"udesde":"1.0000","baseimp":"1.6529","pvp":"2.0000","pvp_exento":"1.9500","dto":"0.00","mostrar_dto":false,"motivo_dto":"","pvp_anterior":null,"baseimp_anterior":null,"pvp_exento_anterior":null,"genera_puntos_fid":true,"aplicar_ofertas":true}

    try {
        if ($tipo <= 2) {

            if (! $data) {
                // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                return 1;
            }

            if ($data['estado'] == false) {

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                if ($specificprice != '') {
                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                    if ($specificprice != '') {
                        // $sp = new SpecificPrice((int)$specificprice);
                        // $idp = $sp->id_product;
                        // $sp->delete();
                        $idp = Db::getInstance()->getValue('select id_product from  aalv_specific_price WHERE id_specific_price='.$specificprice);
                        Db::getInstance()->Execute('DELETE FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                        procesarcombinaciones($idp);
                    }
                }

                Db::getInstance()->Execute('DELETE FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);

                return 1;
            }

            // ver si existe ya esa linea
            $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
            if ($specificprice != '') {
                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                if ($specificprice != '') {
                    $tipo = 2;
                }
            } else {
                $tipo = 1;
            }

            if ($tipo == 2) { // update

                $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_linea='.$fila);
                if ($specificprice != '') {

                    $specificprice = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$specificprice);

                    if ($specificprice != '') {

                        /*

    					$sp = new SpecificPrice((int)$specificprice);

                        $sp->id_shop = 0;
                        $sp->id_group = 0;
                        $sp->id_customer = 0;
                        $sp->id_shop_group = 0;
                        $sp->id_currency = 0;

    					if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){

                                    if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                        $sp->price = round($data["baseimp_anterior"],6);
                                        $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6);
                                    }
                                    else{
                                        $sp->price = round($data["baseimp"],6);
                                        $sp->reduction = 0;
                                    }
                            }
                            else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                            }
    					}
    					else{
    						$sp->price = round($data["baseimp"],6);
    						$sp->reduction = 0;
    					}

    			        $sp->from_quantity = (int)$data["udesde"];

    			        $sp->reduction_tax = 0;
    			        $sp->reduction_type = "amount";

    			        $sp->update();

                        */

                        if ($data['baseimp_anterior']) {

                            if ($data['baseimp_anterior'] > $data['baseimp']) {

                                // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                    $miprice = round($data['baseimp_anterior'], 6);
                                    $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }

                        $sql = 'UPDATE aalv_specific_price SET price='.$miprice.',from_quantity='.$data['udesde'].',reduction='.$mireduction.",reduction_tax=0,reduction_type='amount' WHERE id_specific_price=".$specificprice;
                        Db::getInstance()->Execute($sql);

                        $midproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$specificprice);
                        if ($midproduct != '') {
                            procesarcombinaciones($midproduct);
                        }
                    } else {
                        // como si fuera tipo 1

                        // insert, esperemos que hayan enviado antes la cabecera

                        // ver si existe cabecera
                        $idtarifa_cabecera = $data['idtarifa_cabecera'];

                        $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                        if ($existecabecera != '') {
                            $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                        }

                        if ($existecabecera != '') {

                            $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $idprodattr = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $finicio = ''.Db::getInstance()->getValue('SELECT `from` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $ffin = ''.Db::getInstance()->getValue('SELECT `to` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                            $country = ''.Db::getInstance()->getValue('SELECT id_country FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);

                            if ($data['baseimp_anterior']) {

                                if ($data['baseimp_anterior'] > $data['baseimp']) {

                                    // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                        $miprice = round($data['baseimp_anterior'], 6);
                                        $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                    } else {
                                        $miprice = round($data['baseimp'], 6);
                                        $mireduction = 0;
                                    }
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }

                            $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`)
                                    VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                            Db::getInstance()->Execute($sql);

                            $idnewsp = Db::getInstance()->Insert_ID();

                            Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea)
                                                        VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                            procesarcombinaciones($idproduct);

                            $productact = new Product((int) $idproduct);
                            $productact->update();

                            /*

                            //coger lo que manda en cabecera: producto, idattr, pais, inicio,fin
                            $sp = new SpecificPrice((int)$existecabecera);
                            $idproduct=$sp->id_product;
                            $idprodattr=$sp->id_product_attribute;
                            $finicio=$sp->from;
                            $ffin=$sp->to;
                            $country=$sp->id_country;

                            $sp = new SpecificPrice();
                            $sp->id_shop = 0;
                            $sp->id_group = 0;
                            $sp->id_customer = 0;
                            $sp->id_shop_group = 0;
                            $sp->id_currency = 0;



                            $sp->id_product=$idproduct;
                            $sp->id_product_attribute=$idprodattr;
                            $sp->from=$finicio;
                            $sp->to=$ffin;
                            $sp->id_country=$country;


                            if ($data["baseimp_anterior"]){

                                if ($data["baseimp_anterior"]>$data["baseimp"]){

                                        if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                            $sp->price = round($data["baseimp_anterior"],6);
                                            $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6);
                                        }
                                        else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                        }
                                }
                                else{
                                        $sp->price = round($data["baseimp"],6);
                                        $sp->reduction = 0;
                                }
                            }
                            else{
                                $sp->price = round($data["baseimp"],6);
                                $sp->reduction = 0;
                            }


                            $sp->from_quantity = (int)$data["udesde"];

                            $sp->reduction_tax = 0;
                            $sp->reduction_type = "amount";
                            $sp->add();


                            Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");

                            procesarcombinaciones($sp->id_product);
                            */
                        } else {
                            // coger de nueva tabla auxiliar

                            $existeauxiliar = ''.Db::getInstance()->getValue('SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                            if ($existeauxiliar != '') {

                                $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $idprodattr = Db::getInstance()->getValue('SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $finicio = Db::getInstance()->getValue('SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $ffin = Db::getInstance()->getValue('SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                                $country = Db::getInstance()->getValue('SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                                if ($data['baseimp_anterior']) {

                                    if ($data['baseimp_anterior'] > $data['baseimp']) {

                                        // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                        if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                            $miprice = round($data['baseimp_anterior'], 6);
                                            $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                        } else {
                                            $miprice = round($data['baseimp'], 6);
                                            $mireduction = 0;
                                        }
                                    } else {
                                        $miprice = round($data['baseimp'], 6);
                                        $mireduction = 0;
                                    }
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }

                                $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                                Db::getInstance()->Execute($sql);

                                $idnewsp = Db::getInstance()->Insert_ID();
                                Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                                procesarcombinaciones($idproduct);
                                $productact = new Product((int) $idproduct);
                                $productact->update();

                                /*
                                $sp = new SpecificPrice();
                                $sp->id_shop = 0;
                                $sp->id_group = 0;
                                $sp->id_customer = 0;
                                $sp->id_shop_group = 0;
                                $sp->id_currency = 0;




                                $sp->id_product=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->id_product_attribute=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->from=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->to=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                                $sp->id_country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);


                                if ($data["baseimp_anterior"]){

                                    if ($data["baseimp_anterior"]>$data["baseimp"]){

                                        if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                            $sp->price = round($data["baseimp_anterior"],6);
                                            $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6);
                                        }
                                        else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                        }
                                    }
                                    else{
                                            $sp->price = round($data["baseimp"],6);
                                            $sp->reduction = 0;
                                    }
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }


                                $sp->from_quantity = (int)$data["udesde"];

                                $sp->reduction_tax = 0;
                                $sp->reduction_type = "amount";
                                $sp->add();


                                Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                                procesarcombinaciones($sp->id_product);

                                */
                            }
                        }
                    }
                }
            } else {
                // insert, esperemos que hayan enviado antes la cabecera

                // ver si existe cabecera
                $idtarifa_cabecera = $data['idtarifa_cabecera'];

                $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price_import WHERE id_tarifa_cabecera='.$idtarifa_cabecera);

                if ($existecabecera != '') {
                    $existecabecera = ''.Db::getInstance()->getValue('SELECT id_specific_price FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                }

                if ($existecabecera != '') {

                    $idproduct = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $idprodattr = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $finicio = ''.Db::getInstance()->getValue('SELECT `from` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $ffin = ''.Db::getInstance()->getValue('SELECT `to` FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);
                    $country = ''.Db::getInstance()->getValue('SELECT id_country FROM aalv_specific_price WHERE id_specific_price='.$existecabecera);

                    if ($data['baseimp_anterior']) {

                        if ($data['baseimp_anterior'] > $data['baseimp']) {

                            // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                            if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                $miprice = round($data['baseimp_anterior'], 6);
                                $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }
                    } else {
                        $miprice = round($data['baseimp'], 6);
                        $mireduction = 0;
                    }

                    $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                    Db::getInstance()->Execute($sql);

                    $idnewsp = Db::getInstance()->Insert_ID();

                    Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                    procesarcombinaciones($idproduct);

                    $productact = new Product((int) $idproduct);
                    $productact->update();

                    /*

                    //coger lo que manda en cabecera: producto, idattr, pais, inicio,fin
                    $sp = new SpecificPrice((int)$existecabecera);
                    $idproduct=$sp->id_product;
                     $idprodattr=$sp->id_product_attribute;
                     $finicio=$sp->from;
                    $ffin=$sp->to;
                    $country=$sp->id_country;

                    $sp = new SpecificPrice();
                    $sp->id_shop = 0;
                    $sp->id_group = 0;
                    $sp->id_customer = 0;
                    $sp->id_shop_group = 0;
                    $sp->id_currency = 0;



                    $sp->id_product=$idproduct;
                     $sp->id_product_attribute=$idprodattr;
                     $sp->from=$finicio;
                    $sp->to=$ffin;
                    $sp->id_country=$country;


                    if ($data["baseimp_anterior"]){

                        if ($data["baseimp_anterior"]>$data["baseimp"]){

                                if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    $sp->price = round($data["baseimp_anterior"],6);
                                    $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6);
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }
                        }
                        else{
                                $sp->price = round($data["baseimp"],6);
                                $sp->reduction = 0;
                        }
                    }
                    else{
                        $sp->price = round($data["baseimp"],6);
                        $sp->reduction = 0;
                    }


                    $sp->from_quantity = (int)$data["udesde"];

                    $sp->reduction_tax = 0;
                    $sp->reduction_type = "amount";
                    $sp->add();


                    Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");
                    procesarcombinaciones($sp->id_product);

                    */
                } else {
                    // coger de nueva tabla auxiliar

                    $existeauxiliar = ''.Db::getInstance()->getValue('SELECT id_tarifa_cabecera FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                    if ($existeauxiliar != '') {

                        $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $idprodattr = Db::getInstance()->getValue('SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $finicio = Db::getInstance()->getValue('SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $ffin = Db::getInstance()->getValue('SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);
                        $country = Db::getInstance()->getValue('SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera='.$data['idtarifa_cabecera']);

                        if ($data['baseimp_anterior']) {

                            if ($data['baseimp_anterior'] > $data['baseimp']) {

                                // if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                if ((($data['baseimp_anterior'] - $data['baseimp']) >= 2.479338) || ((1 - ($data['baseimp'] / $data['baseimp_anterior'])) > 0.1)) {
                                    $miprice = round($data['baseimp_anterior'], 6);
                                    $mireduction = round((float) $data['baseimp_anterior'] - (float) $data['baseimp'], 6);
                                } else {
                                    $miprice = round($data['baseimp'], 6);
                                    $mireduction = 0;
                                }
                            } else {
                                $miprice = round($data['baseimp'], 6);
                                $mireduction = 0;
                            }
                        } else {
                            $miprice = round($data['baseimp'], 6);
                            $mireduction = 0;
                        }

                        $sql = 'INSERT INTO `aalv_specific_price`(`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES (0,0,'.$idproduct.',0,0,0,'.$country.',0,0,'.$idprodattr.','.$miprice.','.$data['udesde'].','.$mireduction.",0,'amount','".$finicio."','".$ffin."')";
                        Db::getInstance()->Execute($sql);

                        $idnewsp = Db::getInstance()->Insert_ID();
                        Db::getInstance()->Execute('INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES ('.$idnewsp.','.$data['idtarifa_cabecera'].','.$data['idtarifa_linea'].')');

                        procesarcombinaciones($idproduct);

                        $productact = new Product((int) $idproduct);
                        $productact->update();

                        /*
                        $sp = new SpecificPrice();
                        $sp->id_shop = 0;
                        $sp->id_group = 0;
                        $sp->id_customer = 0;
                        $sp->id_shop_group = 0;
                        $sp->id_currency = 0;

                        $sp->id_product=Db::getInstance()->getValue("SELECT id_product FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                         $sp->id_product_attribute=Db::getInstance()->getValue("SELECT id_attribute FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                         $sp->from=Db::getInstance()->getValue("SELECT finicio FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $sp->to=Db::getInstance()->getValue("SELECT ffin FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);
                        $sp->id_country=Db::getInstance()->getValue("SELECT id_country FROM aalv_tarifa_cabecera_import WHERE id_tarifa_cabecera=".$data["idtarifa_cabecera"]);


                        if ($data["baseimp_anterior"]){

                            if ($data["baseimp_anterior"]>$data["baseimp"]){

                                if ((($data["baseimp_anterior"]-$data["baseimp"])>=3) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
                                    $sp->price = round($data["baseimp_anterior"],6);
                                    $sp->reduction = round((float)$data["baseimp_anterior"]-(float)$data["baseimp"],6);
                                }
                                else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                                }
                            }
                            else{
                                    $sp->price = round($data["baseimp"],6);
                                    $sp->reduction = 0;
                            }
                        }
                        else{
                            $sp->price = round($data["baseimp"],6);
                            $sp->reduction = 0;
                        }


                        $sp->from_quantity = (int)$data["udesde"];

                        $sp->reduction_tax = 0;
                        $sp->reduction_type = "amount";
                        $sp->add();


                        Db::getInstance()->Execute("INSERT INTO aalv_specific_price_import(id_specific_price, id_tarifa_cabecera, id_tarifa_linea) VALUES (".$sp->id.",".$data["idtarifa_cabecera"].",".$data["idtarifa_linea"].")");


                        procesarcombinaciones($sp->id_product);

                        */
                    }
                }
            }
        }

        return 1;
    } catch (Exception $e) {
        return 1;
    }
}

function connectBD()
{

    return $dbcon;
}

function closeBD($dbcon)
{
    mysqli_close($dbcon);
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, 'alsernet:May.8006763');
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function getPaisPrestashop($data)
{
    if ($data['codigo_iso_pais']) {
        return Db::getInstance()->getValue("SELECT id_country FROM aalv_country WHERE iso_code = '".$data['codigo_iso_pais']."'");
    } else {
        switch ($data['idregpais']) {
            case 2: // Portugal
                return 15;

            case 1: // España
            default:
                return 0; // Todos los países
        }
    }
}

function getIdIva()
{
    return Db::getInstance()->getValue("SELECT id_tax_rules_group FROM aalv_tax_rules_group atrg WHERE active = 1 AND deleted = 0 AND `name` LIKE '%21%'");
}

function procesarcombinaciones($idproduct)
{

    // ver si tiene combinaciones

    $productattributes = Db::getInstance()->ExecuteS('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idproduct);

    if ($productattributes) {
        // echo "procesarcombinaciones => ".$productattributes."\n";

        $idproductatributeminimo = 0;
        $preciominimo = 999999;
        $numcambios = 0;

        $arprecios = [];

        foreach ($productattributes as $productattributeitem) {

            $noestaborrada = ''.Db::getInstance()->getValue('SELECT id_tot_switch_attribute_disabled FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$productattributeitem['id_product_attribute']);

            if ($noestaborrada == '') {

                // ver si tiene stock
                $stock = StockAvailable::getQuantityAvailableByProduct($idproduct, $productattributeitem['id_product_attribute']);

                if ($stock > 0) {
                    // coger precio
                    $specific_price = '';

                    $miprecio = Product::priceCalculation(1, $idproduct, $productattributeitem['id_product_attribute'], 0, 0, '', 0, 0, 1, true, 6, 0, false, false, $specific_price, false, 0, true, 0, 0, 0);

                    if (is_array($specific_price) && array_key_exists('reduction', $specific_price)) {
                        $miprecio = $miprecio - $specific_price['reduction'];
                    }

                    $miprecio = round($miprecio, 6);

                    $miprecio = ((int) ($miprecio * 100)) / 100;

                    if (! in_array($miprecio, $arprecios)) {
                        $arprecios[] = $miprecio;
                    }

                    if ($miprecio < $preciominimo) {

                        $preciominimo = $miprecio;
                        $idproductatributeminimo = $productattributeitem['id_product_attribute'];
                        $numcambios = $numcambios + 1;
                    }

                }
            }
        }

        if ($idproductatributeminimo != 0) {
            // hacer $idproductatributeminimo la combinacion Por defecto

            $product = new Product($idproduct);
            $product->deleteDefaultAttributes();
            $product->setDefaultAttribute($idproductatributeminimo);
        }

        if (count($arprecios) > 1) {
            // atributo desde
            // dump($idproduct);die();
            $idfeaturedesde = Feature::addFeatureImport('Poner desde');
            $idfeaturedesdevalue = crearFeatureValue($idfeaturedesde, '1', 0);
            if ($idfeaturedesdevalue != 0) {
                Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
                $product = new Product($idproduct);
                $product->addFeatureProductImport($idproduct, $idfeaturedesde, $idfeaturedesdevalue);
            }
        } else {
            Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
        }

        // borrado cache producto

    }

    // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $idproduct);
    // exec('/usr/bin/php /var/www/clients/client1/web1/web/scriptsalsernet/generarcache.php debug producto='.$product->id, $output, $retval);

}

function crearFeatureValue($idFeature, $value, $custom)
{

    $idFeatureValue = Db::getInstance()->getValue('
                SELECT fv.`id_feature_value`
                FROM '._DB_PREFIX_.'feature_value fv
                LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                WHERE `value` = \''.pSQL($value).'\'
                AND fv.`id_feature` = '.(int) $idFeature.'
                AND fv.`custom` = '.$custom.'
                GROUP BY fv.`id_feature_value`');
    if ($idFeatureValue) {
        return (int) $idFeatureValue;
    } else {
        // sendmailPruebas("Entra en crearFeatureValue() HORA: " . date('Y-m-d H:i:s'));
        $feature_value = new FeatureValue;
        $feature_value->id_feature = (int) $idFeature;
        $feature_value->custom = $custom;
        $feature_value->value = array_fill_keys(Language::getIDs(false), $value);

        $feature_value->add();

        return (int) $feature_value->id;
    }
}
