<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

include '/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php';

// // die();
$dbcon = connectBD();

//
$sql = Db::getInstance()->ExecuteS('select 0 as producto, id_articulo, unidades_oferta, etiqueta, estado_gestion,activo, es_segunda_mano, externo_disponibilidad, codigo_proveedor, precio_costo_proveedor, tarifa_proveedor, es_arma, es_arma_fogueo, es_cartucho, categoria, familia, subfamilia, grupo  from aalv_combinaciones_import aci where estado_gestion = 0
order by id_articulo desc

');

// UNION
// select 1 as producto, id_articulo, unidades_oferta, etiqueta, estado_gestion,activo, es_segunda_mano, externo_disponibilidad, codigo_proveedor, precio_costo_proveedor, tarifa_proveedor, es_arma, es_arma_fogueo, es_cartucho, categoria, familia, subfamilia, grupo from aalv_combinacionunica_import aci where estado_gestion = 0

$nn = 0;
$array = [];
$total = count($sql);
$suma = 0;
$cc = 0;
foreach ($sql as $value) {
    // code...

    $sql_antigua = 'SELECT
                        *
                    FROM
                        producto stoc
                    WHERE
                        stoc.idarticulo = '.$value['id_articulo'];

    $data = mysqli_query($dbcon, $sql_antigua);
    $re = mysqli_fetch_array($data);

    if (is_null($re)) {
        dump($value['id_articulo']);
        exit();
    }

    // Arreglo para almacenar las diferencias
    $diferencias = [];

    // Comparar cada valor con el valor de la base de datos
    if ($re['unidades_oferta'] != $value['unidades_oferta']) {
        if (is_null($re['unidades_oferta'])) {
            $re['unidades_oferta'] = 0;
        }
        $diferencias[] = 'unidades_oferta = '.$re['unidades_oferta'];
    }
    if ($re['etiqueta'] != $value['etiqueta'] && $value['etiqueta'] !== 'SEGUNDA MANO') {
        $diferencias[] = "etiqueta = '".$re['etiqueta']."'";
    }
    if ($re['estado_gestion'] != $value['estado_gestion']) {
        $diferencias[] = 'estado_gestion = '.$re['estado_gestion'];
    }
    if ($re['activo'] != $value['activo']) {
        $diferencias[] = 'activo = '.$re['activo'];
    }
    if ($re['es_segunda_mano'] != $value['es_segunda_mano']) {
        $diferencias[] = 'es_segunda_mano = '.$re['es_segunda_mano'];
    }
    if ($re['externo_disponibilidad'] != $value['externo_disponibilidad']) {
        $diferencias[] = 'externo_disponibilidad = '.$re['externo_disponibilidad'];
    }
    if ($re['codigo_proveedor'] != $value['codigo_proveedor']) {
        $diferencias[] = 'codigo_proveedor = '.$re['codigo_proveedor'];
    }
    if ($re['es_arma'] != $value['es_arma']) {
        $esarma = $re['es_arma'];
        if ($esarma) {
            $diferencias[] = 'es_arma = '.$esarma;
        }
    }
    if ($re['es_arma_fogueo'] != $value['es_arma_fogueo']) {
        $esarmafogueo = $re['es_arma_fogueo'];
        if ($esarmafogueo) {
            $diferencias[] = 'es_arma_fogueo = '.$esarmafogueo;
        }
    }
    if ($re['es_cartucho'] != $value['es_cartucho']) {
        $escartucho = $re['es_cartucho'];
        if ($escartucho) {
            $diferencias[] = 'es_cartucho = '.$escartucho;
        }
    }
    if ($re['categoria'] != $value['categoria']) {
        $categoria = $re['categoria'];
        $categoriaarray = explode('|', $categoria);
        if (count($categoriaarray) > 1) {
            $categoria = $categoriaarray[0];
        }
        if ($categoria) {
            $diferencias[] = 'categoria = '.$categoria;
        }
    }

    if ($re['familia'] != $value['familia']) {
        $familia = $re['familia'];
        $familiaarray = explode('|', $familia);
        if (count($familiaarray) > 1) {
            $familia = $familiaarray[0];
        }
        if ($familia) {
            $diferencias[] = 'familia = '.$familia;
        }
    }

    if ($re['subfamilia'] != $value['subfamilia']) {
        $subfamilia = $re['subfamilia'];
        $subfamiliaarray = explode('|', $subfamilia);
        if (count($subfamiliaarray) > 1) {
            $subfamilia = $subfamiliaarray[0];
        }
        if ($subfamilia) {
            $diferencias[] = 'subfamilia = '.$subfamilia;
        }
    }
    if ($re['grupo'] != $value['grupo']) {
        $grupo = $re['grupo'];
        $grupoarray = explode('|', $grupo);
        if (count($grupoarray) > 1) {
            $grupo = $grupoarray[0];
        }
        if ($grupo) {
            $diferencias[] = 'grupo = '.$grupo;
        }
    }

    // Si hay diferencias, mostrar cuáles son
    if (count($diferencias) > 0) {
        // Concatenar los cambios separados por coma
        $setClause = implode(', ', $diferencias);
        if ($value['producto'] == 0) {
            $sql = 'UPDATE aalv_combinaciones_import SET ';
        } else {
            $sql = 'UPDATE aalv_combinacionunica_import SET ';
        }
        // Construir la sentencia UPDATE
        $sql .= $setClause.' WHERE id_articulo = '.$value['id_articulo'];
        // dump($value);
        // dump($diferencias);
        echo "\n";
        dump($sql);
        echo "\n";
        // dump($re);
        Db::getInstance()->execute($sql);

        $sql_ant = 'SELECT
                        IF(prod.stock_actual is null,0,prod.stock_actual) AS stock_actual,
                        IF(prod.stock_almacen is null,0,prod.stock_almacen) AS stock_almacen,
                        stoc.externo_disponibilidad,
                        stoc.etiqueta,
                        stoc.estado_gestion
                    FROM
                        producto stoc
                        LEFT JOIN control_stock prod on prod.idarticulo = stoc.idarticulo
                    WHERE stoc.idarticulo = '.$value['id_articulo'];

        $dataa = mysqli_query($dbcon, $sql_ant);
        $rev2 = mysqli_fetch_array($dataa);
        // dump($rev2);
        $stock_antigua = controlStock($rev2['etiqueta'], $rev2['estado_gestion'], $rev2['externo_disponibilidad'], $rev2['stock_actual']);
        // dump($stock_antigua);
        if ($value['producto'] == 0) {
            $referencia = Db::getInstance()->ExecuteS('select apa.id_product, apa.id_product_attribute from aalv_combinaciones_import aci
                            left join aalv_product_attribute apa on aci.id_product_attribute = apa.id_product_attribute
                            where aci.id_articulo = '.$value['id_articulo']);
        } else {
            $referencia = Db::getInstance()->ExecuteS('select id_product, 0 as id_product_attribute from aalv_combinacionunica_import aci where id_articulo = '.$value['id_articulo']);
        }
        // dump($referencia);
        if (count($referencia) == 0 || ! isset($referencia[0]['id_product'])) {
            dump('REVISAR EL');
            dump($value);
            exit();
        }

        if ($rev2['estado_gestion'] == 0) {
            StockAvailable::setQuantity($referencia[0]['id_product'], $referencia[0]['id_product_attribute'], 0, 1, false);
        } else {
            StockAvailable::setQuantity($referencia[0]['id_product'], $referencia[0]['id_product_attribute'], $stock_antigua, 1, false);
        }
        Db::getInstance()->execute("UPDATE `aalv_repositorio_stock` SET
                                            `quantity`='".$rev2['stock_actual']."',
                                            `quantity_pocomaco`='".$rev2['stock_almacen']."'
                                        WHERE
                                            `id_product`=".$referencia[0]['id_product'].'
                                            and `id_product_attribute`='.$referencia[0]['id_product_attribute']);

        $product = new Product($referencia[0]['id_product']);
        $product->update();
        Db::getInstance()->Execute('INSERT INTO aalv_alsernet_cache_producto values (NULL, '.$referencia[0]['id_product'].')');
        Product::alsernetNewVisibilidad($value['id_articulo']);

    } else {
        echo '.';
        $nn++;
        if ($nn == 100) {
            $suma = $suma + 100;
            echo ' '.$suma.'/'.$total;
            echo "\n";
            $nn = 0;
        }
        // echo "Para el id_articulo ".$value['id_articulo']." todos los valores coinciden.<br>";
    }

}

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
