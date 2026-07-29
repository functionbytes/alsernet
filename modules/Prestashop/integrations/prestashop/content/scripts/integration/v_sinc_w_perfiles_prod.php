<?php

class v_sinc_w_perfiles_prodClass
{
    public function Procesar_v_sinc_w_perfiles_prod($data, $fila, $tipo)
    {

        if ($tipo <= 2) {

            if (! $data) {
                // throw new Exception("[" . __FUNCTION__ . "] - [" . __LINE__ . "] Dato nulo en data para fila " . $fila);
                return 1;
            }

            $orden = $data['orden'];

            if (! $orden) {
                $orden = 0;
            }

            if ($tipo == 1) {
                $existe = ''.Db::getInstance()->getValue('SELECT * from aalv_perfiles_prod_import where id = '.$data['id']);
                if ($existe == '') {
                    // NO existe registro
                    Db::getInstance()->Execute('INSERT INTO aalv_perfiles_prod_import(id, id_producto, id_valor, id_modelo, orden, activo) VALUES ('.$data['id'].','.$data['id_producto'].','.$data['id_valor'].','.$data['id_modelo'].','.$orden.',1)');
                } else {
                    Db::getInstance()->Execute('UPDATE aalv_perfiles_prod_import SET id_producto='.$data['id_producto'].',id_valor='.$data['id_valor'].',id_modelo='.$data['id_modelo'].',orden='.$orden.' WHERE  id='.$data['id']);
                }
            } else {
                Db::getInstance()->Execute('UPDATE aalv_perfiles_prod_import SET id_producto='.$data['id_producto'].',id_valor='.$data['id_valor'].',id_modelo='.$data['id_modelo'].',orden='.$orden.' WHERE  id='.$data['id']);
            }
        } else {
            Db::getInstance()->Execute('UPDATE aalv_perfiles_prod_import SET activo=0 WHERE id='.$fila);
            $idproducterp = ''.Db::getInstance()->getValue('SELECT id_producto FROM aalv_perfiles_prod_import where id='.$fila);

            if ($idproducterp != '') {

                $idproductattributeps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp);
                if ($idproductattributeps != '') {

                    if ((''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps)) != '') {

                        $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$idproducterp.' and activo=1 order by orden');

                        $idattributes = [];
                        foreach ($rowperfiles as $row) {

                            $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                            if ($idattr != '') {
                                $idattributes[] = (int) $idattr;
                            }
                        }

                        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

                        $combination = new Combination((int) $idprodps);
                        $combination->setAttributes($idattributes);
                        $combination->update();

                        auxiliares::procesarcombinaciones($idprodps);
                    }
                }
            }

            return 1;
        }

        $idproductattributeps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$data['id_producto']);

        if ($idproductattributeps != '') {

            if ((''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps)) != '') {

                $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$data['id_producto'].' and activo=1 order by orden');

                $idattributes = [];
                foreach ($rowperfiles as $row) {

                    $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                    if ($idattr != '') {
                        $idattributes[] = (int) $idattr;
                    }
                }

                if (count($idattributes) == 0) {
                    throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Revisar porque no existe '.$data['id_producto']);

                    return 1;
                }

                $combination = new Combination((int) $idproductattributeps);
                $combination->setAttributes($idattributes);
                $combination->default_on = 0;
                $combination->update();

                $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

                auxiliares::procesarcombinaciones($idprodps);
            }
        } else {
            // ver si está en combinacion_unica
            $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$data['id_producto']);

            if ($idprodps != '') {
                // si  tiene atributos, se debe pasar de combinación unica a combinacio y crear el product attribute correspondiente

                $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$data['id_producto'].' and activo=1 order by orden');

                $idattributes = [];
                foreach ($rowperfiles as $row) {

                    $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                    if ($idattr != '') {
                        $idattributes[] = (int) $idattr;
                    }
                }

                if (count($idattributes) > 0) {

                    // si que tiene caracteristicas, pasarlo de combinación unica a combinacion

                    $rowpasacomb = Db::getInstance()->getRow('SELECT * FROM aalv_combinacionunica_import WHERE id_product='.$idprodps);

                    $product = new Product($idprodps);
                    $stock_original = StockAvailable::getQuantityAvailableByProduct($idprodps);
                    $idProductAttribute = $product->addCombinationEntity(
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        0,
                        $product->reference,
                        $product->id_supplier,
                        $product->ean13,
                        0,
                        null,
                        $product->upc,
                        1,
                        [1],
                        null,
                        '',
                        null,
                        false,
                        null
                    );

                    $combination = new Combination((int) $idProductAttribute);
                    $combination->setAttributes($idattributes);
                    $combination->default_on = 0;
                    $combination->update();

                    Db::getInstance()->Execute('INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES ('.$idProductAttribute.','.$rowpasacomb['id_origen'].','.$rowpasacomb['id_articulo'].')');

                    Db::getInstance()->Execute('UPDATE aalv_combinaciones_import set unidades_oferta='.$rowpasacomb['unidades_oferta'].",etiqueta='".$rowpasacomb['etiqueta']."',activo=".$rowpasacomb['activo'].', estado_gestion='.$rowpasacomb['estado_gestion'].',es_segunda_mano='.$rowpasacomb['es_segunda_mano'].',externo_disponibilidad='.$rowpasacomb['externo_disponibilidad'].",codigo_proveedor='".$rowpasacomb['codigo_proveedor']."',precio_costo_proveedor=".$rowpasacomb['precio_costo_proveedor'].',tarifa_proveedor='.$rowpasacomb['tarifa_proveedor'].',es_arma='.$rowpasacomb['es_arma'].',es_arma_fogueo='.$rowpasacomb['es_arma_fogueo'].',es_cartucho='.$rowpasacomb['es_cartucho'].',categoria='.$rowpasacomb['categoria'].',familia='.$rowpasacomb['familia'].',subfamilia='.$rowpasacomb['subfamilia'].',grupo='.$rowpasacomb['grupo'].' WHERE id_product_attribute='.$idProductAttribute);

                    // pasar repositorio stock
                    Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET id_product_attribute='.$idProductAttribute.' where id_product='.$idprodps.' and id_product_attribute=0');

                    $stock = ''.Db::getInstance()->getValue('select quantity from aalv_repositorio_stock where id_product='.$idprodps.' and id_product_attribute='.$idProductAttribute);
                    if ($stock == '') {
                        $stock = $stock_original;
                    }
                    StockAvailable::setQuantity($idprodps, $idProductAttribute, (int) $stock, 1);

                    Db::getInstance()->Execute('delete from aalv_combinacionunica_import where id_product='.$idprodps);

                    auxiliares::procesarcombinaciones($idprodps);
                }
            }
        }

        return 1;
    }
}
