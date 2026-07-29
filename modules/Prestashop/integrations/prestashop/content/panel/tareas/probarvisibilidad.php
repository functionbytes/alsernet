<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getConOutOfStock($etiquetas, $estado_gestion, $externo_disponibilidad)
{

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

                echo '<br>productattr '.$idpattr['id_product_attribute'];
                $existeorigen = ''.Db::getInstance()->getValue('SELECT id_origen FROM aalv_combinaciones_import where id_product_attribute='.$idpattr['id_product_attribute']);

                if ($existeorigen != '') {

                    $etiquetas = ''.Db::getInstance()->getValue('SELECT etiqueta FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);
                    $estado_gestion = ''.Db::getInstance()->getValue('SELECT estado_gestion FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);
                    $externo_disponibilidad = ''.Db::getInstance()->getValue('SELECT externo_disponibilidad FROM aalv_combinaciones_import WHERE id_product_attribute='.$idpattr['id_product_attribute']);

                    $outofstock = getConOutOfStock($etiquetas, $estado_gestion, $externo_disponibilidad);

                    if ($outofstock == 1) { // tema stock 999999

                        if (ocultarveranoinvierno($etiquetas)) {
                            echo '<br/>Stock 0 por temporada';
                        } else {
                            echo '<br/>stock 99999';
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
                                echo '<br/>Stock 0 por temporada bis';
                            } else {
                                echo '<br/>stock cantidad '.$cantidad;
                            }

                            // $this->setQuantity($id_product, $idpattr["id_product_attribute"], (int)$cantidad);
                        }

                        if ($estado_gestion == '0') {
                            echo '<br/>Stock 0 por estado gestion';
                        }

                    }
                } else {
                    // PrestaShopLogger::addLog(" Pasa productos no provenientes importacion");
                    // echo "saldria";
                    // return 0;
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

        if ($outofstock == 1) { // tema stock 999999

            // $this->setQuantity($id_product, 0, 999999);

            if (ocultarveranoinvierno($etiquetas)) {
                // StockAvailable::setQuantity($id_product, 0, 0, 1, false);
            } else {
                // StockAvailable::setQuantity($id_product, 0, 999999, 1, false);
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
                    // StockAvailable::setQuantity($id_product, 0, 0, 1, false);
                } else {
                    // StockAvailable::setQuantity($id_product, 0, (int)$cantidad, 1, false);
                }

            }

            if ($estado_gestion == '0') {
                // StockAvailable::setQuantity($id_product, 0, 0, 1, false);
            }

            $nolote = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$id_product);

            if ($nolote != '') {
                // StockAvailable::setQuantity($id_product, 0, 999999, 1, false);
            }

        }

        return 1;
    }

}

recalculateStockByConcicionOutOfStock(46140);
