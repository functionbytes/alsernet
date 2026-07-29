<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function getCatalogRelatedTables()
{

    return [
        'product',
        'product_shop',
        'product_lang',
        'category_product',
        'product_tag',
        'tag',
        'image',
        'image_lang',
        'image_shop',
        'product_carrier',
        'cart_product',
        'product_attachment',
        'product_country_tax',
        'product_download',
        'product_group_reduction_cache',
        'product_sale',
        'product_supplier',
        'warehouse_product_location',
        'supply_order_detail',
        'attribute',
        'attribute_impact',
        'attribute_lang',
        'attribute_group',
        'attribute_group_lang',
        'attribute_group_shop',
        'attribute_shop',
        'product_attribute',
        'product_attribute_shop',
        'product_attribute_combination',
        'product_attribute_image',
        'manufacturer',
        'manufacturer_lang',
        'manufacturer_shop',
        'supplier',
        'supplier_lang',
        'supplier_shop',
        'customization',
        'customization_field',
        'customization_field_lang',
        'customized_data',
        // 'feature',
        // 'feature_lang',
        'feature_product',
        // 'feature_shop',
        // 'feature_value',
        // 'feature_value_lang',
        'pack',
        'search_index',
        'search_word',
        'specific_price',
        'specific_price_priority',
        'specific_price_rule',
        'specific_price_rule_condition',
        'specific_price_rule_condition_group',
        'stock',
        'stock_available',
        'stock_mvt',
        'warehouse',
        'attachment_import',
        'attribute_group_import',
        'attribute_import',
        'blog_category_import',
        'blog_post_import',
        'categorias_comunes_import',
        'category_import',
        'category_product_import',
        'combinaciones_import',
        'combinacionunica_import',
        'image_import',
        'lllote_import',
        'llote_import',
        'lote_import',
        'perfiles_prod_import',
        'product_import',
        'product_questions_import',
        'specific_price_import',
        'tarifalote_import',
        'tarifa_cabecera_import',
        'valores_nav_import',
        'video_import',
        'wk_bundle_cart_data',
        'wk_bundle_cart_data_final',
        'wk_bundle_order_detail',
        'wk_bundle_product',
        'wk_bundle_product_download',
        'wk_bundle_product_shop',
        'wk_bundle_section',
        'wk_bundle_section_discount',
        'wk_bundle_section_lang',
        'wk_bundle_section_map',
        'wk_bundle_section_shop',
        'wk_bundle_sub_product',
        'wk_bundle_sub_product_attribute',

    ];
}

function limpiar()
{

    $id_home = Configuration::getMultiShopValues('PS_HOME_CATEGORY');
    $id_root = Configuration::getMultiShopValues('PS_ROOT_CATEGORY');

    $db = Db::getInstance();
    $db->execute('DELETE FROM `'._DB_PREFIX_.'category` WHERE id_category NOT IN ('.implode(',', array_map('intval', $id_home)).', '.implode(',', array_map('intval', $id_root)).')');
    $db->execute('DELETE FROM `'._DB_PREFIX_.'category_lang` WHERE id_category NOT IN ('.implode(',', array_map('intval', $id_home)).', '.implode(',', array_map('intval', $id_root)).')');
    $db->execute('DELETE FROM `'._DB_PREFIX_.'category_shop` WHERE id_category NOT IN ('.implode(',', array_map('intval', $id_home)).', '.implode(',', array_map('intval', $id_root)).')');
    $db->execute('DELETE FROM `'._DB_PREFIX_.'category_group` WHERE id_category NOT IN ('.implode(',', array_map('intval', $id_home)).', '.implode(',', array_map('intval', $id_root)).')');
    $db->execute('ALTER TABLE `'._DB_PREFIX_.'category` AUTO_INCREMENT = '.(1 + max(array_merge($id_home, $id_root))));

    foreach (scandir(_PS_CAT_IMG_DIR_) as $dir) {
        if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $dir)) {
            unlink(_PS_CAT_IMG_DIR_.$dir);
        }
    }

    $tables = getCatalogRelatedTables();
    foreach ($tables as $table) {
        $db->execute('TRUNCATE TABLE `'._DB_PREFIX_.bqSQL($table).'`');
    }

    Image::deleteAllImages(_PS_PROD_IMG_DIR_);
    if (! file_exists(_PS_PROD_IMG_DIR_)) {
        mkdir(_PS_PROD_IMG_DIR_);
    }

    foreach (scandir(_PS_MANU_IMG_DIR_) as $dir) {
        if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $dir)) {
            unlink(_PS_MANU_IMG_DIR_.$dir);
        }
    }

    foreach (scandir(_PS_SUPP_IMG_DIR_) as $dir) {
        if (preg_match('/^[0-9]+(\-(.*))?\.jpg$/', $dir)) {
            unlink(_PS_SUPP_IMG_DIR_.$dir);
        }
    }

}

limpiar();
echo 'Proceso acabado';
