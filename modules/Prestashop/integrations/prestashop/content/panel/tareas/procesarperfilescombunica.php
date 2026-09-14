<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

function crearFeatureValue($idFeature, $value, $custom)
{

    $idFeatureValue = Db::getInstance()->getValue('
                SELECT fv.`id_feature_value`
                FROM '._DB_PREFIX_.'feature_value fv
                LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                WHERE `value` = \''.pSQL($value).'\'
                AND fv.`id_feature` = '.(int) $idFeature.'
                AND fv.`custom` = '.$custom.'
                GROUP BY fv.`id_feature_value`');
    if ($idFeatureValue) {
        return (int) $idFeatureValue;
    } else {

        $feature_value = new FeatureValue;
        $feature_value->id_feature = (int) $idFeature;
        $feature_value->custom = $custom;
        $feature_value->value = array_fill_keys(Language::getIDs(false), $value);

        $feature_value->add();

        return (int) $feature_value->id;

    }
}

function procesarCFSG($idproduct)
{

    $categoria = 0;
    $familia = 0;
    $subfamilia = 0;
    $grupo = 0;

    $essegundamano = 0;

    $productattribute = ''.Db::getInstance()->getValue('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idproduct);

    if ($productattribute != '') {

        $categoria = Db::getInstance()->getValue('SELECT categoria FROM aalv_combinaciones_import WHERE id_product_attribute='.$productattribute);
        $familia = Db::getInstance()->getValue('SELECT familia FROM aalv_combinaciones_import WHERE id_product_attribute='.$productattribute);
        $subfamilia = Db::getInstance()->getValue('SELECT subfamilia FROM aalv_combinaciones_import WHERE id_product_attribute='.$productattribute);
        $grupo = Db::getInstance()->getValue('SELECT grupo FROM aalv_combinaciones_import WHERE id_product_attribute='.$productattribute);
        $essegundamano = Db::getInstance()->getValue('SELECT es_segunda_mano FROM aalv_combinaciones_import WHERE id_product_attribute='.$productattribute);

    } else {

        $categoria = Db::getInstance()->getValue('SELECT categoria FROM aalv_combinacionunica_import WHERE id_product='.$idproduct);
        $familia = Db::getInstance()->getValue('SELECT familia FROM aalv_combinacionunica_import WHERE id_product='.$idproduct);
        $subfamilia = Db::getInstance()->getValue('SELECT subfamilia FROM aalv_combinacionunica_import WHERE id_product='.$idproduct);
        $grupo = Db::getInstance()->getValue('SELECT grupo FROM aalv_combinacionunica_import WHERE id_product='.$idproduct);
        $essegundamano = Db::getInstance()->getValue('SELECT es_segunda_mano FROM aalv_combinacionunica_import WHERE id_product='.$idproduct);

    }

    $product = new Product($idproduct);

    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=11 and id_product='.$idproduct);
    if ($categoria != 0) {
        $idfeaturevalue = crearFeatureValue(11, $categoria, 1);
        if ($idfeaturevalue != 0) {

            $product->addFeatureProductImport($idproduct, 11, $idfeaturevalue);
        }
    }

    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=12 and id_product='.$idproduct);
    if ($familia != 0) {
        $idfeaturevalue = crearFeatureValue(12, $familia, 1);
        if ($idfeaturevalue != 0) {

            $product->addFeatureProductImport($idproduct, 12, $idfeaturevalue);
        }
    }

    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=13 and id_product='.$idproduct);
    if ($subfamilia != 0) {
        $idfeaturevalue = crearFeatureValue(13, $subfamilia, 1);
        if ($idfeaturevalue != 0) {

            $product->addFeatureProductImport($idproduct, 13, $idfeaturevalue);
        }
    }

    Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=14 and id_product='.$idproduct);
    if ($grupo != 0) {
        $idfeaturevalue = crearFeatureValue(14, $grupo, 1);
        if ($idfeaturevalue != 0) {

            $product->addFeatureProductImport($idproduct, 14, $idfeaturevalue);
        }
    }

    if ($essegundamano == 0) {
        $product->show_condition = false;
        $product->condition = 'new';
    } else {
        $product->show_condition = true;
        $product->condition = 'used';
    }
    $product->update();

}

function procesarcombinaciones($idproduct)
{

    // ver si tiene combinaciones

    $productattributes = Db::getInstance()->ExecuteS('SELECT id_product_attribute FROM aalv_product_attribute WHERE id_product='.$idproduct);

    if ($productattributes) {

        $idproductatributeminimo = 0;
        $preciominimo = 999999;
        $numcambios = 0;

        $arprecios = [];

        foreach ($productattributes as $productattributeitem) {

            $noestaborrada = ''.Db::getInstance()->getValue('SELECT id_tot_switch_attribute_disabled FROM aalv_tot_switch_attribute_disabled WHERE id_product_attribute='.$productattributeitem['id_product_attribute']);

            if ($noestaborrada == '') {

                // ver si tiene stock
                $stock = StockAvailable::getQuantityAvailableByProduct($idproduct, $productattributeitem['id_product_attribute']);

                if ($stock > 0) {
                    // coger precio
                    $specific_price = '';
                    $miprecio = Product::priceCalculation(1, $idproduct, $productattributeitem['id_product_attribute'], 0, 0, '', 0, 0, 1, true, 6, 0, false, false, $specific_price, false, 0, true, 0, 0, 0);

                    $miprecio = $miprecio - $specific_price['reduction'];

                    $miprecio = round($miprecio, 2);

                    if (! in_array($miprecio, $arprecios)) {
                        $arprecios[] = $miprecio;
                    }

                    if ($miprecio < $preciominimo) {

                        $preciominimo = $miprecio;
                        $idproductatributeminimo = $productattributeitem['id_product_attribute'];
                        $numcambios = $numcambios + 1;
                    }

                }

            }

        }

        if ($idproductatributeminimo != 0) {
            // hacer $idproductatributeminimo la combinacion Por defecto

            $product = new Product($idproduct);
            $product->deleteDefaultAttributes();
            $product->setDefaultAttribute($idproductatributeminimo);

        }

        if (count($arprecios) > 1) {
            // atributo desde
            $idfeaturedesde = Feature::addFeatureImport('Poner desde');
            $idfeaturedesdevalue = crearFeatureValue($idfeaturedesde, '1', 0);
            if ($idfeaturedesdevalue != 0) {
                Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
                $product = new Product($idproduct);
                $product->addFeatureProductImport($idproduct, $idfeaturedesde, $idfeaturedesdevalue);
            }
        } else {
            Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$idproduct);
        }

    }

}

$rows = Db::getInstance()->ExecuteS('SELECT distinct id_producto, id_modelo FROM aalv_perfiles_prod_import WHERE id_producto in (SELECT id_origen FROM aalv_combinacionunica_import) and id_producto not in (SELECT id_origen FROM aalv_combinaciones_import)');

foreach ($rows as $rowitem) {
    echo $rowitem['id_modelo'].'<br/>';

    $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$rowitem['id_modelo']);

    if ($idprodps != '') {
        // si  tiene atributos, se debe pasar de combinación unica a combinacio y crear el product attribute correspondiente

        $rowperfiles = Db::getInstance()->ExecuteS('SELECT id_valor, orden FROM aalv_perfiles_prod_import where id_producto='.$rowitem['id_producto'].' and activo=1 order by orden');

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
                null);

            $combination = new Combination((int) $idProductAttribute);
            $combination->setAttributes($idattributes);
            $combination->default_on = true;
            $combination->update();

            Db::getInstance()->Execute('INSERT INTO aalv_combinaciones_import(id_product_attribute, id_origen, id_articulo) VALUES ('.$idProductAttribute.','.$rowpasacomb['id_origen'].','.$rowpasacomb['id_articulo'].')');

            Db::getInstance()->Execute('UPDATE aalv_combinaciones_import set unidades_oferta='.$rowpasacomb['unidades_oferta'].",etiqueta='".$rowpasacomb['etiqueta']."',estado_gestion=".$rowpasacomb['estado_gestion'].',es_segunda_mano='.$rowpasacomb['es_segunda_mano'].',externo_disponibilidad='.$rowpasacomb['externo_disponibilidad'].",codigo_proveedor='".$rowpasacomb['codigo_proveedor']."',precio_costo_proveedor=".$rowpasacomb['precio_costo_proveedor'].',tarifa_proveedor='.$rowpasacomb['tarifa_proveedor'].',es_arma='.$rowpasacomb['es_arma'].',es_arma_fogueo='.$rowpasacomb['es_arma_fogueo'].',es_cartucho='.$rowpasacomb['es_cartucho'].',categoria='.$rowpasacomb['categoria'].',familia='.$rowpasacomb['familia'].',subfamilia='.$rowpasacomb['subfamilia'].',grupo='.$rowpasacomb['grupo'].' WHERE id_product_attribute='.$idProductAttribute);

            // pasar repositorio stock
            Db::getInstance()->Execute('UPDATE aalv_repositorio_stock SET id_product_attribute='.$idProductAttribute.' where id_product='.$idprodps.' and id_product_attribute=0');

            $stock = ''.Db::getInstance()->getValue('select quantity from aalv_repositorio_stock where id_product='.$idprodps.' and id_product_attribute='.$idProductAttribute);
            if ($stock == '') {
                $stock = '0';
            }
            StockAvailable::setQuantity($idprodps, $idProductAttribute, (int) $stock, 1);

            Db::getInstance()->Execute('delete from aalv_combinacionunica_import where id_product='.$idprodps);

            procesarcombinaciones($idprodps);
            procesarCFSG($idprodps);

        }

    }

}

echo 'acaba';
