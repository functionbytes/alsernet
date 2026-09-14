<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

setlocale(LC_CTYPE, 'es.UTF16');
$dbcon = connectBD();

$where = '';
$actualizar = false;
$debug = false;
$csv = false;
$test = false;
if (is_array($argv)) {
    foreach ($argv as $parametro) {
        if (is_numeric($parametro) || strpos($parametro, ',')) {
            $where = ' where id_product IN ('.$parametro.')';
        }
        if ($parametro == 'actualizar') {
            $actualizar = true;
        }
        if ($parametro == 'debug') {
            $debug = true;
        }
        if ($parametro == 'csv') {
            $csv = true;
        }
        if ($parametro == 'test') {
            $test = true;
        }
    }
}

if ($_GET['id_product']) {
    $where = ' where id_product IN ('.$_GET['id_product'].')';
}

if ($csv) {
    echo "Referencia;id_product;id_attribute;Es lote;Estado PS Prod.;Estado PS Mod.;Estado gestión;Producto activo;Modelo activo;Visibilidad Prod.;Visibilidad Mod.;Dispo. Prov. PS;Dispo. Prov.;Etiquetas Ges.;Etiquetas PS.;Stock PS;Control Stock;Stock gestión;Actualizaciones\n";
}

$query = 'SELECT id_product FROM aalv_product_import'.$where.' ORDER BY id_product DESC';

$productos = Db::getInstance()->ExecuteS($query);

foreach ($productos as $ps) {
    $stock = Db::getInstance()->ExecuteS('SELECT
                                            pa.id_product_attribute,
                                            sa.quantity,
                                            pa.reference,
                                            ap.active
                                          FROM
                                            aalv_stock_available sa
                                            LEFT JOIN aalv_product_attribute pa ON sa.id_product = pa.id_product AND sa.id_product_attribute = pa.id_product_attribute
                                            LEFT JOIN aalv_product ap on ap.id_product = pa.id_product
                                          WHERE
                                            ap.id_product = '.$ps['id_product']);
    if (count($stock) == 0) {
        // Producto
        $stock = Db::getInstance()->ExecuteS('SELECT
                                                ap.reference,
                                                asa.quantity,
                                                ap.active
                                              FROM
                                                aalv_product ap
                                                left join aalv_stock_available asa on asa.id_product = ap.id_product
                                              WHERE
                                                ap.id_product = '.$ps['id_product']);
    }

    foreach ($stock as $ps_stock) {
        $actualizo_stock = 0;
        $sql_antigua = 'SELECT
                            stoc.stock_actual,
                            prod.externo_disponibilidad,
                            prod.etiqueta,
                            prod.estado_gestion,
                            prod.activo AS producto_activo,
                            mode.activo AS modelo_activo
                        FROM
                            control_stock stoc
                            LEFT JOIN producto prod on prod.referencia = stoc.referencia
                            LEFT JOIN modelo mode on mode.id = prod.id_modelo
                        WHERE stoc.referencia = ';

        if ($ps_stock['reference'] != '') {
            $sql_antigua .= "'".$ps_stock['reference']."'";
        } else {
            PrestaShopLogger::addLog('Producto sin referencia',
                1, null, 'Product', $ps_stock['id_product'], false);

            continue;
        }

        $datos = mysqli_query($dbcon, $sql_antigua);
        if (mysqli_num_rows($datos) == 0) {
            if ($debug) {
                echo "Producto sin registros de stock en web antigua\n";
            }
            PrestaShopLogger::addLog('Producto sin registros de stock en web antigua',
                1, null, 'Product', $ps_stock['id_product'], false);
            // continue;
        }
        $web_antigua = mysqli_fetch_array($datos, MYSQLI_ASSOC);

        $TMP = '';
        $diferencia = 0;
        $product = new Product($ps['id_product']);
        $get_visibilidad_producto = Product::getVisibilidad($ps['id_product'], false, $debug) ? 1 : 0;
        $get_visibilidad_modelo = Product::getVisibilidad($ps['id_product'], $ps_stock['id_product_attribute'], $debug) ? 1 : 0;
        $control_stock = Product::controlStock($ps['id_product'], $ps_stock['id_product_attribute'], $web_antigua['stock_actual'], $debug);

        if ($test) {
            $visibilidad_web = peticionget('http://admin-new.a-alvarez.com/stock-reference/?return=visibleweb&referencia='.$ps_stock['reference']);
            $url_okitup = 'https://www.a-alvarez.com/scriptsalsernet/verify-active.php?';
            if ($ps['id_product']) {
                $url_okitup .= '&id_product='.$ps['id_product'];
            }
            if ($ps_stock['id_product_attribute']) {
                $url_okitup .= '&id_attribute='.$ps_stock['id_product_attribute'];
            }
            $visibilidad_okitup = peticionget($url_okitup);
            if ($visibilidad_web != $visibilidad_okitup) {
                echo 'No coincide en la referencia: '.$ps_stock['reference'].' (ADMIN: '.$visibilidad_web.', PS: '.$visibilidad_okitup.")\n";
            }

            continue;
        }

        $modelo_activo = Db::getInstance()->getValue('SELECT id_tot_switch_attribute_disabled
                                                      FROM aalv_tot_switch_attribute_disabled
                                                      WHERE id_product_attribute = '.$ps_stock['id_product_attribute']) ? 0 : 1;

        if ((int) $ps_stock['quantity'] != $web_antigua['stock_actual'] && $ps_stock['quantity'] != 999999) {
            $diferencia = $ps_stock['quantity'] - $web_antigua['stock_actual'];
            if ($diferencia > 0 && $ps_stock['quantity'] != 0) {
                $TMP .= 'Actualizar stock, hay una diferencia de '.$diferencia.'. ';

                if ($actualizar) {
                    $TMP .= ' => Actualizado a '.$web_antigua['stock_actual'];
                    StockAvailable::setQuantity($ps['id_product'], $ps_stock['id_product_attribute'], $web_antigua['stock_actual'], 1);
                }

            }
        }

        if ($control_stock !== false && $control_stock != $ps_stock['quantity']) {
            $TMP .= 'ERROR: El stock no es correcto. ';

            // if ($actualizar && ($control_stock >= 0 || ($web_antigua['externo_disponibilidad']==0 && $ps_stock['quantity']==999999))) {
            if ($actualizar) {
                $actualizo_stock = 1;
                $TMP .= ' => Actualizado a '.$control_stock;
                StockAvailable::setQuantity($ps['id_product'], $ps_stock['id_product_attribute'], $control_stock, 1);
                $product = new Product($ps['id_product']);
                // if ($product->quantity != $control_stock) {
                // Una vez modificado el módulo alvarezvisibilidad ya no sería necesario este paso
                Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET quantity='.$control_stock.' WHERE id_product='.$ps['id_product'].' AND id_product_attribute='.$ps_stock['id_product_attribute']);
                StockAvailable::setQuantity($ps['id_product'], $ps_stock['id_product_attribute'], $control_stock, 1);
                $TMP .= ' => Realizado UPDATE en aalv_repositorio_stock a '.$control_stock;
                // }

            }

            // $TMP .= "\n";
        }

        if ($ps_stock['id_product_attribute']) {
            // Revisamos la visibilidad del modelo asociado al producto
            $TMP_estado_anterior_modelo = $modelo_activo;
            if (is_numeric($get_visibilidad_modelo) && $get_visibilidad_modelo != $modelo_activo) {
                $TMP .= 'ERROR: La visibilidad del modelo no es correcta. ';
                if ($actualizar) {
                    if ($get_visibilidad_modelo === 1) {
                        Db::getInstance()->ExecuteS('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_shop=1 AND id_product_attribute='.$ps_stock['id_product_attribute']);
                        $combination = new Combination((int) $ps_stock['id_product_attribute']);
                        $combination->default_on = false;
                        $combination->update();

                    } else {
                        Db::getInstance()->ExecuteS('INSERT INTO aalv_tot_switch_attribute_disabled SET id_shop=1, id_product_attribute='.$ps_stock['id_product_attribute']);
                        $combination = new Combination((int) $ps_stock['id_product_attribute']);
                        echo 'Fallo => INSERT INTO aalv_tot_switch_attribute_disabled SET id_shop=1, id_product_attribute='.$ps_stock['id_product_attribute']."\n";
                        $combination->default_on = true;
                        $combination->update();

                        $id_current_default_attribute = Product::getDefaultAttribute($ps['id_product']);
                        if ($ps_stock['id_product_attribute'] == $id_current_default_attribute) {
                            // Se está desactivando un modelo Por defecto, hay que asignar otro.
                            $product->deleteDefaultAttributes();

                            // El modelo activo con más stock
                            $id_modelo_por_defecto = Product::buscaModeloPorDefecto($ps['id_product']);

                            if ($id_modelo_por_defecto) {
                                $product->setDefaultAttribute($id_modelo_por_defecto);
                                $product->update();
                            }
                        }
                    }
                    $TMP .= ' => Actualizado a '.$get_visibilidad_modelo;
                }
            }
        }

        $TMP_estado_anterior_producto = $product->active;
        if (is_numeric($get_visibilidad_producto) && $get_visibilidad_producto != $product->active) {
            $TMP .= 'ERROR: La visibilidad del producto no es correcta. ';

            if ($actualizar && ! $actualizo_stock) {
                $TMP .= ' => Actualizado a '.$get_visibilidad_producto;
                $product->active = $get_visibilidad_producto;
                $product->update();
            }

        }

        if ($ps_stock['id_product_attribute']) {
            $product_import = Db::getInstance()->ExecuteS('SELECT etiqueta,estado_gestion,activo,externo_disponibilidad
                                                      FROM aalv_combinaciones_import
                                                      WHERE id_product_attribute='.$ps_stock['id_product_attribute']);
            if ($product_import[0]['activo'] != $web_antigua['modelo_activo'] && $actualizar) {
                Db::getInstance()->ExecuteS('UPDATE aalv_combinaciones_import
                                                               SET activo='.$web_antigua['modelo_activo'].'
                                                               WHERE id_product_attribute='.$ps_stock['id_product_attribute']);

            }
            if ($product_import[0]['estado_gestion'] != $web_antigua['estado_gestion'] && $actualizar) {
                Db::getInstance()->ExecuteS('UPDATE aalv_combinaciones_import
                                                               SET estado_gestion='.$web_antigua['estado_gestion'].'
                                                               WHERE id_product_attribute='.$ps_stock['id_product_attribute']);
            }
        } else {
            $product_import = Db::getInstance()->ExecuteS('SELECT etiqueta,estado_gestion,activo,externo_disponibilidad
                                                      FROM aalv_combinacionunica_import
                                                      WHERE id_product='.$ps['id_product']);
            if ($product_import[0]['activo'] != $web_antigua['producto_activo'] && $actualizar) {
                Db::getInstance()->ExecuteS('UPDATE aalv_combinacionunica_import
                                                               SET activo='.$web_antigua['producto_activo'].'
                                                               WHERE id_product='.$ps['id_product']);

            }
            if ($product_import[0]['estado_gestion'] != $web_antigua['estado_gestion'] && $actualizar) {
                Db::getInstance()->ExecuteS('UPDATE aalv_combinacionunica_import
                                                               SET estado_gestion='.$web_antigua['estado_gestion'].'
                                                               WHERE id_product='.$ps['id_product']);

            }
        }

        if ($_GET['id_product'] || $_GET['id_attribute']) {
            if ($ps_stock['id_product_attribute']) {
                if ($_GET['id_attribute'] == $ps_stock['id_product_attribute']) {
                    ob_end_clean();
                    header('Content-type: application/json');
                    echo json_encode($get_visibilidad_modelo ? true : false);
                    exit;
                } else {
                    continue;
                }
            } elseif ($_GET['id_product']) {
                ob_end_clean();
                header('Content-type: application/json');
                echo json_encode($get_visibilidad_producto ? true : false);
                exit;
            }

        }

        if (trim($TMP) || $debug) {
            $lote = Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$ps['id_product']) ? 'Sí' : 'No';
            if ($csv) {
                echo $ps_stock['reference'].';'.
                $ps['id_product'].';'.
                $ps_stock['id_product_attribute'].';'.
                $lote.';'.
                $TMP_estado_anterior_producto.';'.
                $TMP_estado_anterior_modelo.';'.
                $web_antigua['estado_gestion'].';'.
                $web_antigua['producto_activo'].';'.
                $web_antigua['modelo_activo'].';'.
                $get_visibilidad_producto.';'.
                $get_visibilidad_modelo.';'.
                $product_import[0]['externo_disponibilidad'].';'.
                $web_antigua['externo_disponibilidad'].';'.
                $web_antigua['etiqueta'].';'.
                $product_import[0]['etiqueta'].';'.
                $ps_stock['quantity'].';'.
                $control_stock.';'.
                $web_antigua['stock_actual'].';'.
                $TMP."\n";
            } else {
                $TMP = 'Referencia      => '.$ps_stock['reference'].'
id_product      => '.$ps['id_product'].'
id_attribute    => '.$ps_stock['id_product_attribute'].'
Es lote         => '.$lote.'
Estado Prod. PS => '.$TMP_estado_anterior_producto.' ('.$get_visibilidad_producto.')
Estado Mod.  PS => '.$TMP_estado_anterior_modelo.' ('.$get_visibilidad_modelo.')
Estado gest. PS => '.$product_import[0]['estado_gestion'].'
Producto act.PS => '.$product_import[0]['activo'].'
Mod.  activo PS => '.$product_import[0]['activo'].'
Estado gestión  => '.$web_antigua['estado_gestion'].'
Producto activo => '.$web_antigua['producto_activo'].'
Modelo activo   => '.$web_antigua['modelo_activo'].'
Visibilidad Pro.=> '.$get_visibilidad_producto.'
Visibilidad Mod.=> '.$get_visibilidad_modelo.'
Dispo. Prov. PS => '.$product_import[0]['externo_disponibilidad'].'
Dispo. Prov.    => '.$web_antigua['externo_disponibilidad'].'
Etiquetas Ges.  => '.$web_antigua['etiqueta'].'
Etiquetas    PS => '.$product_import[0]['etiqueta'].'
Stock        PS => '.$ps_stock['quantity'].'
Control Stock   => '.$control_stock.'
Stock gestión   => '.$web_antigua['stock_actual'].'
'.$TMP;
                echo $TMP."\n\n";

            }
        }

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
