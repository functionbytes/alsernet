<?php
// Incluir los archivos de configuración y de inicialización de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

/**
 * Función que realiza cambios en la tabla aalv_product_attribute_image eliminando las imágenes asociadas a un atributo de producto específico y luego inserta nuevas imágenes.
 * @param {Array} $ids - Arreglo con los IDs de las imágenes que se asociarán al atributo de producto.
 * @param {string} $refencia - El ID del atributo de producto al cual se asociarán las nuevas imágenes.
 */
function Cambios($ids, $refencia) {
    // Eliminar las imágenes asociadas al atributo de producto específico en la tabla aalv_product_attribute_image
    $db = Db::getInstance()->executeS("DELETE FROM aalv_product_attribute_image WHERE id_product_attribute = ".$refencia);

    // Crear la consulta SQL para insertar nuevas imágenes asociadas al atributo de producto.
    $sql = "INSERT INTO aalv_product_attribute_image (id_product_attribute, id_image) VALUES ";

    for ($i = 0; $i < count($ids); $i++) {
        $sql .= "(".$refencia.", ".$ids[$i]."),";
    }

    $sql = substr($sql, 0, -1);

    // Ejecutar la consulta SQL para insertar las nuevas imágenes.
    Db::getInstance()->executeS($sql);
}

// Obtener los datos enviados a través del parámetro POST.
$datos = $_POST['data'];

// Iterar sobre los datos y llamar a la función Cambios para cada conjunto de IDs de imágenes y atributo de producto.
for ($i = 0; $i < count($datos); $i++) {
    Cambios($datos[$i]['ids'], $datos[$i]['referencia']);
}