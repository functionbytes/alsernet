<?php
// Incluir los archivos de configuración y de inicialización de PrestaShop
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Obtener los datos de la base de datos correspondientes al ID del modelo proporcionado en el parámetro POST.
$buscar = Db::getInstance()->executeS("SELECT * FROM aalv_alsernet_imagenes WHERE id_modelo = ".$_POST['id_modelo']);

// Verificar si no se encontraron datos con el ID del modelo proporcionado
if (!$buscar) {
    // Si no se encontraron datos, insertar un nuevo registro en la tabla aalv_alsernet_imagenes con el ID del modelo proporcionado.
    $db = Db::getInstance()->executeS("INSERT INTO aalv_alsernet_imagenes (id_modelo) VALUES (".$_POST['id_modelo'].")");
}

// Devolver una respuesta JSON indicando que la operación se realizó correctamente.
echo json_encode("ok");
