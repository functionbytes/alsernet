<?php

class v_sinc_loteClass
{
    public function Procesar_v_sinc_lote($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');
            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                $codlote = $data['codlote'];
                $idproduct = ''.Db::getInstance()->getValue("SELECT id_product from aalv_product where reference='".$codlote."'");

                if ($idproduct != '') {

                    $idbundleproduct = ''.Db::getInstance()->getValue('SELECT id_wk_bundle_product FROM aalv_wk_bundle_product WHERE id_ps_product='.$idproduct);
                    if ($idbundleproduct == '') {
                        // crear en aalv_wk_bundle_product
                        Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_product(id_ps_product, price_type, tax_type, discount, price, decrease_quantity) VALUES ('.$idproduct.',1,1,0,0,1)');
                        $idbundleproduct = Db::getInstance()->Insert_ID();
                        Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_product_shop(id_wk_bundle_product, id_shop) VALUES ('.$idbundleproduct.',1)');
                        Db::getInstance()->Execute('INSERT INTO aalv_lote_import(idlote, bundle_product) VALUES ('.$data['idlote'].','.$idbundleproduct.')');
                    }
                }
            } else {
            }

            // Confirmar la transacción manualmente
            Db::getInstance()->execute('COMMIT');

            return 1;
        } catch (Exception $e) {
            // Revertir la transacción manualmente en caso de error
            Db::getInstance()->execute('ROLLBACK');

            echo 'Error: '.$e->getMessage();
        }
    }
}
