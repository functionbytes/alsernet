<?php

include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');

// $id_product = 65148;

$datos_productos = Db::getInstance()->ExecuteS('SELECT
                                                    apa.id_product
                                                FROM
                                                    aalv_combinaciones_import aci
                                                    LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                WHERE
                                                    apa.id_product IS NOT NULL
                                                    GROUP BY apa.id_product
                                                    order by apa.id_product asc');
// $nn = 0;
foreach ($datos_productos as $key) {
    $id_product = $key['id_product'];

    $producto_combinacion = Db::getInstance()->ExecuteS('   SELECT
                                                                aci.id_articulo,
                                                                aci.id_product_attribute,
                                                                apa.id_product
                                                            FROM
                                                                aalv_combinaciones_import aci
                                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                            WHERE
                                                                apa.id_product = '.$id_product);

    $disabled = 0;
    $stock_oculto = 0;
    foreach ($producto_combinacion as $value) {
        $datos = Db::getInstance()->getRow('SELECT * FROM aalv_combinaciones_import WHERE id_articulo = '.$value['id_articulo'].' AND id_product_attribute ='.$value['id_product_attribute']);

        // Validamos que el producto este activo en Gestion
        if ($datos['estado_gestion'] != 0) {

            // Validamos si tiene la etiqueta OCULTO WEB
            $array = explode(', ', $datos['etiqueta']);

            if (in_array('OCULTO WEB', $array)) {
                // Si tiene la etiqueta, forzamos el Stock a 0
                StockAvailable::setQuantity($id_product, $value['id_product_attribute'], 0, 1, false);
                $stock_oculto = $stock_oculto + 0;
            } else {
                $repositorio_stock = Db::getInstance()->getRow('SELECT * FROM aalv_repositorio_stock ars WHERE id_product_attribute = '.$value['id_product_attribute'].' AND id_product = '.$id_product);

                $stock = controlStock($datos['etiqueta'], $datos['estado_gestion'], $datos['externo_disponibilidad'], $repositorio_stock['quantity']);
                // Si no se ecuenta, sigue todo normal
                StockAvailable::setQuantity($id_product, $value['id_product_attribute'], $stock, 1, false);
                if ($stock > 0) {
                    $stock_oculto = $stock_oculto + $stock;
                } else {
                    $stock_oculto = $stock_oculto + 0;
                }

            }
        } else {
            // Entonces el producto esta extinto en gestion

            // Contamos los extintos para desactivar el producto
            $disabled++;
            $stock_oculto = $stock_oculto + 0;
            // Actualizamos su Stock a 0, lo forzamos
            StockAvailable::setQuantity($id_product, $value['id_product_attribute'], 0, 1, false);
        }
    }

    // Ahora validamos si todos estan desactivados
    $all_attributes_disabled = Db::getInstance()->getValue('SELECT
                                                                count(*)
                                                            FROM
                                                                aalv_combinaciones_import aci
                                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                                            WHERE
                                                                aci.estado_gestion = 0
                                                                AND apa.id_product = '.$id_product);
    $product = new Product($id_product);
    // dump($id_product);

    if ($disabled != 0 && (int) $all_attributes_disabled == $disabled && count($producto_combinacion) == $disabled) {
        // Si es TRUE tenemos que desacticar el producto
        if ($product->active) {
            $product->active = false;
            dump('Desactivado => '.$id_product);
            // $nn++;
        }
    } else {
        // Si es FALSE lo tenemos que dejar activo

        if ($stock_oculto == 0) {
            if ($product->visibility == 'both') {
                // dump("none => ".$id_product);
                $product->visibility = 'none';
            }
        } else {
            if ($product->visibility == 'none') {
                // dump("both => ".$id_product);
                $product->visibility = 'both';
            }
        }
        if (! $product->active) {
            $product->active = true;
            // $nn++;
            dump('Activo => '.$id_product);
        }

    }
    $product->update();

    // if($nn == 200){
    //     dump('listo');
    //     break;
    //     die();
    // }
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
