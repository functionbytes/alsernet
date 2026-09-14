<?php
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../config/config.inc.php');

// Función recursiva para obtener todos los productos de la categoría y sus subcategorías
function obtenerProductosPorCategoria($id_categoria) {
    $productos = [];

    // Obtener los productos de la categoría actual
    $query = "SELECT id_product FROM "._DB_PREFIX_."category_product WHERE id_category = $id_categoria";
    $result = Db::getInstance()->executeS($query);

    foreach ($result as $row) {
        $productos[] = $row['id_product'];
    }

    // Obtener las subcategorías de la categoría actual
    $query = "SELECT id_category FROM "._DB_PREFIX_."category WHERE id_parent = $id_categoria";
    $result = Db::getInstance()->executeS($query);

    foreach ($result as $row) {
        // Llamada recursiva para obtener los productos de la subcategoría
        $productos = array_merge($productos, obtenerProductosPorCategoria($row['id_category']));
    }

    return $productos;
}
// GOLF => miguel
// caza => gorca
$productos = obtenerProductosPorCategoria(3);
// Eliminar IDs de productos duplicados
$productos = array_unique($productos);

// Convertir el array de IDs de productos en una cadena separada por comas
$productos_string = implode(',', $productos);
// dump($productos_string);



$archivo = 'pruebafrv2.txt';

// Verifica si el archivo existe
if (file_exists($archivo)) {
    // Lee todo el contenido del archivo
    $contenido = file_get_contents($archivo);

    // Imprime el contenido del archivo
    $limit = nl2br($contenido); // nl2br() convierte los saltos de línea en etiquetas <br>
} else {
    echo "El archivo no existe.";
}



$sql = Db::getInstance()->ExecuteS(" select
                                        ap.id_product,
                                        GROUP_CONCAT(apl.name SEPARATOR ';') as name
                                    from
                                        aalv_product ap
                                        left join aalv_product_lang apl on ap.id_product = apl.id_product
                                    where
                                        apl.id_lang in (1,3)
                                        and ap.id_product in (".$productos_string.")
                                        and ap.active = 1
                                    GROUP BY ap.id_product limit 10 OFFSET ".$limit);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recibe los valores de los inputs
    foreach ($_POST as $key => $value) {
        foreach ($sql as $k => $val) {
            if($val['id_product'] == $key){
                $name = explode(';',$val['name']);
                if($name[1] != $value){
                    // dump("UPDATE aalv_product_lang SET `name` = '".pSQL($value)."' where id_lang = 3 AND id_product = ".$key);die();
                    Db::getInstance()->execute("UPDATE aalv_product_lang SET `name` = '".pSQL($value)."' where id_lang = 3 AND id_product = ".$key);
                }

            }
        }
    }
    $nuevoContenido = $limit + 10;
    if (file_put_contents($archivo, $nuevoContenido) !== false) {
        header("Location: https://a-alvarez.com/panel/pruebafr.php");
    }

    // $nombre_frances = $_POST['id'];

    // // Procesa los valores recibidos
    // foreach ($nombre_frances as $id_producto => $nombre) {
    //     echo "ID Producto: " . htmlspecialchars($id_producto) . " - Nombre Francés: " . htmlspecialchars($nombre) . "<br>";
    //     // Aquí puedes agregar la lógica para guardar los datos en una base de datos o realizar otra acción
    // }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tabla de Productos</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: left;
        }
        input.changed {
            background-color: #d4edda;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var inputs = document.querySelectorAll('.fr');
            inputs.forEach(function(input) {
                input.addEventListener('blur', function() {
                    input.classList.add('changed');
                });
            });
        });
    </script>
</head>
<body>
<form method="post" action="#">
    <table>
        <thead>
            <tr>
                <th>ID Producto</th>
                <th>Nombre Español</th>
                <th>Nombre Francés</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sql as $producto):
                $name = explode(";",$producto['name']);
                ?>
            <tr>
                <td style="width: 7%;"><?php echo htmlspecialchars($producto['id_product']); ?></td>
                <td style="width: 45%;"><?php echo htmlspecialchars($name[0]); ?></td>
                <td>
                    <input type="text" class='fr' name="<?php echo $producto['id_product']; ?>" value='<?php echo $name[1]; ?>' required style="width: 97%;">
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <button type="submit">Aprobado</button>
</form>

</body>
</html>