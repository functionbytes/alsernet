<?php

class v_sinc_w_productoClass
{
    public function Procesar_v_sinc_w_producto($data, $fila, $tipo)
    {

        $idproducterp = $data['id'];
        $idmodelo = $data['id_modelo'];
        $categoria = $data['categoria'];
        $categoriaarray = explode('|', $categoria);
        if (count($categoriaarray) > 1) {
            $categoria = $categoriaarray[0];
        }

        $familia = $data['familia'];
        $familiaarray = explode('|', $familia);
        if (count($familiaarray) > 1) {
            $familia = $familiaarray[0];
        }

        $subfamilia = $data['subfamilia'];
        $subfamiliaarray = explode('|', $subfamilia);
        if (count($subfamiliaarray) > 1) {
            $subfamilia = $subfamiliaarray[0];
        }

        $grupo = $data['grupo'];
        $grupoarray = explode('|', $grupo);
        if (count($grupoarray) > 1) {
            $grupo = $grupoarray[0];
        }

        if (! $categoria) {
            $categoria = 0;
        }
        if (! $familia) {
            $familia = 0;
        }
        if (! $subfamilia) {
            $subfamilia = 0;
        }
        if (! $grupo) {
            $grupo = 0;
        }

        $esarma = $data['es_arma'];
        $esarmafogueo = $data['es_arma_fogueo'];
        $escartucho = $data['es_cartucho'];
        $tarifaproveedor = $data['tarifa_proveedor'];

        if (! $esarma) {
            $esarma = 0;
        }
        if (! $esarmafogueo) {
            $esarmafogueo = 0;
        }
        if (! $escartucho) {
            $escartucho = 0;
        }
        if (! $tarifaproveedor) {
            $tarifaproveedor = 0;
        }

        // // echo $idproducterp . " " . $idmodelo . "\n";

        // ver si existe el modelo (producto prestashop)
        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);
        if ($idprodps == '') {
            // producto erp sin modelo
            throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Viene producto '.$idproducterp.' antes de crear el modelo '.$idmodelo.' - NUEVO CAMBIO');

            return 1;
        }
        /*** nuevo ***/
        // ver si existe otro producto prestashop en combinaciones y combinacionunica para el productoerp

        $traspasarstockprecios = false;

        $idprodpscombinacion = ''.Db::getInstance()->getValue('SELECT id_product from aalv_product_attribute where id_product_attribute in (SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp.')');
        $idprodpscombinacionunica = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$idproducterp);
        $idprodpsanterior = '';
        $idpscombanterior = '';
        if (($idprodpscombinacion != '') || ($idprodpscombinacionunica != '')) {
            if ($idprodpscombinacion != '') {
                $idprodpsanterior = $idprodpscombinacion;
                $idpscombanterior = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp);
            } else {
                $idprodpsanterior = $idprodpscombinacionunica;
                $idpscombanterior = '0';
            }
        }

        if ($idprodpsanterior != '') {
            if ($idprodpsanterior != $idprodps) {

                if ($idpscombanterior == 0) {
                    throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] revisar como movieron el producto simple '.$idmodelo);

                    return 1;
                }

                // Cargar la combinación original del producto 1
                $combinacion_anterior = new Combination($idpscombanterior);

                // Crear una nueva combinación para el producto 2
                $newCombination = new Combination;
                $newCombination->id_product = $idprodps;
                $newCombination->reference = $combinacion_anterior->reference;
                $newCombination->price = $combinacion_anterior->price;
                $newCombination->weight = $combinacion_anterior->weight;
                $newCombination->add();  // Guardar la nueva combinación

                // Copiar los atributos
                $attributes = $combinacion_anterior->getAttributesName(1);
                foreach ($attributes as $value) {
                    $idsAttributes[] = $value['id_attribute'];
                }

                $newCombination->setAttributes($idsAttributes);

                // Copiar el stock
                $quantity = StockAvailable::getQuantityAvailableByProduct($idprodpsanterior, $idpscombanterior);

                StockAvailable::setQuantity($idprodps, $newCombination->id, $quantity);

                Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET
                                                                id_product = '.$idprodps.',
                                                                id_product_attribute = '.$newCombination->id.'
                                                            WHERE
                                                                id_product = '.$idprodpsanterior.'
                                                                and id_product_attribute = '.$idpscombanterior);

                Db::getInstance()->Execute('UPDATE aalv_tarifa_cabecera_import SET
                                                                id_product = '.$idprodps.',
                                                                id_attribute = '.$newCombination->id.'
                                                            WHERE
                                                                id_product = '.$idprodpsanterior.'
                                                                and id_attribute = '.$idpscombanterior);

                Db::getInstance()->Execute('UPDATE aalv_portes_producto SET
                                                                id_product = '.$idprodps.',
                                                                id_product_attribute = '.$newCombination->id.'
                                                            WHERE
                                                                id_product = '.$idprodpsanterior.'
                                                                and id_product_attribute = '.$idpscombanterior);

                Db::getInstance()->Execute('UPDATE aalv_specific_price SET
                                                                id_product = '.$idprodps.',
                                                                id_product_attribute = '.$newCombination->id.'
                                                            WHERE
                                                                id_product = '.$idprodpsanterior.'
                                                                and id_product_attribute = '.$idpscombanterior);

                Db::getInstance()->Execute('UPDATE aalv_combinaciones_import SET
                                                                id_product_attribute = '.$newCombination->id.'
                                                            WHERE
                                                                id_product_attribute = '.$idpscombanterior);
                $combinacion_anterior->delete();
                // $traspasarstockprecios = true;
            }
        }

        /*** fin nuevo ***/

        // ver si existe el producto (posible combinacion prestashop)

        $idproductattributeps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp.' and id_product_attribute in (select id_product_attribute from aalv_product_attribute where id_product = '.$idprodps.')');
        $idprodpssinatributo = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$idproducterp);

        // if ($traspasarstockprecios) {
        //     $idprodpssinatributo = "";
        //     $tipo = 1;
        // }
        if (is_null($data['precio_costo_proveedor'])) {
            $data['precio_costo_proveedor'] = 0;
        }

        if (is_null($data['unidades_oferta'])) {
            $data['unidades_oferta'] = 0;
        }

        if ($idproductattributeps != '') {
            Db::getInstance()->Execute('UPDATE aalv_combinaciones_import set unidades_oferta='.$data['unidades_oferta'].",etiqueta='".$data['etiqueta']."',activo=".$data['activo'].', estado_gestion='.$data['estado'].',es_segunda_mano='.$data['es_segunda_mano'].',externo_disponibilidad='.$data['externo_disponibilidad'].",codigo_proveedor='".$data['codigo_proveedor']."',precio_costo_proveedor=".$data['precio_costo_proveedor'].',tarifa_proveedor='.$tarifaproveedor.',es_arma='.$esarma.',es_arma_fogueo='.$esarmafogueo.',es_cartucho='.$escartucho.',categoria='.$categoria.',familia='.$familia.',subfamilia='.$subfamilia.',grupo='.$grupo.',id_articulo='.$data['idarticulo'].' WHERE id_product_attribute='.$idproductattributeps);
        }

        if ($idprodpssinatributo != '') {
            Db::getInstance()->Execute('UPDATE aalv_combinacionunica_import set unidades_oferta='.$data['unidades_oferta'].",etiqueta='".$data['etiqueta']."',activo=".$data['activo'].', estado_gestion='.$data['estado'].',es_segunda_mano='.$data['es_segunda_mano'].',externo_disponibilidad='.$data['externo_disponibilidad'].",codigo_proveedor='".$data['codigo_proveedor']."',precio_costo_proveedor=".$data['precio_costo_proveedor'].',tarifa_proveedor='.$tarifaproveedor.',es_arma='.$esarma.',es_arma_fogueo='.$esarmafogueo.',es_cartucho='.$escartucho.',categoria='.$categoria.',familia='.$familia.',subfamilia='.$subfamilia.',grupo='.$grupo.',id_articulo='.$data['idarticulo'].' WHERE id_product='.$idprodpssinatributo);
        }

        if (($idproductattributeps != '') || ($idprodpssinatributo != '')) {
            $tipo = 2;
        }

        if ($tipo == 2 && $idproductattributeps == '' && $idprodpssinatributo == '') {
            $tipo = 1;
        }

        if ($tipo <= 2) {
            if ($tipo == 1) {
                // crear?
                // ver si existe el prod presta. si no existe, es que no se ha pasado antes el modelo

                if ($idprodps != '') {
                    // tenemos que ver si ya hay en combinacionunica alfo para el modelo
                    $existencombinaciones = Db::getInstance()->getValue('SELECT count(*) FROM aalv_product_attribute WHERE id_product='.$idprodps);

                    if ($existencombinaciones != 0) {
                        $product = new Product($idprodps);

                        // procesar perfiles
                        $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$idproducterp.' and activo=1 order by orden');

                        $idattributes = [];
                        foreach ($rowperfiles as $row) {
                            $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                            if ($idattr != '') {
                                $idattributes[] = (int) $idattr;
                            }
                        }

                        $ean13 = '';
                        if (Validate::isEan13(''.$data['ean13'])) {
                            $ean13 = $data['ean13'];
                        }

                        $upc = '';
                        if (Validate::isUpc(''.$data['upc'])) {
                            $upc = $data['upc'];
                        }

                        $idProductAttribute = $product->addCombinationEntity(
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            0,
                            $data['referencia'],
                            $product->id_supplier,
                            $ean13,
                            0,
                            null,
                            $upc,
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
                        $combination->update();

                        if (is_null($data['precio_costo_proveedor'])) {
                            $data['precio_costo_proveedor'] = 0;
                        }

                        // Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (" . $idProductAttribute . "," . $data["id"] . "," . $data["idarticulo"] . ")");
                        // Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=" . $data["unidades_oferta"] . ",etiqueta='" . $data["etiqueta"] . "',activo=" . $data['activo'] . ", estado_gestion=" . $data["estado"] . ",es_segunda_mano=" . $data["es_segunda_mano"] . ",externo_disponibilidad=" . $data["externo_disponibilidad"] . ",codigo_proveedor='" . $data["codigo_proveedor"] . "',precio_costo_proveedor=" . $data["precio_costo_proveedor"] . ",tarifa_proveedor=" . $tarifaproveedor . ",es_arma=" . $esarma . ",es_arma_fogueo=" . $esarmafogueo . ",es_cartucho=" . $escartucho . ",categoria=" . $categoria . ",familia=" . $familia . ",subfamilia=" . $subfamilia . ",grupo=" . $grupo . " WHERE id_product_attribute=" . $idProductAttribute);

                        Db::getInstance()->Execute('INSERT INTO aalv_combinaciones_import
                                                                                (
                                                                                    id_product_attribute,
                                                                                    id_origen,
                                                                                    id_articulo,
                                                                                    unidades_oferta,
                                                                                    etiqueta,
                                                                                    activo,
                                                                                    estado_gestion,
                                                                                    es_segunda_mano,
                                                                                    externo_disponibilidad,
                                                                                    codigo_proveedor,
                                                                                    precio_costo_proveedor,
                                                                                    tarifa_proveedor,
                                                                                    es_arma,
                                                                                    es_arma_fogueo,
                                                                                    es_cartucho,
                                                                                    categoria,
                                                                                    familia,
                                                                                    subfamilia,
                                                                                    grupo,
                                                                                    ean,
                                                                                    upc
                                                                                )
                                                                            VALUES
                                                                                (
                                                                                    '.$idProductAttribute.',
                                                                                    '.$data['id'].',
                                                                                    '.$data['idarticulo'].',
                                                                                    '.$data['unidades_oferta'].",
                                                                                    '".$data['etiqueta']."',
                                                                                    ".$data['activo'].',
                                                                                    '.$data['estado'].',
                                                                                    '.$data['es_segunda_mano'].',
                                                                                    '.$data['externo_disponibilidad'].",
                                                                                    '".$data['codigo_proveedor']."',
                                                                                    ".$data['precio_costo_proveedor'].',
                                                                                    '.$tarifaproveedor.',
                                                                                    '.$data['es_arma'].',
                                                                                    '.$data['es_arma_fogueo'].',
                                                                                    '.$escartucho.',
                                                                                    '.$categoria.',
                                                                                    '.$familia.',
                                                                                    '.$subfamilia.',
                                                                                    '.$grupo.",
                                                                                    '".$ean13."',
                                                                                    '".$upc."'
                                                                                )");

                        if ((''.$data['activo'] == '0') || (''.$data['estado'] == '0')) {
                            Db::getInstance()->Execute('INSERT INTO aalv_tot_switch_attribute_disabled( id_product_attribute, id_shop) VALUES ('.$idProductAttribute.',1)');
                            $combination = new Combination((int) $idProductAttribute);
                            $combination->default_on = false;
                            $combination->update();
                        } else {
                            Db::getInstance()->Execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$idProductAttribute.' AND  id_shop=1');
                        }

                        if (''.$data['unidades_oferta'] != '0') {
                            $product->unity = ''.$data['unidades_oferta'];
                        }
                        $product->update();
                        // tema armas. Aquí ya existen

                        /* nuevo */
                        if ($traspasarstockprecios) {
                            auxiliares::Cambiarproducto($idprodps, $idProductAttribute, $idprodpsanterior, $idpscombanterior);
                        }
                    } else {
                        // ver si ya existe algo en combinacionunica

                        $existeunica = ''.Db::getInstance()->getValue('SELECT id_origen FROM aalv_combinacionunica_import WHERE id_product='.$idprodps);

                        if ($existeunica == '') {
                            // crearlo como unica, al ser la primera

                            $product = new Product($idprodps);
                            $product->reference = $data['referencia'];

                            if (Validate::isEan13(''.$data['ean13'])) {
                                $product->ean13 = $data['ean13'];
                            }

                            if (Validate::isUpc(''.$data['upc'])) {
                                $product->upc = $data['upc'];
                            }

                            if (is_null($data['precio_costo_proveedor'])) {
                                $data['precio_costo_proveedor'] = 0;
                            }

                            // Db::getInstance()->Execute("INSERT INTO aalv_combinacionunica_import(id_product, id_origen, id_articulo) VALUES (" . $idprodps . "," . $data["id"] . "," . $data["idarticulo"] . ")");
                            // Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import set unidades_oferta=" . $data["unidades_oferta"] . ",etiqueta='" . $data["etiqueta"] . "',activo=" . $data['activo'] . ", estado_gestion=" . $data["estado"] . ",es_segunda_mano=" . $data["es_segunda_mano"] . ",externo_disponibilidad=" . $data["externo_disponibilidad"] . ",codigo_proveedor='" . $data["codigo_proveedor"] . "',precio_costo_proveedor=" . $data["precio_costo_proveedor"] . ",tarifa_proveedor=" . $tarifaproveedor . ",es_arma=" . $data["es_arma"] . ",es_arma_fogueo=" . $data["es_arma_fogueo"] . ",es_cartucho=" . $data["es_cartucho"] . ",categoria=" . $categoria . ",familia=" . $familia . ",subfamilia=" . $subfamilia . ",grupo=" . $grupo . " WHERE id_product=" . $idprodps);

                            Db::getInstance()->Execute('INSERT INTO aalv_combinacionunica_import
                                                                                (
                                                                                    id_product,
                                                                                    id_origen,
                                                                                    id_articulo,
                                                                                    unidades_oferta,
                                                                                    etiqueta,
                                                                                    activo,
                                                                                    estado_gestion,
                                                                                    es_segunda_mano,
                                                                                    externo_disponibilidad,
                                                                                    codigo_proveedor,
                                                                                    precio_costo_proveedor,
                                                                                    tarifa_proveedor,
                                                                                    es_arma,
                                                                                    es_arma_fogueo,
                                                                                    es_cartucho,
                                                                                    categoria,
                                                                                    familia,
                                                                                    subfamilia,
                                                                                    grupo,
                                                                                    ean,
                                                                                    upc
                                                                                )
                                                                            VALUES
                                                                                (
                                                                                    '.$idprodps.',
                                                                                    '.$data['id'].',
                                                                                    '.$data['idarticulo'].',
                                                                                    '.$data['unidades_oferta'].",
                                                                                    '".$data['etiqueta']."',
                                                                                    ".$data['activo'].',
                                                                                    '.$data['estado'].',
                                                                                    '.$data['es_segunda_mano'].',
                                                                                    '.$data['externo_disponibilidad'].",
                                                                                    '".$data['codigo_proveedor']."',
                                                                                    ".$data['precio_costo_proveedor'].',
                                                                                    '.$tarifaproveedor.',
                                                                                    '.$data['es_arma'].',
                                                                                    '.$data['es_arma_fogueo'].',
                                                                                    '.$data['es_cartucho'].',
                                                                                    '.$categoria.',
                                                                                    '.$familia.',
                                                                                    '.$subfamilia.',
                                                                                    '.$grupo.",
                                                                                    '".$data['ean13']."',
                                                                                    '".$data['upc']."'
                                                                                )");

                            if (''.$data['unidades_oferta'] != '0') {
                                $product->unity = ''.$data['unidades_oferta'];
                            }
                            $product->update();

                            /* nuevo */
                            if ($traspasarstockprecios) {
                                auxiliares::Cambiarproducto($idprodps, '0', $idprodpsanterior, $idpscombanterior);
                            }
                            // arama lo que venga

                        } else {
                            // borrar de combinacionunica y pasarla a combinacion normal

                            // la que llega
                            $product = new Product($idprodps);
                            // procesar perfiles
                            $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$idproducterp.' and activo=1 order by orden');

                            $idattributes = [];
                            foreach ($rowperfiles as $row) {
                                $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                                if ($idattr != '') {
                                    $idattributes[] = (int) $idattr;
                                }
                            }

                            $ean13 = '';
                            if (Validate::isEan13(''.$data['ean13'])) {
                                $ean13 = $data['ean13'];
                            }

                            $upc = '';
                            if (Validate::isUpc(''.$data['upc'])) {
                                $upc = $data['upc'];
                            }

                            $idProductAttribute = $product->addCombinationEntity(
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                $data['referencia'],
                                $product->id_supplier,
                                $ean13,
                                0,
                                null,
                                $upc,
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
                            $combination->update();

                            if (is_null($data['precio_costo_proveedor'])) {
                                $data['precio_costo_proveedor'] = 0;
                            }

                            // Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (" . $idProductAttribute . "," . $data["id"] . "," . $data["idarticulo"] . ")");
                            // Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=" . $data["unidades_oferta"] . ",etiqueta='" . $data["etiqueta"] . "',activo=" . $data['activo'] . ", estado_gestion=" . $data["estado"] . ",es_segunda_mano=" . $data["es_segunda_mano"] . ",externo_disponibilidad=" . $data["externo_disponibilidad"] . ",codigo_proveedor='" . $data["codigo_proveedor"] . "',precio_costo_proveedor=" . $data["precio_costo_proveedor"] . ",tarifa_proveedor=" . $tarifaproveedor . ",es_arma=" . $data["es_arma"] . ",es_arma_fogueo=" . $data["es_arma_fogueo"] . ",es_cartucho=" . $data["es_cartucho"] . ",categoria=" . $categoria . ",familia=" . $familia . ",subfamilia=" . $subfamilia . ",grupo=" . $grupo . " WHERE id_product_attribute=" . $idProductAttribute);
                            Db::getInstance()->Execute('INSERT INTO aalv_combinaciones_import
                                                                                (
                                                                                    id_product_attribute,
                                                                                    id_origen,
                                                                                    id_articulo,
                                                                                    unidades_oferta,
                                                                                    etiqueta,
                                                                                    activo,
                                                                                    estado_gestion,
                                                                                    es_segunda_mano,
                                                                                    externo_disponibilidad,
                                                                                    codigo_proveedor,
                                                                                    precio_costo_proveedor,
                                                                                    tarifa_proveedor,
                                                                                    es_arma,
                                                                                    es_arma_fogueo,
                                                                                    es_cartucho,
                                                                                    categoria,
                                                                                    familia,
                                                                                    subfamilia,
                                                                                    grupo,
                                                                                    ean,
                                                                                    upc
                                                                                )
                                                                            VALUES
                                                                                (
                                                                                    '.$idProductAttribute.',
                                                                                    '.$data['id'].',
                                                                                    '.$data['idarticulo'].',
                                                                                    '.$data['unidades_oferta'].",
                                                                                    '".$data['etiqueta']."',
                                                                                    ".$data['activo'].',
                                                                                    '.$data['estado'].',
                                                                                    '.$data['es_segunda_mano'].',
                                                                                    '.$data['externo_disponibilidad'].",
                                                                                    '".$data['codigo_proveedor']."',
                                                                                    ".$data['precio_costo_proveedor'].',
                                                                                    '.$tarifaproveedor.',
                                                                                    '.$data['es_arma'].',
                                                                                    '.$data['es_arma_fogueo'].',
                                                                                    '.$data['es_cartucho'].',
                                                                                    '.$categoria.',
                                                                                    '.$familia.',
                                                                                    '.$subfamilia.',
                                                                                    '.$grupo.",
                                                                                    '".$ean13."',
                                                                                    '".$upc."'
                                                                                )");
                            if ((''.$data['activo'] == '0') || (''.$data['estado'] == '0')) {
                                Db::getInstance()->Execute('INSERT INTO aalv_tot_switch_attribute_disabled( id_product_attribute, id_shop) VALUES ('.$idProductAttribute.',1)');
                                $combination = new Combination((int) $idProductAttribute);
                                $combination->default_on = false;
                                $combination->update();
                            } else {
                                Db::getInstance()->Execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$idProductAttribute.' AND  id_shop=1');
                            }

                            if (''.$data['unidades_oferta'] != '0') {
                                $product->unity = ''.$data['unidades_oferta'];
                                $product->update();
                            }

                            // la anterior que estaba en unica, recuperarla y pasarla a combinacion
                            $rowpasacomb = Db::getInstance()->getRow('SELECT * FROM aalv_combinacionunica_import WHERE id_product='.$idprodps);
                            $product = new Product($idprodps);

                            // procesar perfiles
                            $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$rowpasacomb['id_origen'].' and activo=1 order by orden');

                            $idattributes = [];
                            foreach ($rowperfiles as $row) {
                                $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                                if ($idattr != '') {
                                    $idattributes[] = (int) $idattr;
                                }
                            }

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
                            $combination->update();

                            if (is_null($data['precio_costo_proveedor'])) {
                                $data['precio_costo_proveedor'] = 0;
                            }

                            // Db::getInstance()->Execute("INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES (" . $idProductAttribute . "," . $rowpasacomb["id_origen"] . "," . $rowpasacomb["id_articulo"] . ")");
                            // Db::getInstance()->Execute("UPDATE aalv_combinaciones_import set unidades_oferta=" . $rowpasacomb["unidades_oferta"] . ",etiqueta='" . $rowpasacomb["etiqueta"] . "',activo=" . $rowpasacomb['activo'] . ", estado_gestion=" . $rowpasacomb["estado_gestion"] . ",es_segunda_mano=" . $rowpasacomb["es_segunda_mano"] . ",externo_disponibilidad=" . $rowpasacomb["externo_disponibilidad"] . ",codigo_proveedor='" . $rowpasacomb["codigo_proveedor"] . "',precio_costo_proveedor=" . $rowpasacomb["precio_costo_proveedor"] . ",tarifa_proveedor=" . $tarifaproveedor . ",es_arma=" . $rowpasacomb["es_arma"] . ",es_arma_fogueo=" . $rowpasacomb["es_arma_fogueo"] . ",es_cartucho=" . $rowpasacomb["es_cartucho"] . ",categoria=" . $rowpasacomb["categoria"] . ",familia=" . $rowpasacomb["familia"] . ",subfamilia=" . $rowpasacomb["subfamilia"] . ",grupo=" . $rowpasacomb["grupo"] . " WHERE id_product_attribute=" . $idProductAttribute);
                            Db::getInstance()->Execute('INSERT INTO aalv_combinaciones_import
                                                            (
                                                                id_product_attribute,
                                                                id_origen,
                                                                id_articulo,
                                                                unidades_oferta,
                                                                etiqueta,
                                                                activo,
                                                                estado_gestion,
                                                                es_segunda_mano,
                                                                externo_disponibilidad,
                                                                codigo_proveedor,
                                                                precio_costo_proveedor,
                                                                tarifa_proveedor,
                                                                es_arma,
                                                                es_arma_fogueo,
                                                                es_cartucho,
                                                                categoria,
                                                                familia,
                                                                subfamilia,
                                                                grupo,
                                                                ean,
                                                                upc
                                                            )
                                                            VALUES
                                                            (
                                                                '.$idProductAttribute.',
                                                                '.$rowpasacomb['id_origen'].',
                                                                '.$rowpasacomb['id_articulo'].',
                                                                '.$rowpasacomb['unidades_oferta'].",
                                                                '".$rowpasacomb['etiqueta']."',
                                                                ".$rowpasacomb['activo'].',
                                                                '.$rowpasacomb['estado_gestion'].',
                                                                '.$rowpasacomb['es_segunda_mano'].',
                                                                '.$rowpasacomb['externo_disponibilidad'].",
                                                                '".$rowpasacomb['codigo_proveedor']."',
                                                                ".$rowpasacomb['precio_costo_proveedor'].',
                                                                '.$tarifaproveedor.',
                                                                '.$rowpasacomb['es_arma'].',
                                                                '.$rowpasacomb['es_arma_fogueo'].',
                                                                '.$rowpasacomb['es_cartucho'].',
                                                                '.$rowpasacomb['categoria'].',
                                                                '.$rowpasacomb['familia'].',
                                                                '.$rowpasacomb['subfamilia'].',
                                                                '.$rowpasacomb['grupo'].",
                                                                '".$rowpasacomb['ean']."',
                                                                '".$rowpasacomb['upc']."'
                                                            )");
                            // pasar repositorio stock
                            Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET id_product_attribute='.$idProductAttribute.' where id_product='.$idprodps.' and id_product_attribute=0');
                            Db::getInstance()->Execute('DELETE from aalv_combinacionunica_import where id_product='.$idprodps);

                            // Procesamos el stock del articulo viejo para ponerle el stock
                            Product::alsernetNewVisibilidad($rowpasacomb['id_articulo']);

                            $product = new Product($idprodps);
                            // $product->reference = "";
                            $product->update();

                            /* nuevo */
                            if ($traspasarstockprecios) {
                                auxiliares::Cambiarproducto($idprodps, $idProductAttribute, $idprodpsanterior, $idpscombanterior);
                            }
                        }
                    }

                    $product->update();
                } else {
                    // producto erp sin modelo
                    throw new Exception('['.__FUNCTION__.'] - ['.__LINE__.'] Viene producto '.$idproducterp.' antes de crear el modelo '.$idmodelo);

                    return 1;
                }
            } else {
                if ($idprodpssinatributo != '') {
                    $proddelattr = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product WHERE id_product='.$idprodpssinatributo);
                    if ($proddelattr == '') {
                        return 1;
                    }

                    $product = new Product($idprodpssinatributo);
                    $product->reference = $data['referencia'];

                    if (Validate::isEan13(''.$data['ean13'])) {
                        $product->ean13 = $data['ean13'];
                    }

                    if (Validate::isUpc(''.$data['upc'])) {
                        $product->upc = $data['upc'];
                    }

                    if (''.$data['unidades_oferta'] != '0') {
                        $product->unity = ''.$data['unidades_oferta'];
                    }

                    $product->update();
                }

                if ($idproductattributeps != '') {
                    $proddelattr = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);
                    if ($proddelattr == '') {
                        return 1;
                    }

                    $existe = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

                    if ($existe != '') {
                        // procesar perfiles
                        $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$idproducterp.' and activo=1 order by orden');

                        $idattributes = [];
                        foreach ($rowperfiles as $row) {
                            $idattr = ''.Db::getInstance()->getValue('SELECT id_attribute FROM aalv_attribute_import WHERE id_origen='.$row['id_valor']);
                            if ($idattr != '') {
                                $idattributes[] = (int) $idattr;
                            }
                        }
                        $combination = new Combination((int) $idproductattributeps);
                        $combination->reference = $data['referencia'];

                        if (Validate::isEan13(''.$data['ean13'])) {
                            $combination->ean13 = $data['ean13'];
                        }

                        if (Validate::isUpc(''.$data['upc'])) {
                            $combination->upc = $data['upc'];
                        }

                        if (count($idattributes) > 0) {
                            $combination->setAttributes($idattributes);
                        }

                        $combination->update();

                        if ((''.$data['activo'] == '0') || (''.$data['estado'] == '0')) {
                            Db::getInstance()->Execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$idproductattributeps.' AND  id_shop=1');
                            Db::getInstance()->Execute('INSERT INTO aalv_tot_switch_attribute_disabled (id_product_attribute, id_shop) VALUES ('.$idproductattributeps.',1)');
                            $combination = new Combination((int) $idproductattributeps);
                            $combination->default_on = false;
                            $combination->update();
                        } else {
                            Db::getInstance()->Execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$idproductattributeps.' AND  id_shop=1');
                        }

                        $proddelattr = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

                        if ($proddelattr != '') {
                            $product = new Product($proddelattr);

                            if (''.$data['unidades_oferta'] != '0') {
                                $product->unity = ''.$data['unidades_oferta'];
                            }

                            $product->update();
                        }
                    }
                }
            }

            /* BLOQUEOS POR TAMAÑO ENVIO SEUR */
            if (strpos($data['etiqueta'], 'NOEX') !== false) {
                if (! auxiliares::validarFeature(VENTA_ESPANA_PORTUGAL_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_ESPANA_PORTUGAL_FEATURE.','.$idprodps.','.VENTA_ESPANA_PORTUGAL_FEATURE_VALUE.')');
                }
                /* BLOQUEAR INPOST POR PESO */
                Db::getInstance()->Execute('UPDATE aalv_product SET weight = 30 WHERE id_product='.$idprodps);

            } else {
                if (auxiliares::validarFeature(VENTA_ESPANA_PORTUGAL_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_ESPANA_PORTUGAL_FEATURE);
                }
                Db::getInstance()->Execute('UPDATE aalv_product SET weight = 0 WHERE id_product='.$idprodps);
            }
            /* BLOQUEOS POR PESO TAMAÑO INPOST */
            if (strpos($data['etiqueta'], 'NOPP') !== false || strpos($data['etiqueta'], 'NOLOC') !== false) {
                $peso = 0;
                if (strpos($data['etiqueta'], 'NOPP') !== false) {
                    $peso = 30;
                }
                if (strpos($data['etiqueta'], 'NOLOC') !== false) {
                    $peso = 25;
                }
                Db::getInstance()->Execute('UPDATE aalv_product SET weight = '.$peso.' WHERE id_product='.$idprodps.' AND weight < '.$peso);

            } else {
                Db::getInstance()->Execute('UPDATE aalv_product SET weight = 0 WHERE id_product='.$idprodps);
            }

            if (strpos($data['etiqueta'], 'VENTA_ESP') !== false) {
                if (! auxiliares::validarFeature(VENTA_ESP_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_ESP_FEATURE.','.$idprodps.','.VENTA_ESP_FEATURE_VALUE.')');
                }
            } else {
                if (auxiliares::validarFeature(VENTA_ESP_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_ESP_FEATURE);
                }
            }

            if ($data['es_segunda_mano'] != 0) {
                if (! auxiliares::validarFeature(ES_SEGUNDA_MANO_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta = '".pSQL('SEGUNDA MANO')."' WHERE id_product = ".$idprodps);
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.ES_SEGUNDA_MANO_FEATURE.','.$idprodps.','.ES_SEGUNDA_MANO_FEATURE_VALUE.')');
                } else {
                    Db::getInstance()->Execute('UPDATE aalv_feature_product SET id_feature_value = '.ES_SEGUNDA_MANO_FEATURE_VALUE.' WHERE id_product = '.$idprodps.' AND id_feature = '.ES_SEGUNDA_MANO_FEATURE);
                }

                if (! auxiliares::validarFeature(ES_SEGUNDA_MANO_FINANCIACION_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES
                        ('.ES_SEGUNDA_MANO_FINANCIACION_FEATURE.','.$idprodps.','.ES_SEGUNDA_MANO_FINANCIACION_FEATURE_VALUE.')
                        ON DUPLICATE KEY UPDATE id_feature = '.ES_SEGUNDA_MANO_FINANCIACION_FEATURE.', id_product = '.$idprodps.', id_feature_value = '.ES_SEGUNDA_MANO_FINANCIACION_FEATURE_VALUE);
                }

                $buscar = ''.Db::getInstance()->getValue('SELECT es_segunda_mano FROM aalv_combinacionunica_import WHERE id_product = '.$idprodps." and etiqueta = '".pSQL('SEGUNDA MANO')."'");

                if ($buscar == '') {
                    Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta='".pSQL('SEGUNDA MANO')."' WHERE id_product = ".$idprodps);
                }
            } else {
                if (auxiliares::validarFeature(ES_SEGUNDA_MANO_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute("UPDATE aalv_combinacionunica_import SET etiqueta = '' WHERE id_product = ".$idprodps);
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.ES_SEGUNDA_MANO_FEATURE);
                }
                if (auxiliares::validarFeature(ES_SEGUNDA_MANO_FINANCIACION_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.ES_SEGUNDA_MANO_FINANCIACION_FEATURE);
                }
            }

            if (strpos($data['etiqueta'], 'DNI') !== false) {
                if (! auxiliares::validarFeature(VENTA_DNI_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_DNI_FEATURE.','.$idprodps.','.VENTA_DNI_FEATURE_VALUE.')');
                }
            } else {
                if (auxiliares::validarFeature(VENTA_DNI_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_DNI_FEATURE.' and id_feature_value = '.VENTA_DNI_FEATURE_VALUE);
                }
            }

            if (strpos($data['etiqueta'], 'ESCOPETA') !== false) {
                if (! auxiliares::validarFeature(VENTA_ESCOPETA_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_ESCOPETA_FEATURE.','.$idprodps.','.VENTA_ESCOPETA_FEATURE_VALUE.')');
                }
            } else {
                if (auxiliares::validarFeature(VENTA_ESCOPETA_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_ESCOPETA_FEATURE.' and id_feature_value = '.VENTA_ESCOPETA_FEATURE_VALUE);
                }
            }

            if (strpos($data['etiqueta'], 'RIFLE') !== false) {
                if (! auxiliares::validarFeature(VENTA_RIFLE_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_RIFLE_FEATURE.','.$idprodps.','.VENTA_RIFLE_FEATURE_VALUE.')');
                }
            } else {
                if (auxiliares::validarFeature(VENTA_RIFLE_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_RIFLE_FEATURE.' and id_feature_value = '.VENTA_RIFLE_FEATURE_VALUE);
                }
            }

            if (strpos($data['etiqueta'], 'CORTA') !== false) {
                if (! auxiliares::validarFeature(VENTA_CORTA_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('INSERT INTO aalv_feature_product (id_feature, id_product, id_feature_value) VALUES ('.VENTA_CORTA_FEATURE.','.$idprodps.','.VENTA_CORTA_FEATURE_VALUE.')');
                }
            } else {
                if (auxiliares::validarFeature(VENTA_CORTA_FEATURE, $idprodps)) {
                    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_product = '.$idprodps.' AND id_feature = '.VENTA_CORTA_FEATURE.' and id_feature_value = '.VENTA_CORTA_FEATURE_VALUE);
                }
            }

            auxiliares::ProcesarTipoProducto($data, $idprodps);
            auxiliares::procesarcombinaciones($idprodps);
            $array = [
                '11' => $categoria,
                '12' => $familia,
                '13' => $subfamilia,
                '14' => $grupo,
            ];
            auxiliares::TodoFeature($idprodps, $array);
            Product::alsernetNewVisibilidad($data['idarticulo']);
        } else {
            // borrado desactivacion
            $idproducterp = $fila;
            $idproductattributeps = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_combinaciones_import WHERE id_origen='.$idproducterp);
            $idprodpssinatributo = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_combinacionunica_import WHERE id_origen='.$idproducterp);

            if ($idproductattributeps != '') {
                // desactivar la combinacion
                Db::getInstance()->Execute('DELETE FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$idproductattributeps.' AND  id_shop=1');
                Db::getInstance()->Execute('INSERT INTO aalv_tot_switch_attribute_disabled (id_product_attribute, id_shop) VALUES ('.$idproductattributeps.',1)');
                $combination = new Combination((int) $idproductattributeps);
                $combination->default_on = false;
                $combination->update();

                $proddelattr = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_attribute WHERE id_product_attribute='.$idproductattributeps);

                if ($proddelattr != '') {
                    $product = new Product($proddelattr);
                    $product->update();

                    auxiliares::procesarcombinaciones($proddelattr);

                    $array = [
                        '11' => $categoria,
                        '12' => $familia,
                        '13' => $subfamilia,
                        '14' => $grupo,
                    ];
                    auxiliares::TodoFeature($idprodps, $array);
                }
            }
        }

        return 1;
    }
}
