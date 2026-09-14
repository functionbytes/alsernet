<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function addsql($texto)
{
    $stdout = fopen(dirname(__FILE__).'/catprodvalidar.txt', 'a');
    fwrite($stdout, $texto);
    fwrite($stdout, "\n");
    fclose($stdout);
}

function getConOutOfStock($etiquetas, $estado_gestion, $externo_disponibilidad)
{

    echo $etiquetas.' '.$estado_gestion.' '.$externo_disponibilidad.'<br/>';

    if ($etiquetas != '') {
        $etiquetasarray = explode(',', $etiquetas);

        foreach ($etiquetasarray as $key => $value) {
            $etiquetasarray[$key] = trim($value);
        }

        if (count($etiquetasarray) > 0) {
            $etiquetasstock = Db::getInstance()->ExecuteS('SELECT etiqueta FROM aalv_etiqueta_stock');
            foreach ($etiquetasstock as $etistock) {
                $existeetiqueta = in_array($etistock['etiqueta'], $etiquetasarray);
                if ($existeetiqueta) {
                    return 2; // predeterminado
                }
            }
        }
    }

    if (($externo_disponibilidad != '1') && ($externo_disponibilidad != '')) {
        return 2;
    }

    if (($estado_gestion != '1') && ($estado_gestion != '')) {
        return 2;
    }

    return 1;

}

function ocultarveranoinvierno($etiquetas)
{

    if ($etiquetas != '') {
        $etiquetasarray = explode(',', $etiquetas);

        foreach ($etiquetasarray as $key => $value) {
            $etiquetasarray[$key] = trim($value);
        }

        if (count($etiquetasarray) > 0) {

            $year = date('Y');
            $fromwinterhidden = $year.'-04-01';
            $towinterhidden = $year.'-08-15';
            $fromsummerhidden = $year.'-10-01';
            $tosummerhidden = (((int) $year) + 1).'-02-16';

            $now = date('Y-m-d');

            if (in_array('TEMPORADA_INVIERNO', $etiquetasarray)) {
                if (($now >= $fromwinterhidden) && ($now <= $towinterhidden)) {

                    return true;
                } else {

                    return false;
                }

            }

            if (in_array('TEMPORADA_VERANO', $etiquetasarray)) {

                if (($now >= $fromsummerhidden) && ($now <= $tosummerhidden)) {

                    return true;
                } else {

                    return false;
                }
            }

            return false;
        }
    }

    return false;

}

function recalculateStockByConcicionOutOfStock($id_product)
{

    $existecombunica = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_product='.$id_product);

    if ($existecombunica == '') {
        // tiene atributos
        $idpattrs = Db::getInstance()->ExecuteS('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$id_product);

        if ($idpattrs) {

            foreach ($idpattrs as $idpattr) {

                $existeorigen = ''.Db::getInstance()->getValue('SELECT id_origen FROM aalv_combinaciones_import where id_product_attribute='.$idpattr['id_product_attribute']);

                if ($existeorigen != '') {

                    $etiquetas = ''.Db::getInstance()->getValue('SELECT etiqueta FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);
                    $estado_gestion = ''.Db::getInstance()->getValue('SELECT estado_gestion FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);
                    $externo_disponibilidad = ''.Db::getInstance()->getValue('SELECT externo_disponibilidad FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);

                    $outofstock = getConOutOfStock($etiquetas, $estado_gestion, $externo_disponibilidad);

                    if ($outofstock == 1) { // tema stock 999999

                        if (ocultarveranoinvierno($etiquetas)) {
                            StockAvailable::setQuantity($id_product, $idpattr['id_product_attribute'], 0, 1, false);
                        } else {
                            StockAvailable::setQuantity($id_product, $idpattr['id_product_attribute'], 999999, 1, false);
                        }

                    } else {
                        // si es 2, recuperar de repositorio
                        $cantidad = ''.Db::getInstance()->getValue('select quantity from aalv_repositorio_stock where id_product='.$id_product.' and id_product_attribute='.$idpattr['id_product_attribute']);
                        if ($cantidad != '') {

                            $cantidad = (int) $cantidad;
                            if ($cantidad < 0) {
                                $cantidad = 0;
                            }

                            if (ocultarveranoinvierno($etiquetas)) {
                                StockAvailable::setQuantity($id_product, $idpattr['id_product_attribute'], 0, 1, false);
                            } else {
                                StockAvailable::setQuantity($id_product, $idpattr['id_product_attribute'], (int) $cantidad, 1, false);
                            }

                            // $this->setQuantity($id_product, $idpattr["id_product_attribute"], (int)$cantidad);
                        }

                        if ($estado_gestion == '0') {
                            StockAvailable::setQuantity($id_product, $idpattr['id_product_attribute'], 0, 1, false);
                        }

                    }
                } else {
                    // PrestaShopLogger::addLog(" Pasa productos no provenientes importacion");
                    return 0;
                }
            }

            return 1;
        } else {
            // productos no provenientes importacion

            // PrestaShopLogger::addLog(" Pasa productos no provenientes importacion");
            return 0;
        }
    } else {
        $etiquetas = ''.Db::getInstance()->getValue('SELECT etiqueta FROM aalv_combinacionunica_import WHERE id_product='.$id_product);
        $estado_gestion = ''.Db::getInstance()->getValue('SELECT estado_gestion FROM aalv_combinacionunica_import WHERE id_product='.$id_product);
        $externo_disponibilidad = ''.Db::getInstance()->getValue('SELECT externo_disponibilidad FROM aalv_combinacionunica_import WHERE id_product='.$id_product);

        $outofstock = getConOutOfStock($etiquetas, $estado_gestion, $externo_disponibilidad);

        echo 'oosstock '.$outofstock;

        if ($outofstock == 1) { // tema stock 999999

            // $this->setQuantity($id_product, 0, 999999);

            if (ocultarveranoinvierno($etiquetas)) {
                StockAvailable::setQuantity($id_product, 0, 0, 1, false);
            } else {
                StockAvailable::setQuantity($id_product, 0, 999999, 1, false);
            }

        } else {
            // si es 2, recuperar de repositorio
            $cantidad = ''.Db::getInstance()->getValue('select quantity from aalv_repositorio_stock where id_product='.$id_product.' and id_product_attribute=0');
            if ($cantidad != '') {

                $cantidad = (int) $cantidad;
                if ($cantidad < 0) {
                    $cantidad = 0;
                }

                // $this->setQuantity($id_product, 0, (int)$cantidad);

                if (ocultarveranoinvierno($etiquetas)) {
                    StockAvailable::setQuantity($id_product, 0, 0, 1, false);
                } else {
                    StockAvailable::setQuantity($id_product, 0, (int) $cantidad, 1, false);
                }

            }

            if ($estado_gestion == '0') {
                StockAvailable::setQuantity($id_product, 0, 0, 1, false);
            }

            $nolote = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$id_product);

            if ($nolote != '') {
                StockAvailable::setQuantity($id_product, 0, 999999, 1, false);
            }

        }

        return 1;
    }

}

function visibilidadLote($idproduct)
{

    // ver si tienen stock las partes
    $idbundleproduct = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$idproduct);
    $productoslote = Db::getInstance()->ExecuteS('SELECT id_product FROM aalv_wk_bundle_sub_product where id_wk_bundle_section in (SELECT id_wk_bundle_section FROM aalv_wk_bundle_section_map where id_wk_bundle_product='.$idbundleproduct.')');

    foreach ($productoslote as $productosloteitem) {
        $stock = StockAvailable::getQuantityAvailableByProduct($productosloteitem['id_product']);
        if ($stock <= 0) {
            return 'none';
        }
    }

    return 'both';

}

function productopertenecelote($idproduct)
{

    $lotes = Db::getInstance()->ExecuteS('SELECT id_ps_product FROM aalv_wk_bundle_product a inner join aalv_wk_bundle_section_map b on a.id_wk_bundle_product=b.id_wk_bundle_product where b.id_wk_bundle_section in (SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product where id_product='.$idproduct.' union SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product_attribute where id_product='.$idproduct.')');

    $products = [];

    foreach ($lotes as $lote) {
        $products[] = $lote['id_ps_product'];
    }

    return $products;

}

$idproduct = 55917;

$esimportado = recalculateStockByConcicionOutOfStock($idproduct);

if ($esimportado == 1) {

    Db::getInstance()->Execute('UPDATE aalv_product SET out_of_stock=2 where id_product='.$idproduct);
    StockAvailable::setProductOutOfStock($idproduct, 2);

    $stockdisponible = StockAvailable::getQuantityAvailableByProduct($idproduct);

    if ($stockdisponible <= 0) {

        echo 'llega';

        $visibilidad = 'none';
    } else {
        $visibilidad = 'both';

        $nolote = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$idproduct);

        if ($nolote == '') {

            $specific_price = Db::getInstance()->getValue('SELECT ifnull(sum(price),0) FROM aalv_specific_price WHERE id_product='.$idproduct);
            if ($specific_price <= 0) {
                $visibilidad = 'none';
            }
        } else {
            // es lote
            echo 'pasa';
            $prodsinlote = productopertenecelote($idproduct);
            foreach ($prodsinlote as $prodinlote) {
                $pinlote = new Product($prodinlote);
                $pinlote->update();
            }

            $visibilidad = visibilidadLote($idproduct);

        }

    }

    Db::getInstance()->Execute("Update aalv_product set visibility='".$visibilidad."' where id_product=".$idproduct);
    Db::getInstance()->Execute("Update aalv_product_shop set visibility='".$visibilidad."' where id_product=".$idproduct);

    echo $visibilidad;

}
