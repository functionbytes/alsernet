<?php
// Incluir los archivos de configuración y de inicialización de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');
include(dirname(__FILE__).'/../../classes/ImageManager.php');

/**
 * Función que busca información de los atributos de productos y las imágenes asociadas a un modelo específico.
 * @param {string} id_modelo - El ID del modelo de producto para el cual se buscará información.
 * @return {Array} - Un arreglo con la información de los atributos de productos y las imágenes asociadas al modelo.
 */
function buscarDatos($id_modelo) {
    // Verificar si se está realizando una solicitud para obtener información de atributos de productos y las imágenes.
    if ($_GET['datos'] == 'v1') {
        // Realizar una consulta a la base de datos para obtener los atributos de productos y las imágenes asociadas al modelo específico.
        $db = Db::getInstance()->executeS("SELECT DISTINCT(imp.id_modelo),
                                                att.id_product_attribute,
                                                att.reference,
                                                lang.name,
                                                (SELECT GROUP_CONCAT(lan.name,' - ',lang2.name)
                                                FROM aalv_product_attribute_combination com
                                                INNER JOIN aalv_attribute_lang lang2 ON lang2.id_attribute = com.id_attribute
                                                INNER JOIN aalv_attribute att ON att.id_attribute = com.id_attribute
                                                INNER JOIN aalv_attribute_group_lang lan ON lan.id_attribute_group = att.id_attribute_group
                                                WHERE com.id_product_attribute = att.id_product_attribute
                                                AND lang2.id_lang = 1
                                                AND lan.id_lang = 1) AS nombre,
                                                IF(ava.quantity = 0,'No activo','Si activo') AS active,
                                                (SELECT ima.id_image
                                                FROM aalv_product_attribute_image ima
                                                INNER JOIN aalv_image impo ON impo.id_image = ima.id_image
                                                WHERE ima.id_product_attribute = att.id_product_attribute
                                                ORDER BY impo.position ASC LIMIT 1) AS imagen,
                                                (SELECT impo.id_image
                                                FROM aalv_product_import im
                                                INNER JOIN aalv_image impo ON impo.id_product = im.id_product
                                                WHERE im.id_modelo = imp.id_modelo
                                                AND impo.cover = true) AS portada
                                            FROM aalv_product_import imp
                                            INNER JOIN aalv_product_attribute att ON att.id_product = imp.id_product
                                            INNER JOIN aalv_product_lang lang ON lang.id_product = att.id_product
                                            INNER JOIN aalv_stock_available ava ON ava.id_product_attribute = att.id_product_attribute
                                            WHERE lang.id_lang = 1
                                            AND imp.id_modelo = ".$id_modelo);

        // Procesar las URLs de las imágenes utilizando la función CrearUrlImagen
        for ($i = 0; $i < count($db); $i++) {
            $db[$i]['imagen'] = CrearUrlImagen($db[$i]['imagen']);
            $db[$i]['portada'] = CrearUrlImagen($db[$i]['portada']);
        }
    } else {
        // Realizar una consulta a la base de datos para obtener las imágenes asociadas al modelo específico.
        $imagenes = Db::getInstance()->executeS("SELECT impo.id_image
                                                FROM aalv_product_import imp
                                                INNER JOIN aalv_image impo ON impo.id_product = imp.id_product
                                                WHERE imp.id_modelo = ".$id_modelo."
                                                ORDER BY impo.position ASC");

        // Obtener la URL base del servidor de medios
        $mediaServerUrl = Tools::getMediaServer(_PS_IMG_); // Puedes utilizar _PS_IMG_ para la carpeta 'img'

        // Procesar las URLs de las imágenes utilizando la función CrearUrlImagen
        for ($i = 0; $i < count($imagenes); $i++) {
            // Convertir el identificador en un array de dígitos
            $digits = str_split($imagenes[$i]['id_image']);

            // Construir la ruta de la imagen
            $imagePath = '/img/p/';

            foreach ($digits as $digit) {
                $imagePath .= $digit . '/';
            }

            $imagePath .= $imagenes[$i]['id_image'] . '-home_default.jpg';

            $db['imagen'][$i]['url'] = $imagePath;
            $db['imagen'][$i]['id_image'] = $imagenes[$i]['id_image'];
        }

        // Realizar una consulta para obtener la imagen seleccionada en el campo de selección de imágenes
        $db['select'] = Db::getInstance()->executeS("SELECT ima.id_image
                                                    FROM aalv_product_attribute_image ima
                                                    INNER JOIN aalv_image impo ON impo.id_image = ima.id_image
                                                    WHERE id_product_attribute = ".$_POST['refencia']);
    }

    // Devolver el arreglo con la información de los atributos de productos y las imágenes asociadas al modelo.
    return $db;
}

/**
 * Función que crea la URL de la imagen a partir del ID de la imagen.
 * @param {string} id_image - El ID de la imagen para la cual se creará la URL.
 * @return {string} - La URL completa de la imagen.
 */
function CrearUrlImagen($id_image) {
    // Verificar si el ID de la imagen está vacío
    if ($id_image == '') {
        // Si está vacío, devolver la URL de una imagen de reemplazo.
        return '/modules/alsernetimagenes/logo.png';
    }

    // Obtener la URL base del servidor de medios
    $mediaServerUrl = Tools::getMediaServer(_PS_IMG_); // Puedes utilizar _PS_IMG_ para la carpeta 'img'

    // Convertir el identificador en un array de dígitos
    $digits = str_split($id_image);

    // Construir la ruta de la imagen
    $imagePath = '/img/p/';

    foreach ($digits as $digit) {
        $imagePath .= $digit . '/';
    }

    $imagePath .= $id_image . '-home_default.jpg';

    // Devolver la URL completa de la imagen.
    return $imagePath;
}

// Obtener el ID del modelo de producto a través del parámetro POST
$id_modelo = $_POST['id_modelo'];

// Ejecutar la función buscarDatos con el ID del modelo de producto y obtener la información correspondiente.
$db = buscarDatos($id_modelo);

// Devolver la información en formato JSON
echo json_encode($db);


?>