<?php

include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');

$producto_simples = Db::getInstance()->ExecuteS('   SELECT
                                                        id_product,
                                                        etiqueta,
                                                        estado_gestion,
                                                        externo_disponibilidad,
                                                        0 AS id_product_attribute
                                                    FROM
                                                        aalv_combinacionunica_import');
$disabled = 0;
$nn = 0;
foreach ($producto_simples as $value) {
    $product = new Product($value['id_product']);
    // Validamos que el producto este activo en Gestion
    if ($value['estado_gestion'] != 0) {

        // Validamos si tiene la etiqueta OCULTO WEB
        $array = explode(', ', $value['etiqueta']);

        if (in_array('OCULTO WEB', $array)) {
            // Si tiene la etiqueta, forzamos el Stock a 0
            StockAvailable::setQuantity($value['id_product'], $value['id_product_attribute'], 0, 1, false);
            if ($product->visibility == 'both') {
                $product->visibility = 'none';
            }
        } else {
            $repositorio_stock = Db::getInstance()->getRow('SELECT * FROM aalv_repositorio_stock ars WHERE id_product_attribute = '.$value['id_product_attribute'].' AND id_product = '.$value['id_product']);

            $stock = controlStock($value['etiqueta'], $value['estado_gestion'], $value['externo_disponibilidad'], $repositorio_stock['quantity']);
            // Si no se ecuenta, sigue todo normal
            StockAvailable::setQuantity($value['id_product'], $value['id_product_attribute'], $stock, 1, false);
            if ($product->visibility == 'none') {
                $product->visibility = 'both';
            }
        }
        if (! $product->active) {
            dump('Activo => '.$value['id_product']);
            $product->active = true;
            $nn++;
        }
    } else {
        // Entonces el producto esta extinto en gestion

        // Actualizamos su Stock a 0, lo forzamos
        StockAvailable::setQuantity($value['id_product'], $value['id_product_attribute'], 0, 1, false);

        if ($product->visibility == 'both') {
            $product->visibility = 'none';
        }

        if ($product->active) {
            $product->active = false;
            $nn++;
            dump('Desactivado => '.$value['id_product']);
        }

    }
    // dump("producto update => ".$value['id_product']);
    $product->update();

    if ($nn == 200) {
        dump('listo');
        break;
        exit();
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
