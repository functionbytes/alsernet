<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');
include '/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php';

// $busca = Db::getInstance()->ExecuteS("  SELECT
//                                             asa.id_product,
//                                             aci.id_product_attribute,
//                                             asa.quantity
//                                         FROM
//                                             aalv_combinaciones_import aci
//                                             left join aalv_stock_available asa on asa.id_product_attribute = aci.id_product_attribute
//                                         WHERE
//                                             aci.etiqueta like '%CSW%'
//                                             GROUP  BY  asa.id_product
//                                             ORDER BY aci.id_product_attribute DESC
//                                             LIMIT 300,100 ");
// foreach ($busca as $value) {
//     // $se_oculta = Db::getInstance()->getValue("  SELECT
//     //                                                 COUNT(*)
//     //                                             FROM
//     //                                                 aalv_product p
//     //                                                 JOIN aalv_product_attribute pa ON p.id_product = pa.id_product
//     //                                                 JOIN aalv_stock_available sa ON pa.id_product_attribute = sa.id_product_attribute
//     //                                                 JOIN aalv_product_attribute  pc ON pa.id_product_attribute = pc.id_product_attribute
//     //                                             WHERE
//     //                                                 sa.quantity = 0
//     //                                                 AND p.active = 1
//     //                                                 AND pc.id_product_attribute = ".$value['id_product_attribute']."
//     //                                                 AND NOT EXISTS (
//     //                                                     SELECT
//     //                                                         1
//     //                                                     FROM
//     //                                                         aalv_tot_switch_attribute_disabled tsad
//     //                                                     WHERE
//     //                                                         tsad.id_product_attribute = pc.id_product_attribute
//     //                                                 )
//     //                                                 order by p.id_product DESC ");
//     // if($se_oculta != 0){
//     //     Db::getInstance()->execute("INSERT INTO aalv_tot_switch_attribute_disabled VALUES (null,".$value['id_product_attribute'].",1);");
//     //     var_dump($value['id_product']);
//     //     echo "<br>";
//     // }
//     $buscamos_attribute = Db::getInstance()->executeS(" SELECT
//                                                             sp.id_product_attribute,
//                                                             sp.price
//                                                         FROM
//                                                             aalv_product_attribute pa
//                                                             LEFT JOIN aalv_specific_price sp ON pa.id_product_attribute = sp.id_product_attribute
//                                                         WHERE
//                                                             pa.id_product = ".$value['id_product']."
//                                                             AND pa.id_product_attribute NOT IN (SELECT id_product_attribute FROM aalv_tot_switch_attribute_disabled)
//                                                             AND sp.id_product_attribute != 0
//                                                             AND sp.id_country = 0");
//     // Inicializar el precio más bajo
//     $precioMasBajo = PHP_INT_MAX;
//     $idAtributoMasBajo = null;
//     foreach ($buscamos_attribute as $valuee) {
//         // Encontrar el atributo con el precio más bajo
//         if ($valuee['price'] < $precioMasBajo) {
//             $precioMasBajo      = $valuee['price'];
//             $idAtributoMasBajo  = $valuee['id_product_attribute'];
//         }
//     }

//     if(!$idAtributoMasBajo){
//         peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$value['id_product']);
//         continue;
//     }

//     Db::getInstance()->execute("UPDATE aalv_product_attribute SET default_on = NULL WHERE id_product = ".$value['id_product']);
//     Db::getInstance()->execute("UPDATE aalv_product_attribute_shop SET default_on = NULL WHERE id_product = ".$value['id_product']);
//     Db::getInstance()->execute("UPDATE aalv_product_attribute SET default_on = 1 WHERE id_product = ".$value['id_product']." AND id_product_attribute = ".$value['id_product_attribute']);
//     Db::getInstance()->execute("UPDATE aalv_product_attribute_shop SET default_on = 1 WHERE id_product = ".$value['id_product']." AND id_product_attribute = ".$value['id_product_attribute']);
//     Db::getInstance()->execute("UPDATE aalv_product SET cache_default_attribute = ".$value['id_product_attribute']." WHERE id_product = ".$value['id_product']);
//     Db::getInstance()->execute("UPDATE aalv_product_shop SET cache_default_attribute = ".$value['id_product_attribute']." WHERE id_product = ".$value['id_product']);

//     peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$value['id_product']);

//     var_dump($value['id_product']);
//     echo "<br>";
// }

// function peticionget($url){
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     $content = curl_exec($ch);
//     curl_close($ch);
//     return $content;
// }

// $datos = Db::getInstance()->executeS("SELECT bo.`id_banner`, bo.`id_product`, bo.`id_category`, bo.`etiqueta_import`, bo.`id_feature_category`, bo.`id_feature_family`, bo.`id_feature_subfamily`, bo.`id_feature_group` FROM `aalv_banner_object` bo INNER JOIN `aalv_banner` b ON b.`id`=bo.`id_banner` AND b.`active`=1 AND b.`tipo`=2 INNER JOIN `aalv_banner_lang` bl ON bl.`id`=bo.`id_banner` AND bl.`id_lang`=1 WHERE bo.`id_zone`=7 AND ((CONCAT(',', REPLACE(bo.`id_product`, ' ', ''), ',') LIKE CONCAT('%,',48294, ',%')) OR (bo.`id_product`='' AND (1=1 AND (CONCAT(',', TRIM(bo.`reference`), ',') LIKE CONCAT('%,','C102464-1', ',%') OR bo.`reference`='') AND (CONCAT(',', TRIM(bo.`id_manufacturer`), ',') LIKE CONCAT('%,',383, ',%') OR bo.`id_manufacturer`='') AND (CONCAT(',', TRIM(bo.`id_feature_category`), ',') LIKE CONCAT('%,','100000402', ',%') OR bo.`id_feature_category`='') AND (CONCAT(',', TRIM(bo.`id_feature_family`), ',') LIKE CONCAT('%,','100000904', ',%') OR bo.`id_feature_family`='') AND (CONCAT(',', TRIM(bo.`id_feature_subfamily`), ',') LIKE CONCAT('%,','100001687', ',%') OR bo.`id_feature_subfamily`='') AND (CONCAT(',', TRIM(bo.`id_feature_group`), ',') LIKE CONCAT('%,','100007937', ',%') OR bo.`id_feature_group`='') AND (bo.`price_from`<=132.990011 OR bo.`price_from`=0) AND (bo.`price_up`>=132.990011 OR bo.`price_up`=0) ))) ORDER BY bo.`position` ASC");

// foreach ($datos as $key => $value) {
//     echo $value['id_category']."<br>";
// }

// $product = new Product(64549);

// $product->delete(true);
// dump('listo');

// controlStock(28747,67065,0,true);

$sql = Db::getInstance()->executeS('select id_articulo, aci.externo_disponibilidad , aci.etiqueta , aci.estado_gestion
from aalv_combinaciones_import aci where id_articulo >= 300062866 order by id_articulo asc');
$dbcon = connectBD();
$nn = 0;
$sum = 0;
foreach ($sql as $value) {
    // code...

    $sql_antigua = 'select externo_disponibilidad, etiqueta, estado_gestion from producto where idarticulo = '.$value['id_articulo'];
    $data = mysqli_query($dbcon, $sql_antigua);
    $re = mysqli_fetch_array($data, MYSQLI_ASSOC);

    $update = 'UPDATE aalv_combinaciones_import SET ';

    $set = '';
    if ((int) $value['externo_disponibilidad'] != (int) $re['externo_disponibilidad']) {
        $set .= 'externo_disponibilidad='.$re['externo_disponibilidad'].',';
    }
    if ((int) $value['estado_gestion'] != (int) $re['estado_gestion']) {
        $set .= 'estado_gestion='.$re['estado_gestion'].',';
    }

    if ($value['etiqueta'] != '' || $re['etiqueta'] != '') {
        $exp_ps = explode(', ', $value['etiqueta']);
        $exp_antigua = explode(', ', $re['etiqueta']);

        if (array_count_values($exp_ps) != array_count_values($exp_antigua)) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
        } elseif (! empty(array_diff($exp_ps, $exp_antigua)) || ! empty(array_diff($exp_antigua, $exp_ps))) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
        } elseif (count($exp_ps) != count($exp_antigua)) {
            $set .= 'etiqueta="'.$re['etiqueta'].'" ';
            dump($value);
            dump($exp_ps);
            dump($exp_antigua);
            echo '---------------------';
            echo '---------------------';
            exit();
        }
    }

    if ($set != '') {
        if (substr($set, -1) === ',') {
            $set = substr($set, 0, -1).' '; // Elimina el último carácter (la coma)
        }
        $update .= $set.'WHERE id_articulo = '.$value['id_articulo'];
        dump($value);
        dump($re);
        dump($set);
        dump($update);
        exit();
        Db::getInstance()->Execute($update);
        Product::alsernetNewVisibilidad($value['id_articulo']);
        exit();
    }

    if ($nn < 150) {
        echo '.';
        $nn++;
    }
    if ($nn == 150) {
        $sum = $sum + 150;
        echo ' '.$sum.'/'.count($sql);
        echo "\n";
        $nn = 0;
    }
}

// if (!array_count_values($array1) == !array_count_values($array2)) {
//     echo "Los arrays contienen los mismos elementos en las mismas cantidades.";
// } else {
//     echo "Los arrays son diferentes.";
// }

// if (!empty(array_diff($array1, $array2)) && !empty(array_diff($array2, $array1))) {
//     echo "Los arrays tienen los mismos elementos, sin importar el orden.";
// } else {
//     echo "Los arrays son diferentes.";
// }

// select etiqueta from producto where idarticulo = 1221
