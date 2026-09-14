<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}

if (! defined('PS_ADMIN_DIR')) {
    define('PS_ADMIN_DIR', _PS_ADMIN_DIR_);
}

require _PS_ADMIN_DIR_.'/../config/config.inc.php';
require _PS_ADMIN_DIR_.'/../init.php';

$feature_id = 6;
$feature_value_id = 24178;

$languages = Language::getLanguages();

$sql = 'SELECT fp.`id_product`
        FROM `'._DB_PREFIX_.'feature_product` fp
        WHERE fp.`id_feature`='.(int) $feature_id.' AND fp.`id_feature_value`='.(int) $feature_value_id.'
        ORDER BY fp.`id_product`';
$products = DB::getInstance()->executeS($sql);

foreach ($products as $product_item) {
    $product = new Product((int) $product_item['id_product']);

    $sql = 'SELECT `id_customization_field` FROM `'._DB_PREFIX_.'customization_field` WHERE `id_product`='.$product->id;
    $id_customization_field = DB::getInstance()->getValue($sql);

    if (! $id_customization_field) {
        $sql = 'INSERT INTO `'._DB_PREFIX_.'customization_field`(`id_product`, `type`, `required`, `is_module`, `is_deleted`) VALUES ('.$product->id.', 1, 0, 0, 0)';
        if (DB::getInstance()->execute($sql)) {
            echo 'Producto '.$product->id.'; customization guardada ...<br>';

            $sql = 'SELECT `id_customization_field` FROM `'._DB_PREFIX_.'customization_field` WHERE `id_product`='.$product->id;
            $id_customization_field = DB::getInstance()->getValue($sql);

            if ($id_customization_field) {
                foreach ($languages as $language) {
                    $sql = 'INSERT INTO `'._DB_PREFIX_.'customization_field_lang`(`id_customization_field`, `id_lang`, `id_shop`, `name`) VALUES ('.(int) $id_customization_field.', '.(int) $language['id_lang'].', 1, \'test\')';
                    if (DB::getInstance()->execute($sql)) {
                        echo 'Producto '.$product->id.'; customization_lang guardada ...<br>';
                    } else {
                        echo 'ERROR: Producto '.$product->id.'; customization_lang no guardada ...<br>';
                    }
                }
            } else {
                echo 'ERROR: Producto '.$product->id.'; Customization no devuelta tras insertarla; [id_customization_field = '.$id_customization_field.'] ...<br>';
            }
        } else {
            echo 'ERROR: Producto '.$product->id.'; customization no guardada ...<br>';
        }
    }

    if ($product->save()) {
        echo 'Producto '.$product->id.' guardado ...<br>';
    } else {
        echo 'ERROR: Producto '.$product->id.' no se ha podido guardar ...<br>';
    }
}
