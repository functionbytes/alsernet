<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

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

procesarcombinaciones(Tools::getValue('id'));
echo 'acaba';
