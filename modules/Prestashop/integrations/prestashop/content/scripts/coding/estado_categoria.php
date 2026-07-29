<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}

include(dirname(__FILE__) . '/../config/config.inc.php');
include(dirname(__FILE__) . '/../init.php');

// Obtener todas las categorías con su estado (activo o no)
$categories = Db::getInstance()->executeS('SELECT id_category, active FROM '._DB_PREFIX_.'category');

// Arrays para almacenar categorías según la condición
$categorias_activas_sin_productos = array();
$categorias_desactivadas_con_productos = array();

// Recorrer cada categoría
foreach ($categories as $category) {
    $id_category = (int)$category['id_category'];
    $active = (int)$category['active'];

    // Contar productos en la categoría
    $count_products = Db::getInstance()->getValue('
        SELECT COUNT(*) FROM '._DB_PREFIX_.'category_product
        WHERE id_category = '.$id_category);

    // Verificar condiciones y agregar a los arrays correspondientes
    if ($active == 1 && $count_products == 0) {
        $categorias_activas_sin_productos[] = $id_category;
    } elseif ($active == 0 && $count_products > 0) {
        $categorias_desactivadas_con_productos[] = $id_category;
    }
}

// Mostrar resultados
if (!empty($categorias_activas_sin_productos)) {
    echo 'Categorías activas sin productos: <br>';
    foreach ($categorias_activas_sin_productos as $id_category) {
        echo 'ID de categoría: ' . $id_category . '<br>';
    }
} else {
    echo 'No hay categorías activas sin productos.<br>';
}

if (!empty($categorias_desactivadas_con_productos)) {
    echo 'Categorías desactivadas con productos: <br>';
    foreach ($categorias_desactivadas_con_productos as $id_category) {
        echo 'ID de categoría: ' . $id_category . '<br>';
    }
} else {
    echo 'No hay categorías desactivadas con productos.';
}