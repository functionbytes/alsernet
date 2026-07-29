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

function getMetaBrans()
{

    $db = Db::getInstance();

    // Obtener todos los fabricantes
    $manufacturers = $db->executeS('SELECT id_manufacturer, name FROM '._DB_PREFIX_.'manufacturer');

    // Definir las plantillas de meta_description
    $metaDescriptions = [
        1 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        2 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        3 => 'Découvrez notre sélection de produits [nombre] et achetez-les en ligne au meilleur prix. Álvarez, le plus grand magasin de sport et de loisirs.',
        4 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        5 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
    ];

    foreach ($manufacturers as $manufacturer) {

        $idManufacturer = $manufacturer['id_manufacturer'];
        $name = $manufacturer['name'];

        // Obtener los registros de la tabla intermedia para este fabricante
        $langs = $db->executeS('
            SELECT id_lang
            FROM '._DB_PREFIX_.'manufacturer_lang
            WHERE id_manufacturer = '.(int) $idManufacturer.'
            AND (meta_title = "" OR meta_title IS NULL OR meta_description = "" OR meta_description IS NULL)
        ');

        foreach ($langs as $lang) {

            $idLang = (int) $lang['id_lang'];

            // Crear meta_title y meta_description
            $metaTitle = "$name - Álvarez";
            $metaDescription = str_replace('[nombre]', $name, $metaDescriptions[$idLang]);

            // Actualizar la tabla intermedia
            $db->execute('
                UPDATE '._DB_PREFIX_.'manufacturer_lang
                SET meta_title = "'.pSQL($metaTitle).'", meta_description = "'.pSQL($metaDescription).'"
                WHERE id_manufacturer = '.(int) $idManufacturer.' AND id_lang = '.(int) $idLang
            );
        }
    }

}

function getMetaCategory()
{

    $db = Db::getInstance();

    $manufacturerCategories = $db->executeS('SELECT * FROM '._DB_PREFIX_.'manufacturer_category');

    // Definir las plantillas de meta_description
    $metaDescriptions = [
        1 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        2 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        3 => 'Découvrez notre sélection de produits [nombre] et achetez-les en ligne au meilleur prix. Álvarez, le plus grand magasin de sport et de loisirs.',
        4 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
        5 => 'Descubre nuestra selección de productos [nombre] y cómpralos online al mejor precio. Alvarez, la mayor tienda de deporte y tiempo libre',
    ];

    foreach ($manufacturerCategories as $relation) {

        $idManufacturer = (int) $relation['id_manufacturer'];
        $idCategory = (int) $relation['id_category'];

        $manufacturer = $db->getRow('SELECT name FROM '._DB_PREFIX_.'manufacturer WHERE id_manufacturer = '.$idManufacturer);
        $manufacturerName = $manufacturer['name'];

        // Obtener el nombre de la categoría
        $category = $db->getRow('SELECT name FROM '._DB_PREFIX_.'category_lang WHERE id_category = '.$idCategory.' AND id_lang = 1');
        $categoryName = $category['name'];

        // Obtener los registros de la tabla intermedia para este fabricante
        $langs = $db->executeS('
                SELECT id_lang
                FROM '._DB_PREFIX_.'category_lang
                WHERE id_category = '.$idCategory.'
            ');

        foreach ($langs as $lang) {

            $idLang = (int) $lang['id_lang'];

            // Crear meta_title y meta_description
            $metaTitle = "$manufacturerName - Alvarez";
            $metaDescription = str_replace('[nombre]', $manufacturerName, $metaDescriptions[$idLang] ?? $metaDescriptions[1]);

            // Actualizar la tabla intermedia
            $db->execute('
                UPDATE '._DB_PREFIX_.'category_lang
                SET meta_title = "'.pSQL($metaTitle).'",
                    meta_description = "'.pSQL($metaDescription).'"
                WHERE id_category = '.$idCategory.'
                  AND id_lang = '.$idLang
            );

        }

    }

}

// getMetaCategory();
