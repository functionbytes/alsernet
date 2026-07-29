<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// $result = Db::getInstance()->executeS("SELECT
//                     id_product_attribute,
//                     COUNT(*) AS cantidad_repeticiones,
//                     id_product
//                 FROM
//                     aalv_specific_price
//                 WHERE
//                 	`to` IS NULL
//                 	and `from` like '2022%'
//                     and id_country = 0
//                 GROUP BY id_product_attribute, id_country
//                 HAVING COUNT(*) > 1
//                 ORDER BY id_product DESC");

// foreach ($result as $value) {
//     $result2 = Db::getInstance()->executeS("SELECT
//                             asp.id_specific_price,
//                             asp.`from`
//                         FROM
//                             aalv_product_attribute apa
//                             left join aalv_specific_price asp on apa.id_product_attribute = asp.id_product_attribute
//                         WHERE
//                             apa.id_product_attribute = ".$value['id_product_attribute']."
//                             and asp.id_country = 0
//                             AND asp.`to` is NULL
//                         ORDER BY `from` ASC");
//     // var_dump(count($result2));die();

//     if(count($result2) == 1){
//         continue;
//     }
//     // foreach ($result2 as $val) {
//         // Fecha específica
//         $fecha = new DateTime($result2[0]['from']);

//         // Restar un día
//         $fecha->modify('+1 day');

//         // Mostrar la fecha resultante
//         // echo "UPDATE PS_specific_price set `to` = '".$fecha->format('Y-m-d 00:00:00')."' WHERE id_specific_price = ".$result2[0]['id_specific_price'].';<br>';

//     // }
// }

// setQuantity(61872,181886,1, 1, false);

function setQuantity($id_product, $id_product_attribute, $quantity, $id_shop = null, $add_movement = true)
{
    if (! Validate::isUnsignedId($id_product)) {
        return false;
    }
    $context = Context::getContext();
    // if there is no $id_shop, gets the context one
    if ($id_shop === null && Shop::getContext() != Shop::CONTEXT_GROUP) {
        $id_shop = (int) $context->shop->id;
    }
    $depends_on_stock = StockAvailable::dependsOnStock($id_product);

    // Try to set available quantity if product does not depend on physical stock
    if (! $depends_on_stock) {
        // $stockManager = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Stock\\StockManager');

        $id_stock_available = (int) StockAvailable::getStockAvailableIdByProductId($id_product, $id_product_attribute, $id_shop);

        if ($id_stock_available) {
            $stock_available = new StockAvailable($id_stock_available);

            $deltaQuantity = (int) $quantity - (int) $stock_available->quantity;
            var_dump($deltaQuantity);
            exit();
            $stock_available->quantity = (int) $quantity;
            $stock_available->update();

            if ($add_movement === true && $deltaQuantity != 0) {
                $stockManager->saveMovement($id_product, $id_product_attribute, $deltaQuantity);
            }
        }
        // else {
        //     $out_of_stock = StockAvailable::outOfStock($id_product, $id_shop);
        //     $stock_available = new StockAvailable();
        //     $stock_available->out_of_stock = (int) $out_of_stock;
        //     $stock_available->id_product = (int) $id_product;
        //     $stock_available->id_product_attribute = (int) $id_product_attribute;
        //     $stock_available->quantity = (int) $quantity;
        //     if ($id_shop === null) {
        //         $shop_group = Shop::getContextShopGroup();
        //     } else {
        //         $shop_group = new ShopGroup((int) Shop::getGroupFromShop((int) $id_shop));
        //     }
        //     // if quantities are shared between shops of the group
        //     if ($shop_group->share_stock) {
        //         $stock_available->id_shop = 0;
        //         $stock_available->id_shop_group = (int) $shop_group->id;
        //     } else {
        //         $stock_available->id_shop = (int) $id_shop;
        //         $stock_available->id_shop_group = 0;
        //     }
        //     $stock_available->add();

        //     if (true === $add_movement && 0 != $quantity) {
        //         $stockManager->saveMovement($id_product, $id_product_attribute, (int) $quantity);
        //     }
        // }

        // Hook::exec(
        //     'actionUpdateQuantity',
        //     [
        //         'id_product' => $id_product,
        //         'id_product_attribute' => $id_product_attribute,
        //         'quantity' => $stock_available->quantity,
        //     ]
        // );
    }
    // Cache::clean('StockAvailable::getQuantityAvailableByProduct_' . (int) $id_product . '*');
}
