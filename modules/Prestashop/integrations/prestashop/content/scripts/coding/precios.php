<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
// include ('/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php');
function connectBD()
{
    $dbcon = mysqli_connect('213.134.40.117', 'tiendalv', 'Alv.121126');
    mysqli_select_db($dbcon, 'tienda');
    return $dbcon;
}

// and visibility = 'none'
// (65732,69104,69099)

$sql = Db::getInstance()->ExecuteS("select * from aalv_product where active = 1 and id_product not in (
65732,69104,69099
) and reference not like '%_CUSTOM%' order by id_product desc");
// 6    => ES
// 15   => PT
// 8    => FR
// 1    => DE
// 10   => IT
// 2    => AU

// dump($argv[1]);die();

$id_country = $argv[1];
$excluir_producto = [
    63957, //JORNADA AQUALUNG+APEKS
    32827, //Cheque regalo
    32828, //Cheque Regalo
    32829, //Cheque Regalo
    32830, //Cheque Regalo
    32831, //Cheque Regalo
    32832, //Cheque Regalo
    32833, //Cheque Regalo
    32834, //Cheque Regalo
    32835, //Cheque Regalo,
    53609, // LICEN-PR-SR-INT-S-WE
    53608, // LICEN-PR-SR-VAL-S-WE
    53607, // LICENCIA-PR-SR-PV-S-
    53606, // LICENCIA-PR-SR-NV-S-
    53605, // LICENCIA-PR-SR-MR-S-
    53604, // LICENCIA-PR-SR-MD-S-
    53603, // LICENCIA-PR-S-RIO-S-
    53602, // LICENCIA-PMO-GAL-S-W
    53601, // LICEN-PR-SR-EXT-WEB
    53599, // LICEN-R-S-CAT-WEB
    53598, // LICENCIA-PR-S-CL-WEB
    53597, // LICENCIA-PR--MAN-WEB
    53596, // LICENCIA-PR-CAN-WEB
    53594, // LICENCIA-PR-S-AST-S-
    53593, // LICENCIA-PR-ARA-S-WE
    53592, // LICENCIA-PR-SR-AN-S-
    53590, // LICEN-C-INT-U-CR-WEB
    53589, // LICENCIA-VAL-CR-WEB
    53588, // LICENCIA-C-PVS-CR-S-
    53587, // LICENCIA-C-NAV-CR-S-
    53586, // LICENCIA-C-MUR-S-WEB
    53585, // LICENCIA-C-MADRID-U-
    53583, // LICENCIA-C-RIJ-CR-S-
    53582, // LICENCIA-C-GAL-CR-WE
    53581, // LICENCIA-C-EXT-S-WEB
    53580, // LICENCIA-C-CAT-U-WEB
    53579, // LICENCIA-C-CL-S-WEB
    53578, // LICENCIA-C-CMAN-S-WE
    53576, // LICEN-C-BAL-S-WEB
    53575, // LICENCIA-C-CAN-S-WEB
    53574, // LICEN-C-AST-CR-WEB
    53573, // LICENCIA-C-ARA-S-WEB
    53571, // LICENCIA-C-AN-SR-S-W
    71858, // G3WY00006-GEN_CUSTOM
    71952, // G3WY00006-GEN_CUSTOM
    72088, // G3WY00006-GEN_CUSTOM
    72109, // G3WY00006-GEN_CUSTOM
    72110, // G3WY00006-GEN_CUSTOM
    71723, // G3PY00008-GEN3_CUSTOM
    71720, // G3PY00013-GEN8_CUSTOM
    72251, // GWYBZZ115-GEN_CUSTOM_CUSTOM
    72250, // GWYBZZ115-GEN_CUSTOM
    72257, // GWYBZZ115-GEN_CUSTOM
    72249, // G3HIY00005-GEN2_CUSTOM
    72248, // G3HIY00005-GEN_CUSTOM_CUSTOM_CUS
    72247, // G3MY00006-GEN3_CUSTOM
    72232, // G3MDY00008-GEN_CUSTOM
    72246, // G3MDY00008-GEN_CUSTOM
    72245, // G3HIY00005-GEN_CUSTOM_CUSTOM
    72244, // G3HIY00005-GEN_CUSTOM
    72233, // G3MDY00007-GEN_CUSTOM
    72237, // G3MDY00007-GEN_CUSTOM
    72339, // G3HY00011-GEN1_CUSTOM
    72342, // G3HY00011-GEN1_CUSTOM_CUSTOM
    72343, // G3HY00011-GEN1_CUSTOM
    72378, // G3HY00011-GEN1_CUSTOM
    72380, // G3HY00011-GEN1_CUSTOM_CUSTOM
    72381, // G3HY00011-GEN1_CUSTOM
    72382, // G3HY00011-GEN1_CUSTOM_CUSTOM_CUS
    72432, // G3HY00011-GEN1_CUSTOM
    72427, // G3HY00009-GEN_CUSTOM
    72460, // G3HY00009-GEN_CUSTOM
    72461, // G3HY00009-GEN_CUSTOM
    72421, // G3HY00012-GEN_CUSTOM
    72447 // G3HY00012-GEN_CUSTOM

];



$dbcon = connectBD();
$archivo_resultado = '/home/alvarez/web/scripts/coding/precios_diferencias_'.$id_country.'.csv'; // Cambia la ruta si quieres otro destino
file_put_contents($archivo_resultado, '');

foreach ($sql as $value) {
    if (in_array($value['id_product'], $excluir_producto)) {
        continue;
    }

    # code...
    $feature_product_telefono = Db::getInstance()->executeS("select * from aalv_feature_product afp where id_product = ".$value['id_product']." and id_feature = 2 and id_feature_value = 5");

    if(count($feature_product_telefono) > 0){
        continue;
    }
    // Verifica si el producto tiene combinaciones
    $combinations = Db::getInstance()->executeS("SELECT pa.`id_product_attribute`, pa.`reference`
        FROM `aalv_product_attribute` pa
        INNER JOIN aalv_product_attribute_shop product_attribute_shop
        ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop = 1)
        WHERE pa.`id_product` = " . $value['id_product']);

    if (count($combinations) > 0) {
        // Verificamos los precios en la tabla specific_price para el id_country = 1
        foreach ($combinations as $combinationnnnn) {

            $estado_gestion = Db::getInstance()->executeS("select aci.estado_gestion, aci.es_segunda_mano from aalv_combinaciones_import aci where aci.id_product_attribute = ".$combinationnnnn['id_product_attribute']);

            if(count($estado_gestion) > 0 && $estado_gestion[0]['estado_gestion'] == 0){
                continue;
            }

            if (count($estado_gestion) > 0 && $estado_gestion[0]['es_segunda_mano'] != 0 && $id_country != 6) {
                continue;
            }

            $price_query = Product::priceCalculation(
                1,                // id_shop
                $value['id_product'],            // id_product
                $combinationnnnn['id_product_attribute'],           // id_product_attribute
                $id_country,                // id_country
                0,                // id_state
                '',            // zipcode
                1,                // id_currency
                3,                // id_group
                1,                // quantity
                true,             // use_tax
                2,                // decimals
                false,            // only_reduc
                true,             // use_reduc
                true,             // with_ecotax
                $specific_price,  // Pasamos la variable que es modificable
                true,             // use_group_reduction
                0,                // id_customer
                true,             // use_customer_price
                0,                // id_cart
                0,                // real_quantity
                0                 // id_customization
            );

            // if($price_query < 1){
            //     $combinaciones_import = Db::getInstance()->executeS("select * from aalv_combinaciones_import aci where id_product_attribute = ".$combinationnnnn['id_product_attribute']." and estado_gestion != 0");
            //     if(count($combinaciones_import) > 0){
            //         $id_stock_available = StockAvailable::getStockAvailableIdByProductId($value['id_product'],$combinationnnnn['id_product_attribute']);
            //         $stock_available = new StockAvailable($id_stock_available);
            //         if($stock_available->quantity > 0){
            //             dump($price_query);
            //             dump("stock => ".$stock_available->quantity);
            //             dump("id_product => ".$value['id_product']);
            //             dump("id_product_attribute => ".$combinationnnnn['id_product_attribute']);
            //             dump("reference => ".$combinationnnnn['reference']);
            //             dump("-----------------\n\n");
            //         }
            //     }
            // }
            // if(esComercial($price_query,$id_country)){
            //     $combinaciones_import = Db::getInstance()->executeS("select * from aalv_combinaciones_import aci where id_product_attribute = ".$combinationnnnn['id_product_attribute']." and estado_gestion != 0");
            //     if(count($combinaciones_import) > 0){
            //         $id_stock_available = StockAvailable::getStockAvailableIdByProductId($value['id_product'],$combinationnnnn['id_product_attribute']);
            //         $stock_available = new StockAvailable($id_stock_available);
            //         if($stock_available->quantity > 0){
            //             dump("NO ES COMERCIAL EL PRECIO");
            //             dump($price_query.';'.$combinationnnnn['reference']);
            //             // dump("stock => ".$stock_available->quantity);
            //             // dump("id_product => ".$value['id_product']);
            //             // dump("id_product_attribute => ".$combinationnnnn['id_product_attribute']);
            //             // dump("reference => ".$combinationnnnn['reference']);
            //             // dump("-----------------\n\n");

            //         }
            //     }
            // }
            $web = compararWebAntigua($dbcon,$price_query,$combinationnnnn['reference'],$id_country);
            if($web['estado']){
                // if($price_query == 0){

                    // dump($value['id_product'].";".$combinationnnnn['reference'].";".$price_query.";".$web['price']);
                    $linea = $value['id_product'] . ";" . $combinationnnnn['reference'] . ";" . $price_query . ";" . $web['price'] . "\n";
                    file_put_contents($archivo_resultado, $linea, FILE_APPEND);
                // }
                // dump("precio PS => ".$price_query);
                // dump("precio antigua => ".$web['price']);
                // dump("id_product => ".$value['id_product']);
                // dump("id_product_attribute => ".$combinationnnnn['id_product_attribute']);
                // dump("reference => ".$combinationnnnn['reference']);
                // dump("-----------------\n\n");
            }

        }
    }else{

        $estado_gestion = Db::getInstance()->executeS("select aci.estado_gestion, aci.es_segunda_mano from aalv_combinacionunica_import aci where aci.id_product = ".$value['id_product']);

        if(count($estado_gestion) > 0 && $estado_gestion[0]['estado_gestion'] == 0){
            continue;
        }

        if (count($estado_gestion) > 0 && $estado_gestion[0]['es_segunda_mano'] != 0 && $id_country != 6) {
            continue;
        }

        $price_query = Product::priceCalculation(
            1,                // id_shop
            $value['id_product'],            // id_product
            0,           // id_product_attribute
            $id_country,                // id_country
            0,                // id_state
            '',            // zipcode
            1,                // id_currency
            3,                // id_group
            1,                // quantity
            true,             // use_tax
            2,                // decimals
            false,            // only_reduc
            true,             // use_reduc
            true,             // with_ecotax
            $specific_price,  // Pasamos la variable que es modificable
            true,             // use_group_reduction
            0,                // id_customer
            true,             // use_customer_price
            0,                // id_cart
            0,                // real_quantity
            0                 // id_customization
        );

        // if($price_query < 1){
        //     $combinaciones_import = Db::getInstance()->executeS("select * from aalv_combinacionunica_import aci where id_product =  ".$value['id_product']." and estado_gestion != 0");
        //     if(count($combinaciones_import) > 0){
        //         $id_stock_available = StockAvailable::getStockAvailableIdByProductId($value['id_product'],0);
        //         $stock_available = new StockAvailable($id_stock_available);
        //         if($stock_available->quantity > 0){
        //             dump($price_query);
        //             dump("stock => ".$stock_available->quantity);
        //             dump($value['id_product']);
        //             dump("-----------------\n\n");
        //         }
        //     }
        // }
        // if(esComercial($price_query,$id_country)){
        //     $combinaciones_import = Db::getInstance()->executeS("select * from aalv_combinacionunica_import aci where id_product =  ".$value['id_product']." and estado_gestion != 0");
        //     if(count($combinaciones_import) > 0){
        //         $id_stock_available = StockAvailable::getStockAvailableIdByProductId($value['id_product'],0);
        //         $stock_available = new StockAvailable($id_stock_available);
        //         if($stock_available->quantity > 0){
        //             $refe = Db::getInstance()->executeS("select reference from aalv_product ap where id_product = ".$value['id_product']);
        //             dump("NO ES COMERCIAL EL PRECIO");
        //             dump($price_query.';'.$refe[0]['reference']);
        //             // dump("stock => ".$stock_available->quantity);
        //             // dump($value['id_product']);
        //             // dump("-----------------\n\n");

        //         }
        //     }
        // }
        $web = compararWebAntigua($dbcon,$price_query,$value['reference'],$id_country);
        if($web['estado']){
            // if($price_query == 0){
                // dump($value['id_product'].";".$value['reference'].";".$price_query.";".$web['price']);
                $linea = $value['id_product'] . ";" . $value['reference'] . ";" . $price_query . ";" . $web['price'] . "\n";
                file_put_contents($archivo_resultado, $linea, FILE_APPEND);
            // }
            // dump("precio PS => ".$price_query);
            // dump("precio antigua => ".$web['price']);
            // dump("id_product => ".$value['id_product']);
            // dump("reference => ".$value['reference']);
            // dump("-----------------\n\n");
        }
    }
}
dump("Listo");




function esComercial($numero,$id_country) {
    // Aseguramos que tenga dos decimales
    $decimal = number_format($numero, 2, '.', '');

    // Obtenemos los últimos dos dígitos después del punto decimal
    $parteDecimal = substr($decimal, -2);

    // Verificamos si termina en .00 o si el último dígito (antes o después del punto) es 9
    if($id_country == 15){
        if ($parteDecimal === '00' || $parteDecimal === '50' || substr($decimal, -1) === '9') {
            return false;
        }
    }else{
        if ($parteDecimal === '00' || substr($decimal, -1) === '9') {
            return false;
        }

    }

    return true;
}

function compararWebAntigua($dbco,$price_query,$reference,$id_country) {
    if(is_null($reference) || $reference == ''){
        return ["price" => 00000,0000, "estado" => false];
    }
    switch ($id_country) {
        case '6': //España
            $pais = 1;
            break;
        case '15': //Portugal
            $pais = 2;
            break;
        case '8': //Francia
            $pais = 3;
            break;
        case '1': //Alemania
            $pais = 4;
            break;
        case '10': //Italia
            $pais = 5;
            break;
        case '2': //Austria
            $pais = 6;
            break;
    }

    //Buscamos las tarifas en la web Antigua
    $sql_antigua    = " select
                            tl.pvp
                        from
                            tarifa_linea as tl
                            inner join tarifa_cabecera as tc on (tc.idtarifa_cabecera = tl.idtarifa_cabecera AND tc.finicio <= NOW() AND (tc.ffin IS NULL OR tc.ffin >= NOW()) AND tc.estado = 1)
                            inner join producto as p on (p.idarticulo = tc.idarticulo)
                        where
                            p.referencia like '".$reference."'
                            and tc.idregpais = ".$pais."
                            AND tl.estado = 1
                        order by tl.udesde";

// $sql_antigua    = " select
//                             tl.pvp
//                         from
//                             tarifa_linea as tl
//                             inner join tarifa_cabecera as tc on (tc.idtarifa_cabecera = tl.idtarifa_cabecera AND tc.finicio <= NOW() AND (tc.ffin IS NULL OR tc.ffin >= NOW()) AND tc.estado = 1)
//                             inner join producto as p on (p.idarticulo = tc.idarticulo)
//                         where
//                             p.referencia like '".$reference."'
//                             and tc.idregpais = ".$pais."
//                             and p.estado_gestion <> 0
//                             AND tl.estado = 1
//                         order by tl.udesde";


    $result_antigua = mysqli_query($dbco, $sql_antigua);
    $re_antigua = mysqli_fetch_array($result_antigua, MYSQLI_ASSOC);

    if (!is_array($re_antigua)) {
        $re_antigua['pvp'] = 0;
    }

    $web_pvp = round((float)$re_antigua['pvp'], 2); // Asegura que sea float con 2 decimales
    $ps_pvp = round((float)$price_query, 2);        // Asegura también

    if ($web_pvp !== $ps_pvp) {
        // dump("refe => ".$reference);
        // dump("web antigua => " . $web_pvp);
        // dump("PS => " . $ps_pvp);
        // dump("-----------------------------");
        return ["price" => $web_pvp, "estado" => true];
    }
    else{
        return ["price" => $web_pvp, "estado" => false];
    }
}
