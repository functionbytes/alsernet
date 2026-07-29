<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

$datos = Db::getInstance()->executeS("select * from aalv_alsernet_brand_as_category aabac");

foreach ($datos as $value) {
    # code...

    $datos_product = Db::getInstance()->executeS("select * from aalv_product where id_manufacturer = " . $value['id_manufacturer'] . " and active = 1");

    foreach ($datos_product as $val) {
        # code...
        try {
            $product = new Product($val['id_product']);

            if (!Validate::isLoadedObject($product)) {
                echo "[ERROR] Producto " . $val['id_product'] . " no existe.\n";
                $errors++;
                continue;
            }

            // Categorías actuales del producto (en este shop)
            $currentCats = $product->getCategories();
            $hasCategory = in_array($value['id_category'], $currentCats);

            if ($hasCategory) {
                echo "[SKIP] Producto " . $val['id_product'] . " ya tiene la categoría " . $value['id_category'] . ".\n";
            } else {
                echo "[ADD] Agregando categoría " . $value['id_category'] . " al producto " . $val['id_product'] . "...\n";

                // addToCategories mantiene las existentes y añade las nuevas
                if (!$product->addToCategories([$value['id_category']])) {
                    echo "[ERROR] Falló addToCategories para producto " . $val['id_product'] . ".\n";
                    $errors++;
                    continue;
                }else{
                    $product->update();
                }
            }
        } catch (Exception $e) {
            echo "[EXCEPTION] Producto " . $val['id_product'] . ": " . $e->getMessage() . "\n";
            $errors++;
        }
    }
}
echo "\nLISTO\n";
