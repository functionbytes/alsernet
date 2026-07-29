<?php

ini_set('max_execution_time', 176000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

$datos = Db::getInstance()->executeS("select s.id_product, `from`, `to` from aalv_specific_price s LEFT JOIN aalv_product p ON p.id_product = s.id_product where p.active=1 AND id_country=0 AND `to` like '2024-06-27%' OR `from` like '2024-06-27%' ORDER BY id_product, `from` ASC;");
$contador = 0;
$contador_bien = 0;
foreach ($datos as $precio) {
    $producto = $precio['id_product'];
    $fecha_from = null;
    if ($precio['from'] == '2024-06-27 04:00:00.000') {
        $fecha_from = $precio['from'];
    }

    if ($producto != $producto_old && ! $fecha_from) {
        echo $producto_old."\n";
        $contador++;
    } else {
        $contador_bien++;
    }

    $producto_old = $precio['id_product'];
}

echo "\n\n".$contador."\n";
exit;

/*
include(dirname(__FILE__).'/importarerpcronscript.php');
$tarifa_cabecera = Db::getInstance()->executeS("select * from aalv_integracion_cambios where id=11977315")[0];
$tarifa_linea = Db::getInstance()->executeS("select * from aalv_integracion_cambios where id=11977316")[0];
ProcesarTarifaCabecera((array) json_decode($tarifa_cabecera['data']), $tarifa_cabecera["fila"], $tarifa_cabecera["tipo"] );
ProcesarTarifaLinea((array) json_decode($tarifa_linea["data"]), $tarifa_linea["fila"], $tarifa_linea["tipo"] );
die;
*/

/*
$datos = Db::getInstance()->executeS("
SELECT
        p.id_product,  SUM((SELECT SUM(s.quantity) FROM aalv_repositorio_stock AS s WHERE s.id_product_attribute=i.id_product_attribute)) AS stock
    FROM
        aalv_product p
    INNER JOIN aalv_product_attribute a ON
        a.id_product = p.id_product
    INNER JOIN aalv_combinaciones_import i ON
        i.id_product_attribute = a.id_product_attribute
    where
        (i.estado_gestion = 1 OR i.estado_gestion = 2)
        #AND i.externo_disponibilidad = 1
        AND i.etiqueta NOT LIKE '%OCULTO WEB%'
        AND p.active = 0
    GROUP BY
        p.id_product
    ORDER BY stock DESC");
foreach ($datos as $key => $value) {
   //if ($value['stock']>0) {
     echo $value['id_product']."\n";
     $product = new Product($value['id_product']);
     $product->active = true;
     $product->update();
   //}
}
*/

// Ruta a la carpeta de imágenes de PrestaShop
// $rutaImagenes = '../img/p/';

// Función para buscar imágenes de manera recursiva y filtrar por nombres numéricos
// function buscarImagenesRecursivamente($directorio, &$resultado, &$contador)
// {
//     $archivos = glob($directorio . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
//     $subdirectorios = glob($directorio . '*', GLOB_ONLYDIR);

//     foreach ($archivos as $archivo) {
//         // Obtén el nombre del archivo sin la extensión
//         $nombreArchivo = pathinfo($archivo, PATHINFO_FILENAME);

//         // Verifica si el nombre del archivo es un número
//         if (is_numeric($nombreArchivo)) {
//             // Agrega el resultado al array
//             $resultado[] = [
//                 "name" => $nombreArchivo,
//                 "ruta" => $directorio,
//                 "imagen" => $directorio.basename($archivo)
//             ];

//             // Incrementa el contador
//             $contador++;

//             // Verifica si se han encontrado los primeros 20 resultados
//             // if ($contador >= 20) {
//             //     return; // Termina la búsqueda
//             // }
//         }
//     }

//     foreach ($subdirectorios as $subdirectorio) {
//         // Llama recursivamente a la función para explorar los subdirectorios
//         buscarImagenesRecursivamente($subdirectorio . '/', $resultado, $contador);

//         // Verifica nuevamente si se han encontrado los primeros 20 resultados
//         // if ($contador >= 20) {
//         //     return; // Termina la búsqueda
//         // }
//     }
// }

// // Inicializa un array para almacenar los resultados
// $resultadoArray = array();

// // Inicializa un contador
// $contador = 0;

// // // Llama a la función para buscar imágenes de manera recursiva y llenar el array de resultados
// buscarImagenesRecursivamente($rutaImagenes, $resultadoArray, $contador);
// $nn = 2;
// foreach ($resultadoArray as $key => $value) {
//     $tablas = Db::getInstance()->ExecuteS("SELECT * FROM aalv_image ai WHERE id_image = ".$value['name']);
//     if(count($tablas) == 0){
//         Db::getInstance()->ExecuteS("INSERT INTO aalv_image (id_image, id_product, position, cover) VALUES (".$value['name'].", 61309, ".$nn.", null)");
//         Db::getInstance()->ExecuteS("INSERT INTO aalv_image_shop (id_product, id_image, id_shop, cover) VALUES (61309 , ".$value['name'].", 1, null)");

//         $nn++;
//     }

// }

// $tablas = Db::getInstance()->ExecuteS(" SELECT
//                                             i.id_image,
//                                             i.id_product,
//                                             i.position,
//                                             i.cover,
//                                             ish.id_shop
//                                         FROM
//                                             aalv_image i
//                                             LEFT JOIN aalv_image_shop ish ON i.id_image = ish.id_image");

//     // Recorrer las filas de resultados
// foreach ($tablas as $row) {
//     $idImage = $row['id_image'];
//     $imagePath = $rutaImagenes;

//     // Separar cada dígito del id_image en una carpeta
//     $idImageStr = strval($idImage);
//     for ($i = 0; $i < strlen($idImageStr); $i++) {
//         $imagePath .= $idImageStr[$i] . "/";
//     }

//     $imagePath .= $idImage . ".jpg";
//     // Verificar si la imagen existe físicamente en el servidor
//     if (!file_exists($imagePath)) {
//         echo "La imagen NO existe en el servidor => ".$idImage.".<br>";
//     }
// }
