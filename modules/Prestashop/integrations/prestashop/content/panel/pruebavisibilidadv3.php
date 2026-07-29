<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

$id_articulo = 47424;

function AlsernetNewVisibilidad($id_articulo)
{

    $datos = Db::getInstance()->getRow('SELECT
                                            *,
                                            0 AS id_product_attribute,
                                            1 AS es_simpe
                                        FROM
                                            aalv_combinacionunica_import aci
                                        WHERE
                                            id_articulo ='.$id_articulo);

    if (! $datos) {
        $datos = Db::getInstance()->getRow('SELECT
                                                apa.id_product,
                                                0 AS es_simple,
                                                aci.*
                                            FROM
                                                aalv_combinaciones_import aci
                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                            WHERE
                                                aci.id_articulo ='.$id_articulo);
    }

    // if ($datos['es_simpe']) {
    $product = new Product($datos['id_product']);
    // }

    if ($datos['estado_gestion'] != 0) {

        // Validamos si tiene la etiqueta OCULTO WEB
        $array = explode(', ', $datos['etiqueta']);

        if (in_array('OCULTO WEB', $array)) {
            // Si tiene la etiqueta, forzamos el Stock a 0
            StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], 0, 1, false);

            if ($datos['es_simpe']) {
                $product->visibility = 'none';
            }
        } else {
            $repositorio_stock = Db::getInstance()->getRow('SELECT * FROM aalv_repositorio_stock ars WHERE id_product_attribute = '.$datos['id_product_attribute'].' AND id_product = '.$datos['id_product']);

            $stock = controlStock($datos['etiqueta'], $datos['estado_gestion'], $datos['externo_disponibilidad'], $repositorio_stock['quantity']);

            // Si no se ecuenta, sigue todo normal
            StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], $stock, 1, false);

            if ($datos['es_simpe']) {
                $product->visibility = 'both';
            }
        }
        if ($datos['es_simpe']) {
            $product->active = 1;
        }
    } else {
        // Entonces el producto esta extinto en gestion

        // Actualizamos su Stock a 0, lo forzamos
        StockAvailable::setQuantity($datos['id_product'], $datos['id_product_attribute'], 0, 1, false);

        if ($datos['es_simpe']) {
            $product->visibility = 'none';
            $product->active = 0;
        }
    }
    if ($datos['es_simpe']) {
        $product->save();
    } else {
        // buscamos que el stock de todas las combinaciones esten a 0
        $cero_stock = Db::getInstance()->getRow('SELECT sum(quantity) AS quantity FROM aalv_stock_available asa WHERE id_product = '.$datos['id_product'].' AND id_product_attribute != 0');

        if ((int) $cero_stock['quantity'] == 0) {

            $product->visibility = 'none';
            $product->active = 1;

        } if ((int) $cero_stock['quantity'] > 0) {

            $product->active = 1;
            $product->visibility = 'both';
        }
        $product->save();
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
