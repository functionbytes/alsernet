<?php

class v_sinc_llloteClass
{
    public function Procesar_v_sinc_lllote($data, $fila, $tipo)
    {
        try {
            // Iniciar la transacción manualmente
            Db::getInstance()->execute('START TRANSACTION');

            if ($tipo <= 2) {

                if (! $data) {
                    // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
                    return 1;
                }

                $id_bundle_sub_product = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote='.$data['idlllote']);
                if (''.$id_bundle_sub_product == '') {
                    // crear

                    $id_bundle_sub_product = 0;
                    $id_bundle_sub_product_attribute = 0;

                    $idarticulo = $data['idarticulo'];
                    $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo.' UNION select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo.')');

                    // ver si existe seccion producto

                    $bundlesection = ''.Db::getInstance()->getValue('select bundle_section from aalv_llote_import where idllote='.$data['idllote']);

                    if ($bundlesection != '') {

                        $id_bundle_sub_product = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product from aalv_wk_bundle_sub_product where id_wk_bundle_section='.$bundlesection.' and id_product='.$idproduct);

                        if ($id_bundle_sub_product == '') {
                            Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_sub_product(id_wk_bundle_section, id_product, id_shop, active, for_bundle_only) VALUES ('.$bundlesection.','.$idproduct.',1,1,0)');
                            $id_bundle_sub_product = Db::getInstance()->Insert_ID();
                        }

                        $idproductattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo);

                        if ($idproductattribute == '') {
                            $idproductattribute = '0';
                        }

                        $defaultattr = '0';
                        if ($idproductattribute != '0') {
                            $defaultattr = ''.Db::getInstance()->getValue('SELECT default_on FROM aalv_product_attribute where id_product_attribute = '.$idproductattribute);
                            if ($defaultattr == '') {
                                $defaultattr = '0';
                            }
                        }

                        Db::getInstance()->Execute('UPDATE aalv_wk_bundle_section SET min_quantity = '.$data['unidades'].' WHERE id_wk_bundle_section = '.$bundlesection);

                        $stock_producto = Db::getInstance()->ExecuteS('SELECT quantity FROM aalv_stock_available asa WHERE id_product = '.$idproduct.' AND id_product_attribute = '.$idproductattribute);

                        Db::getInstance()->Execute('INSERT INTO aalv_wk_bundle_sub_product_attribute(id_wk_bundle_section, id_product, id_product_attribute, quantity, normal_stock, default_attr) VALUES ('.$bundlesection.','.$idproduct.','.$idproductattribute.','.$stock_producto[0]['quantity'].',0,'.$defaultattr.')');

                        $id_bundle_sub_product_attribute = Db::getInstance()->Insert_ID();

                        // $unidades = (int)$data["unidades"];
                        // if ($unidades>1){

                        //     Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute SET quantity=".$unidades." WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);

                        // }
                        // 20230906 - Esto no hace falta ya que lo inserta con 0 Por defecto
                        // else{
                        //     Db::getInstance()->Execute("UPDATE aalv_wk_bundle_sub_product_attribute SET quantity=0 WHERE id_sub_product_attribute=".$id_bundle_sub_product_attribute);
                        // }

                        Db::getInstance()->Execute('INSERT INTO aalv_lllote_import(idlllote, idarticulo, id_bundle_sub_product, id_bundle_sub_product_attribute) VALUES ('.$data['idlllote'].','.$idarticulo.','.$id_bundle_sub_product.','.$id_bundle_sub_product_attribute.')');
                    }
                } else {
                    // modificar

                    $id_bundle_sub_product_attribute = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product_attribute FROM aalv_lllote_import WHERE idlllote='.$data['idlllote']);

                    $idarticulo = $data['idarticulo'];
                    $idproduct = Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_articulo='.$idarticulo.' UNION select id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo.')');

                    $idproductattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_articulo = '.$idarticulo);

                    if ($idproductattribute == '') {
                        $idproductattribute = '0';
                    }

                    $defaultattr = '0';
                    if ($idproductattribute != '0') {
                        $defaultattr = ''.Db::getInstance()->getValue('SELECT default_on FROM aalv_product_attribute where id_product_attribute = '.$idproductattribute);
                        if ($defaultattr == '') {
                            $defaultattr = '0';
                        }
                    }

                    Db::getInstance()->Execute('REPLACE INTO aalv_wk_bundle_sub_product(id_bundle_sub_product, id_wk_bundle_section, id_product, id_shop, active, for_bundle_only) VALUES ('.$id_bundle_sub_product.', '.$bundlesection.','.$idproduct.',1,1,0)');

                    Db::getInstance()->Execute('REPLACE INTO aalv_wk_bundle_sub_product_attribute(id_sub_product_attribute, id_wk_bundle_section, id_product, id_product_attribute, quantity, normal_stock, default_attr) VALUES ('.$id_bundle_sub_product_attribute.', '.$bundlesection.','.$idproduct.','.$idproductattribute.','.$stock_producto[0]['quantity'].',0,'.$defaultattr.')');

                    $unidades = (int) $data['unidades'];
                    if ($unidades > 1) {

                        Db::getInstance()->Execute('UPDATE aalv_wk_bundle_sub_product_attribute SET quantity='.$unidades.' WHERE id_sub_product_attribute='.$id_bundle_sub_product_attribute);
                    }

                    Db::getInstance()->Execute('UPDATE aalv_lllote_import set idarticulo='.$idarticulo.' where idlllote='.$data['idlllote']);
                }
            } else {

                // recuperar
                $id_bundle_sub_product = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product FROM aalv_lllote_import WHERE idlllote='.$fila);
                $id_bundle_sub_product_attribute = ''.Db::getInstance()->getValue('SELECT id_bundle_sub_product_attribute FROM aalv_lllote_import WHERE idlllote='.$fila);

                // borrar de la tabla

                if ($id_bundle_sub_product_attribute != '') {

                    // coger producto y atributo
                    $idprodaux = Db::getInstance()->getValue('SELECT id_product FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute='.$id_bundle_sub_product_attribute);
                    $idprodattributeaux = Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute='.$id_bundle_sub_product_attribute);

                    $idsectionaux = Db::getInstance()->getValue('SELECT id_wk_bundle_section FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute='.$id_bundle_sub_product_attribute);

                    Db::getInstance()->Execute('DELETE FROM aalv_wk_bundle_sub_product_attribute WHERE id_sub_product_attribute='.$id_bundle_sub_product_attribute);

                    // ver si no queda ningún producto
                    $existe = Db::getInstance()->getValue('SELECT count(*) FROM aalv_wk_bundle_sub_product_attribute WHERE id_wk_bundle_section = '.$idsectionaux.' and id_product= '.$idprodaux);
                    if ($existe == 0) {
                        // borrar tambien $id_bundle_sub_product
                        if ($id_bundle_sub_product != '') {
                            Db::getInstance()->Execute('DELETE FROM aalv_wk_bundle_sub_product WHERE id_bundle_sub_product='.$id_bundle_sub_product);
                        }
                    }
                }
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
