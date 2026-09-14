<?php

class v_sinc_tarifaloteClass
{
    public function Procesar_v_sinc_tarifalote($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');
            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                $idtarifalote = ''.Db::getInstance()->getValue('SELECT idtarifalote FROM aalv_tarifalote_import where idtarifalote='.$data['idtarifalote']);
                if ($idtarifalote == '') {
                    Db::getInstance()->Execute('INSERT INTO aalv_tarifalote_import(idtarifalote, idllote, estado, idttarifa, precio, precio_con_impuestos) VALUES ('.$data['idtarifalote'].','.$data['idllote'].','.$data['estado'].','.$data['idregpais'].','.$data['precio'].",'".$data['precio_con_impuestos']."')");
                } else {
                    Db::getInstance()->Execute('UPDATE aalv_tarifalote_import SET idllote='.$data['idllote'].',estado='.$data['estado'].',idttarifa='.$data['idregpais'].',precio='.$data['precio'].",precio_con_impuestos='".$data['precio_con_impuestos']."' WHERE idtarifalote=".$data['idtarifalote']);
                }

                // averiguar idproduct
                $idproduct = ''.Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_wk_bundle_product WHERE id_wk_bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote='.$data['idllote'].'))');
                if ($idproduct != '') {

                    $idlote = ''.Db::getInstance()->getValue('SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote='.$data['idllote'].'))');

                    if ($idlote != '') {
                        $paisPS = _TARIFA_LOTE_PAIS_[$data['idregpais']];

                        $preciotarifa = Db::getInstance()->getValue('SELECT sum(precio) FROM aalv_tarifalote_import where idttarifa='.$data['idregpais'].' and idllote in (select idllote from aalv_llote_import where bundle_section in (select id_wk_bundle_section from aalv_wk_bundle_section_map where id_wk_bundle_product in (select bundle_product from aalv_lote_import where idlote='.$idlote.')))');
                        $preciotarifa = filter_var($preciotarifa, FILTER_VALIDATE_FLOAT);

                        // sendmailPruebas('tipo => '.$tipo.' idregpais => '.$data["idregpais"].' preciotarifa => '.round($preciotarifa,6));
                        if ($tipo == 1) {
                            if ($data['idregpais'] == 1) {
                                $product = new Product($idproduct);
                                $product->id_tax_rules_group = getIdIva();
                                $product->price = round($preciotarifa, 6);
                                $product->update();
                                // Db::getInstance()->Execute("update aalv_product_shop set id_tax_rules_group = ".PORC_IVA_21.", price=".round($preciotarifa,6)." where id_product=".$idproduct);
                                // Db::getInstance()->Execute("update aalv_product set id_tax_rules_group = ".PORC_IVA_21.", price=".round($preciotarifa,6)." where id_product=".$idproduct);
                            } else {
                                $validar_existencia = ''.Db::getInstance()->getValue('SELECT * FROM aalv_specific_price WHERE id_product = '.$idproduct.' AND id_country = '.$paisPS.' AND price = '.$preciotarifa);
                                if ($validar_existencia == '') {
                                    Db::getInstance()->Execute('INSERT INTO aalv_specific_price (`id_specific_price_rule`, `id_cart`, `id_product`, `id_shop`, `id_shop_group`, `id_currency`, `id_country`, `id_group`, `id_customer`, `id_product_attribute`, `price`, `from_quantity`, `reduction`, `reduction_tax`, `reduction_type`, `from`, `to`) VALUES
                                    (0,0,'.$idproduct.',0,0,0,'.$paisPS.',0,0,0,'.$preciotarifa.",1,0.00,0,'amount',NOW(),'0000-00-00 00:00:00')");
                                }
                            }
                        }

                        if ($tipo == 2) {
                            if ($data['idregpais'] == 1) {
                                $product = new Product($idproduct);
                                $product->id_tax_rules_group = getIdIva();
                                $product->price = round($preciotarifa, 6);
                                $product->update();
                                // Db::getInstance()->Execute("update aalv_product_shop set id_tax_rules_group = ".PORC_IVA_21.", price=".round($preciotarifa,6)." where id_product=".$idproduct);
                                // Db::getInstance()->Execute("update aalv_product set id_tax_rules_group = ".PORC_IVA_21.", price=".round($preciotarifa,6)." where id_product=".$idproduct);
                            } else {
                                $validar_existencia = ''.Db::getInstance()->getValue('SELECT * FROM aalv_specific_price WHERE id_product = '.$idproduct.' AND id_country = '.$paisPS);
                                if ($validar_existencia == '') {
                                    Db::getInstance()->Execute('UPDATE aalv_specific_price SET price = '.$preciotarifa.', `from` = NOW() WHERE id_product = '.$idproduct.' AND id_country = '.$paisPS);
                                }
                            }
                        }

                        Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=6 and id_product='.$idproduct);
                        $idfeaturevalue = crearFeatureValue(6, '1', 0);
                        if ($idfeaturevalue != 0) {
                            $product = new Product($idproduct);
                            $product->addFeatureProductImport($idproduct, 6, $idfeaturevalue);
                        }

                        Db::getInstance()->Execute('REPLACE INTO aalv_appagebuilder_page(id_product, id_category, page, id_shop) VALUES ('.$idproduct.",0,'detail1988211104',1)");
                    }
                }
            } else {
                // borrado

                $idllote = ''.Db::getInstance()->getValue('SELECT idllote FROM aalv_tarifalote_import where idtarifalote='.$fila);

                $idproduct = ''.Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_wk_bundle_product WHERE id_wk_bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote='.$idllote.'))');
                if ($idproduct != '') {

                    $idlote = ''.Db::getInstance()->getValue('SELECT idlote FROM aalv_lote_import WHERE bundle_product in (SELECT id_wk_bundle_product FROM aalv_wk_bundle_section_map WHERE id_wk_bundle_section in (SELECT bundle_section FROM aalv_llote_import WHERE idllote='.$idllote.'))');

                    if ($idlote != '') {

                        // borrar antes del cálculo esa tarifa
                        Db::getInstance()->Execute('DELETE FROM aalv_tarifalote_import where idtarifalote='.$fila);

                        $preciotarifa = Db::getInstance()->getValue('SELECT sum(precio) FROM aalv_tarifalote_import where idttarifa=1 and idllote in (select idllote from aalv_llote_import where bundle_section in (select id_wk_bundle_section from aalv_wk_bundle_section_map where id_wk_bundle_product in (select bundle_product from aalv_lote_import where idlote='.$idlote.')))');

                        Db::getInstance()->Execute('update aalv_product_shop set id_tax_rules_group = '.getIdIva().', price='.round($preciotarifa, 6).' where id_product='.$idproduct);
                        Db::getInstance()->Execute('update aalv_product set id_tax_rules_group = '.getIdIva().', price='.round($preciotarifa, 6).' where id_product='.$idproduct);

                        Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=6 and id_product='.$idproduct);
                        $idfeaturevalue = crearFeatureValue(6, '1', 0);
                        if ($idfeaturevalue != 0) {
                            $product = new Product($idproduct);
                            $product->addFeatureProductImport($idproduct, 6, $idfeaturevalue);
                        }

                        Db::getInstance()->Execute('REPLACE INTO aalv_appagebuilder_page(id_product, id_category, page, id_shop) VALUES ('.$idproduct.",0,'detail1988211104',1)");
                    }
                }
            }

            return 1;
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
