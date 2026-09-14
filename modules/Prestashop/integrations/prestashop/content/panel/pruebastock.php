<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include '/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php';
// include (dirname(__FILE__).'/../init.php');

// $db = Db::getInstance()->ExecuteS("select id_product from aalv_product where active = false order by id_product desc");
// $nn = 0;

// foreach ($db as $key => $value) {

//     // $CONTROL_STOCK_WEB = false;
//     $buscar = Db::getInstance()->ExecuteS(" SELECT
//                                                 p.id_product,
//                                                 pl.name AS product_name,
//                                                 SUM(s.quantity) AS total_stock,
//                                                 s.id_product_attribute,
//                                                 imp.id_modelo
//                                             FROM
//                                                 aalv_product AS p
//                                                 LEFT JOIN aalv_product_import AS imp ON imp.id_product = p.id_product
//                                                 LEFT JOIN aalv_product_lang AS pl ON p.id_product = pl.id_product
//                                                 LEFT JOIN aalv_stock_available AS s ON p.id_product = s.id_product
//                                             WHERE
//                                                 p.id_product = ".$value['id_product']."
//                                                 and s.id_product_attribute != (select if(count(1) = 1,1,0) from aalv_stock_available where id_product = ".$value['id_product'].")
//                                                 and pl.id_lang = 1");

//     // $control = Db::getInstance()->getValue("select count(1) from aalv_combinacionunica_import where etiqueta like '%CONTROL_STOCK_WEB%' and id_product =". $buscar[0]['id_product']);
//     // if($control){
//     //     $CONTROL_STOCK_WEB = true;
//     // }else{
//     //     $control = Db::getInstance()->getValue("select count(1) from aalv_combinaciones_import where etiqueta like '%CONTROL_STOCK_WEB%' and id_product_attribute =". $buscar[0]['id_product_attribute']);
//     //     if($control){
//     //         $CONTROL_STOCK_WEB = true;
//     //     }
//     // }

//     // if($CONTROL_STOCK_WEB){
//         if($buscar[0]["total_stock"] != '0'){
//             //Db::getInstance()->ExecuteS("update aalv_product set active = 0 where id_product = ".$value['id_product']);
//             //Db::getInstance()->ExecuteS("update aalv_product_shop set active = 0 where id_product = ".$value['id_product']);
//             echo "id_producto: ".$buscar[0]['id_product']." - ";
//             echo "id_modelo: ".$buscar[0]['id_modelo']." - ";
//             echo "Cantidad Stock: ".$buscar[0]['total_stock']." - ";
//             echo "nombre: ".$buscar[0]['product_name']."<br><br><hr/>";
//             $nn++;
//             //break;
//         }
//     // }
// }
// echo "<br>".$nn;

// function ControlStock($id_product){
//     $CONTROL_STOCK_WEB = false;
//     $buscar = Db::getInstance()->ExecuteS(" SELECT
//                                                 p.id_product,
//                                                 pl.name AS product_name,
//                                                 SUM(s.quantity) AS total_stock
//                                             FROM
//                                                 aalv_product AS p
//                                                 LEFT JOIN aalv_product_lang AS pl ON p.id_product = pl.id_product
//                                                 LEFT JOIN aalv_stock_available AS s ON p.id_product = s.id_product
//                                                 left join aalv_tot_switch_attribute_disabled dis on s.id_product_attribute = dis.id_product_attribute
//                                             WHERE
//                                                 p.id_product = ".$id_product."
//                                                 and s.id_product_attribute != (select if(count(1) = 1,1,0) from aalv_stock_available where id_product = ".$id_product.")
//                                                 and pl.id_lang = 1
//                                                 and dis.id_tot_switch_attribute_disabled is null");

//     $control = Db::getInstance()->getValue("select
//                                                 count(1)
//                                             from
//                                                 aalv_combinacionunica_import
//                                             where
//                                                 etiqueta like '%CONTROL_STOCK_WEB%'
//                                                 and id_product =". $buscar[0]['id_product']);
//     if($control){
//         $CONTROL_STOCK_WEB = true;
//     }else{
//         $control = Db::getInstance()->getValue("select
//                                                     count(1)
//                                                 from
//                                                     aalv_combinaciones_import
//                                                 where
//                                                     etiqueta like '%CONTROL_STOCK_WEB%'
//                                                     and id_product_attribute =". $buscar[0]['id_product_attribute']);
//         if($control){
//             $CONTROL_STOCK_WEB = true;
//         }
//     }

//     if($CONTROL_STOCK_WEB){
//         if($buscar[0]["total_stock"] == '0'){
//             Db::getInstance()->ExecuteS("update aalv_product set active = 0 where id_product = ".$id_product);
//             Db::getInstance()->ExecuteS("update aalv_product_shop set active = 0 where id_product = ".$id_product);
//             return $id_product;
//         }
//     }
// }

// $todos = Db::getInstance()->ExecuteS("SELECT id_product FROM aalv_product WHERE active = 1");

// foreach ($todos as $value) {
//     $buscar = Db::getInstance()->getValue("SELECT COUNT(*) FROM aalv_specific_price WHERE id_product = ".$value['id_product']." AND `from` < NOW() GROUP BY id_product");
//     if($buscar == 0){
//         echo $value['id_product']."<br>";
//         // var_dump($value['id_product']);
//         // die();
//     }
// }

// $todos = Db::getInstance()->ExecuteS("SELECT * FROM aalv_product_import WHERE id_product in (

// )");

// foreach ($todos as $value) {
//     echo $value['id_modelo']." / ".$value['id_product']."<br>";
// }

// $fecha_inicial = new DateTime();

// $cantidad_de_dias = 30;

// for ($i = 0; $i < $cantidad_de_dias; $i++) {
//     $fecha = clone $fecha_inicial; // Clonar la fecha inicial para no modificarla directamente
//     $fecha->modify("-$i day"); // Restar $i días
//     $buscar = Db::getInstance()->ExecuteS(" SELECT
//                                                 count(*) as total,
//                                                 sum(o.total_paid_tax_incl) as precio
//                                             FROM
//                                                 aalv_orders o
//                                                 LEFT JOIN aalv_customer cu ON o.id_customer = cu.id_customer
//                                                 LEFT JOIN aalv_currency cur ON o.id_currency = cur.id_currency
//                                                 INNER JOIN aalv_address a ON o.id_address_delivery = a.id_address
//                                                 LEFT JOIN aalv_order_state os ON o.current_state = os.id_order_state
//                                                 LEFT JOIN aalv_shop s ON o.id_shop = s.id_shop
//                                                 INNER JOIN aalv_country c ON a.id_country = c.id_country
//                                                 INNER JOIN aalv_country_lang cl ON c.id_country = cl.id_country AND cl.id_lang = 1
//                                                 LEFT JOIN aalv_order_state_lang osl ON os.id_order_state = osl.id_order_state AND osl.id_lang = 1
//                                             WHERE
//                                                 (o.`id_shop` IN ('1'))
//                                                 AND (o.`date_add` >= '".$fecha->format('Y-m-d')." 0:0:0')
//                                                 AND (o.`date_add` <= '".$fecha->format('Y-m-d')." 23:59:59')
//                                             ORDER BY o.id_order desc");
//     echo "Fecha => ".$fecha->format('d/m/Y')."<br>";
//     echo "Total de compras => ".$buscar[0]['total']."<br>";
//     echo "Monto => ".number_format($buscar[0]['precio'],2)."<br>";
//     echo "-----------------------------------------<br>";
// }

// function countChildCategories($categoryId) {
//     // Obtener la categoría actual
//     $category = new Category($categoryId);

//     // Contador de subcategorías
//     $count = 0;

//     // Obtener subcategorías directas
//     $subcategories = $category->getSubCategories(Context::getContext()->language->id);

//     foreach ($subcategories as $subcategory) {
//         // Incrementar el contador por cada subcategoría
//         $count++;

//         // Llamada recursiva para contar las subcategorías de la subcategoría actual
//         $count += countChildCategories($subcategory['id_category']);
//     }

//     return $count;
// }

// // ID de la categoría padre
// $parentId = 2820; // Reemplaza con el ID de la categoría que quieres consultar

// // Obtener el número de categorías hijas recursivamente
// $totalChildCategories = countChildCategories($parentId);

// echo "La categoría con ID $parentId tiene $totalChildCategories categorías hijas en total.";

$combinacion = Db::getInstance()->ExecuteS('SELECT id_product_attribute, id_articulo FROM aalv_combinaciones_import aci ORDER BY id_product_attribute DESC
limit 120000,10000');
/*$combinacion = Db::getInstance()->ExecuteS("SELECT aci.id_product_attribute, aci.id_articulo FROM aalv_combinaciones_import aci
left join aalv_product_attribute apa on apa.id_product_attribute = aci.id_product_attribute
WHERE
apa.id_product = 67634
ORDER BY aci.id_product_attribute DESC");*/
$dbcon = connectBD();
$total = count($combinacion);
$nn = 0;
$suma = 0;
foreach ($combinacion as $value) {
    // code...
    $referencia = Db::getInstance()->ExecuteS('SELECT reference, id_product FROM aalv_product_attribute apa WHERE id_product_attribute = '.$value['id_product_attribute']);

    if (count($referencia) == 0) {
        dump('SIN REFE');

        continue;
    }

    $lote = Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_alsernet_lotes_copia awbp WHERE active = 0 AND id_ps_product = '.$referencia[0]['id_product']);

    if ($lote) {
        echo "ES LOTE\n";

        continue;
    }

    if (count($referencia) > 1) {
        dump('MAS DE DOS => '.$referencia);
        exit();
    }

    $sql_antigua = 'SELECT
                        IF(prod.stock_actual is null,0,prod.stock_actual) AS stock_actual,
                        IF(prod.stock_almacen is null,0,prod.stock_almacen) AS stock_almacen,
                        stoc.externo_disponibilidad,
                        stoc.etiqueta,
                        stoc.estado_gestion
                    FROM
                        producto stoc
                        LEFT JOIN control_stock prod on prod.idarticulo = stoc.idarticulo
                    WHERE stoc.idarticulo = '.$value['id_articulo'];
    // if($referencia[0]['reference'] != ''){
    //     $sql_antigua    .= "stoc.idarticulo = ".$value['id_articulo'];
    // }else{
    //     dump("SIN REFE => ".$referencia);die();
    // }
    $data = mysqli_query($dbcon, $sql_antigua);

    if (! $data) {
        continue;
    }

    $re = mysqli_fetch_array($data);

    if (is_null($re)) {
        continue;
    }

    $stock = Db::getInstance()->ExecuteS('SELECT quantity, quantity_pocomaco  FROM aalv_repositorio_stock ars WHERE id_product = '.$referencia[0]['id_product'].' AND id_product_attribute = '.$value['id_product_attribute']);

    if (count($stock) == 0) {
        Db::getInstance()->execute('INSERT INTO aalv_repositorio_stock
        (id_product, id_product_attribute, quantity, quantity_pocomaco)
        VALUES('.$referencia[0]['id_product'].', '.$value['id_product_attribute'].', '.$re['stock_actual'].', '.$re['stock_almacen'].');');
        $stock = Db::getInstance()->ExecuteS('SELECT quantity, quantity_pocomaco  FROM aalv_repositorio_stock ars WHERE id_product = '.$referencia[0]['id_product'].' AND id_product_attribute = '.$value['id_product_attribute']);
    }

    $stock_antigua = controlStock($re['etiqueta'], $re['estado_gestion'], $re['externo_disponibilidad'], $re['stock_actual']);

    if ($stock[0]['quantity'] != $re['stock_actual']) {

        if ($re['stock_almacen'] < 0) {
            $re['stock_almacen'] = 0;
        }
        // Db::getInstance()->execute("UPDATE `aalv_combinaciones_import` SET `etiqueta`='".$re['etiqueta']."',`estado_gestion`='".$re['estado_gestion']."',`externo_disponibilidad`='".$re['externo_disponibilidad']."' WHERE `id_product_attribute`=".$value['id_product_attribute']." AND `id_articulo`=".$value['id_articulo']);
        Db::getInstance()->execute("UPDATE `aalv_repositorio_stock` SET `quantity`='".$re['stock_actual']."',`quantity_pocomaco`='".$re['stock_almacen']."' WHERE `id_product`=".$referencia[0]['id_product'].' and `id_product_attribute`='.$value['id_product_attribute']);
        $bloqueo = Db::getInstance()->ExecuteS('select * from aalv_tot_switch_attribute_disabled WHERE id_product_attribute = '.$value['id_product_attribute']);
        if (count($bloqueo) > 0) {
            if ($stock_antigua > 0 && $re['estado_gestion'] != 0) {
                Db::getInstance()->execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute = '.$value['id_product_attribute']);
                echo 'ELIMINADO DEL DISABLED => '.$value['id_product_attribute']."\n";
            }
        }
        echo 'ID_PRODUCT => '.$referencia[0]['id_product']."\n";
        // $product = new Product($referencia[0]['id_product']);
        // $product->update();
        StockAvailable::setQuantity($referencia[0]['id_product'], $value['id_product_attribute'], $stock_antigua, 1, false);

        echo 'ID_PRODUCT_ATTRIBUTE => '.$value['id_product_attribute']."\n";
        echo 'etiqueta_GT => '.$re['etiqueta']."\n";
        echo 'estado_gestion_GT => '.$re['estado_gestion']."\n";
        echo 'externo_disponibilidad_GT => '.$re['externo_disponibilidad']."\n";
        echo 'STOCK_AHORA => '.$stock_antigua."\n";
        echo 'STOCK_ANTES => '.$stock[0]['quantity']."\n";
        echo "--------------------------------------------\n\n";
        // dump($value);
        // dump($referencia);
        // dump($stock);
        // dump($stock_antigua);
        // die();
    } else {

        $stock_PS = Db::getInstance()->getValue('SELECT quantity FROM aalv_stock_available asa WHERE id_product_attribute = '.$value['id_product_attribute'].' AND id_product = '.$referencia[0]['id_product']);
        if ($stock_PS != $stock_antigua) {

            echo "STOCK DISTINTOS DE PS\n";
            echo 'ID_PRODUCT => '.$referencia[0]['id_product']."\n";
            echo 'ID_PRODUCT_ATTRIBUTE => '.$value['id_product_attribute']."\n";
            echo 'Stock PS => '.$stock_PS."\n";
            echo 'Stock Antgular => '.$stock_antigua."\n";
            StockAvailable::setQuantity($referencia[0]['id_product'], $value['id_product_attribute'], $stock_antigua, 1, false);
            // $product = new Product($referencia[0]['id_product']);
            // $product->update();

            echo "--------------------------------------------\n\n";
            // die();
            // var_dump($value['id_product_attribute']);
            // var_dump($referencia[0]['id_product']);die();
        } else {
            $activo = Db::getInstance()->getValue("SELECT IF(ap.active = aps.active, ap.active,'ajustar') AS active FROM aalv_product ap LEFT JOIN aalv_product_shop aps ON aps.id_product = ap.id_product WHERE ap.id_product = ".$referencia[0]['id_product']);
            // dump($activo);die();
            if ($activo == 0) {

                if ($re['estado_gestion'] != 0) {
                    echo "PRODUCTO INACTIVO EN PS\n";
                    echo 'ID_PRODUCT => '.$referencia[0]['id_product']."\n";
                    echo 'ID_PRODUCT_ATTRIBUTE => '.$value['id_product_attribute']."\n";
                    $product = new Product($referencia[0]['id_product']);
                    $product->active = true;
                    $product->update();
                    echo "--------------------------------------------\n\n";
                    exit();
                }
            } elseif ($activo == 'ajustar') {
                echo 'AJUSTAR';
                exit();
            } elseif ($activo == 1) {
                // Verificamos si esta bloqueado por alguna razon
                $bloqueo = Db::getInstance()->ExecuteS('select * from aalv_tot_switch_attribute_disabled WHERE id_product_attribute = '.$value['id_product_attribute']);
                if (count($bloqueo) > 0) {
                    // Esta activo el producto y esta bloqueado
                    if ($re['estado_gestion'] != 0) {
                        // Significa que el producto esta activo
                        Db::getInstance()->execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute = '.$value['id_product_attribute']);
                        echo 'ID_PRODUCT => '.$referencia[0]['id_product']."\n";
                        echo 'ELIMINADO DEL DISABLED => '.$value['id_product_attribute']."\n";
                        StockAvailable::setQuantity($referencia[0]['id_product'], $value['id_product_attribute'], $stock_antigua, 1, false);
                        echo "--------------------------------------------\n\n";
                        // die();
                    }
                } elseif ($re['estado_gestion'] == 0) {
                    // Significa que no esta agregado
                    Db::getInstance()->execute('insert into aalv_tot_switch_attribute_disabled VALUES (null,'.$value['id_product_attribute'].',1)');
                    echo 'ID_PRODUCT => '.$referencia[0]['id_product']."\n";
                    echo 'INSERTADO EL DISABLED => '.$value['id_product_attribute']."\n";
                    echo "--------------------------------------------\n\n";
                    // die();
                }
            }
        }

    }
    $datos = Db::getInstance()->getRow('SELECT
                                                *,
                                                0 AS id_product_attribute,
                                                1 AS es_simple
                                            FROM
                                                aalv_combinacionunica_import aci
                                            WHERE
                                                id_articulo ='.$value['id_articulo']);

    if (! $datos) {
        $datos = Db::getInstance()->getRow('SELECT
                                                    apa.id_product,
                                                    0 AS es_simple,
                                                    aci.*
                                                FROM
                                                    aalv_combinaciones_import aci
                                                    LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                WHERE
                                                    aci.id_articulo ='.$value['id_articulo']);
    }
    if (! $datos) {
        $dest = [];
        $dest[] = 'alvarez@alsernet.es';

        $data = ['{message}' => 'Revisar el articulo => '.$value['id_articulo'].' que va a crear un producto nuevo'];
        Mail::Send(1,
            'integracion',
            'Integracion',
            $data,
            $dest,
            Configuration::get('PS_SHOP_NAME'),
            'desarrollotest@a-alvarez.com',
            'desarrollotest',
            [],
            null,
            _PS_MAIL_DIR_,
            false,
            1
        );

        continue;
    }
    Product::alsernetNewVisibilidad($value['id_articulo']);
    echo '.';
    $nn++;
    if ($nn == 100) {
        $suma = $suma + 100;
        echo ' '.$suma.'/'.$total;
        echo "\n";
        $nn = 0;
    }
}
echo "\nlisto\n";

function controlStock($etiqueta, $estado_gestion, $externo_disponibilidad, $stock)
{
    if (ocultarVeranoInvierno($etiqueta)) {
        return 0;
    } elseif (controlEtiquetaStockWeb($etiqueta) || $estado_gestion == 2) {
        return $stock;
    } elseif ($externo_disponibilidad) {
        return 999999;
    } else {
        return $stock;
    }
}

function ocultarVeranoInvierno($etiquetas)
{
    if ($etiquetas != '') {
        $etiquetasarray = explode(',', $etiquetas);
        foreach ($etiquetasarray as $key => $value) {
            $etiquetasarray[$key] = trim($value);
        }
        if (count($etiquetasarray) > 0) {
            $mes = (int) date('m');
            $dia = (int) date('d');
            if (in_array('TEMPORADA_INVIERNO', $etiquetasarray)) {
                switch ($mes) {
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                        return true;
                        break;
                    case 8:
                        if ($dia <= 15) {
                            return true;
                        }
                        break;
                }
            }
            if (in_array('TEMPORADA_VERANO', $etiquetasarray)) {
                switch ($mes) {
                    case 10:
                    case 11:
                    case 12:
                    case 1:
                        return true;
                        break;
                    case 2:
                        if ($dia <= 16) {
                            return true;
                        }
                        break;
                }
            }

            return false;
        }
    }

    return false;
}

function controlEtiquetaStockWeb($etiquetas)
{
    $tags_exclude = Db::getInstance()->getValue('SELECT GROUP_CONCAT(etiqueta) from aalv_etiqueta_stock');
    $tags_exclude = explode(',', $tags_exclude);
    $tags_exclude = array_map('trim', $tags_exclude);

    return array_intersect($tags_exclude, explode(', ', $etiquetas)) ? true : false;
}
