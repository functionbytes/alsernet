<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../../config/config.inc.php';

$sql = Db::getInstance()->ExecuteS('SELECT
                                        apa.id_product
                                    FROM
                                        aalv_combinaciones_import aci
                                        LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                    GROUP BY apa.id_product
                                    where
                                    	apa.id_product is not null
                                        and apa.id_product != 0
                                    ORDER BY apa.id_product DESC ');

$idfeaturedesde = Feature::addFeatureImport('Poner desde');
$idFeatureValue = Db::getInstance()->getValue('
                SELECT fv.`id_feature_value`
                FROM '._DB_PREFIX_.'feature_value fv
                LEFT JOIN '._DB_PREFIX_.'feature_value_lang fvl ON (fvl.`id_feature_value` = fv.`id_feature_value` AND fvl.`id_lang` = 1)
                WHERE `value` = 1
                AND fv.`id_feature` = '.(int) $idfeaturedesde.'
                AND fv.`custom` = 0
                GROUP BY fv.`id_feature_value`');

foreach ($sql as $value) {

    $lote = Db::getInstance()->getValue('SELECT id_ps_product FROM aalv_alsernet_lotes_copia awbp WHERE active = 0 AND id_ps_product = '.$value['id_product']);

    if ($lote) {
        // echo "ES LOTE\n";
        continue;
    }

    $result = checkCombinationsHaveSamePrice($value['id_product']);

    // Verificamos si el producto tiene el Desde
    $feature = Db::getInstance()->getValue('SELECT * FROM aalv_feature_product afp WHERE id_feature = 9 AND id_product = '.$value['id_product']);

    if ($result['all_same_price'] && $feature) {
        // echo "Todas las combinaciones con stock tienen el mismo precio. => ".$value['id_product']." \n";
        Db::getInstance()->Execute('DELETE FROM aalv_feature_product WHERE id_feature=9 and id_product='.$value['id_product']);
    } elseif ($result['multiple_prices'] && ! $feature) {
        $product = new Product($value['id_product']);
        $product->addFeatureProductImport($value['id_product'], $idfeaturedesde, $idFeatureValue);
        $product->update();
        // echo "Hay más de una combinación con diferentes precios y tienen stock. => ".$value['id_product']." \n";
    }
}
dump('listo');

function checkCombinationsHaveSamePrice($id_product)
{
    // Cargar el producto
    $product = new Product($id_product);
    if (! Validate::isLoadedObject($product)) {
        return false; // Producto no encontrado
    }

    // Obtener todas las combinaciones del producto
    $combinations = $product->getAttributeCombinations();
    $prices = [];
    $has_stock = false;

    foreach ($combinations as $combination) {
        $id_combination = $combination['id_product_attribute'];

        // Validar si la combinación tiene stock
        $stock = StockAvailable::getQuantityAvailableByProduct($id_product, $id_combination);
        if ($stock > 0) {
            $has_stock = true; // Al menos una combinación tiene stock

            // Obtener el precio de la combinación (con o sin precio específico)
            $price_with_tax = Product::getPriceStatic(
                $id_product,
                true,   // Incluyendo impuestos
                $id_combination
            );

            $prices[] = $price_with_tax;
        }
    }

    // Contar los precios únicos entre combinaciones con stock
    $unique_prices_count = count(array_unique($prices));
    $all_same_price = $unique_prices_count === 1;
    $multiple_prices = $unique_prices_count > 1;

    return [
        'all_same_price' => $all_same_price,  // True si todas las combinaciones con stock tienen el mismo precio
        'has_stock' => $has_stock,            // True si al menos una combinación tiene stock
        'multiple_prices' => $multiple_prices,  // True si hay más de un precio entre combinaciones con stock
        'unique_prices_count' => $unique_prices_count,
    ];
}
