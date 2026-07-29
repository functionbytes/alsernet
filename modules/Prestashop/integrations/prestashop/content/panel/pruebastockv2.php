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

$simple = Db::getInstance()->ExecuteS('SELECT id_product, id_articulo FROM aalv_combinacionunica_import aci ORDER BY id_product DESC');
$dbcon = connectBD();

foreach ($simple as $value) {

    $id_product_existe = Db::getInstance()->ExecuteS('select * from aalv_product where id_product = '.$value['id_product']);

    if (count($id_product_existe) == 0) {
        continue;
    }

    $lote = Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_alsernet_lotes_copia awbp WHERE active = 0 AND id_ps_product = '.$value['id_product']);

    if ($lote) {
        // echo "ES LOTE\n";
        continue;
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

    $data = mysqli_query($dbcon, $sql_antigua);
    if (! $data) {
        continue;
    }
    $re = mysqli_fetch_array($data);

    if (! is_null($re)) {
        continue;
    }
    // dump($re);die();
    $stock = Db::getInstance()->ExecuteS('SELECT quantity, quantity_pocomaco  FROM aalv_repositorio_stock ars WHERE id_product = '.$value['id_product']);

    if (count($stock) == 0) {
        Db::getInstance()->execute('INSERT INTO aalv_repositorio_stock
        (id_product, id_product_attribute, quantity, quantity_pocomaco)
        VALUES('.$value['id_product'].', 0, '.$re['stock_actual'].', '.$re['stock_almacen'].');');
        $stock = Db::getInstance()->ExecuteS('SELECT quantity, quantity_pocomaco  FROM aalv_repositorio_stock ars WHERE id_product = '.$value['id_product']);
    }

    $stock_antigua = controlStock($re['etiqueta'], $re['estado_gestion'], $re['externo_disponibilidad'], $re['stock_actual']);

    if ($stock[0]['quantity'] != $re['stock_actual']) {
        if ($re['stock_almacen'] < 0) {
            $re['stock_almacen'] = 0;
        }
        // Db::getInstance()->execute("UPDATE `aalv_combinacionunica_import` SET `etiqueta`='".$re['etiqueta']."',`estado_gestion`='".$re['estado_gestion']."',`externo_disponibilidad`='".$re['externo_disponibilidad']."' WHERE `id_product`=".$value['id_product']." AND `id_articulo`=".$value['id_articulo']);
        Db::getInstance()->execute("UPDATE `aalv_repositorio_stock` SET `quantity`='".$re['stock_actual']."',`quantity_pocomaco`='".$re['stock_almacen']."' WHERE `id_product`=".$value['id_product']);
        echo 'ID_PRODUCT => '.$value['id_product']."\n";
        // $product = new Product($value['id_product']);
        // $product->update();
        StockAvailable::setQuantity($value['id_product'], 0, $stock_antigua, 1, false);

        echo 'etiqueta_GT => '.$re['etiqueta']."\n";
        echo 'estado_gestion_GT => '.$re['estado_gestion']."\n";
        echo 'externo_disponibilidad_GT => '.$re['externo_disponibilidad']."\n";
        echo 'STOCK_AHORA => '.$stock_antigua."\n";
        echo 'STOCK_ANTES => '.$stock[0]['quantity']."\n";
        echo "--------------------------------------------\n\n";
        // die();
    } else {
        $stock_PS = Db::getInstance()->getValue('SELECT quantity FROM aalv_stock_available asa WHERE id_product = '.$value['id_product']);
        if ($stock_PS != $stock_antigua) {
            echo "STOCK DISTINTOS DE PS\n";
            echo 'ID_PRODUCT => '.$value['id_product']."\n";
            echo 'STOCK_AHORA => '.$stock_antigua."\n";
            echo 'STOCK_PS => '.$stock_PS."\n";
            // $product = new Product($value['id_product']);
            // $product->update();
            StockAvailable::setQuantity($value['id_product'], 0, $stock_antigua, 1, false);

            echo "--------------------------------------------\n\n";
            // die();
        } else {
            $activo = Db::getInstance()->getValue("SELECT IF(ap.active = aps.active, ap.active,'ajustar') AS active FROM aalv_product ap LEFT JOIN aalv_product_shop aps ON aps.id_product = ap.id_product WHERE ap.id_product = ".$value['id_product']);
            if ($activo == 0) {
                if ($re['estado_gestion'] != 0) {
                    echo "PRODUCTO INACTIVO EN PS\n";
                    echo 'ID_PRODUCT => '.$value['id_product']."\n";
                    $product = new Product($value['id_product']);
                    $product->active = true;
                    $product->update();
                    StockAvailable::setQuantity($value['id_product'], 0, $stock_antigua, 1, false);
                    echo "--------------------------------------------\n\n";
                    // die();
                }
            } elseif ($activo == 'ajustar') {
                echo 'AJUSTAR';
                exit();
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
            'Revisar articulo',
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
