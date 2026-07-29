<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
// include (dirname(__FILE__).'/../init.php');

/*
*   Saber que categoria no existe su padre
*/

// SELECT
// 	id_category,
// 	id_parent
// FROM
// 	aalv_category
// WHERE
// 	id_parent NOT IN (SELECT id_category FROM aalv_category)
// 	and id_category != 1;

/* FIN */

/*
*   Saber que categorias tienen Lang y no existen su categoria
*/

// SELECT
// 	cl.id_category,
// 	cl.id_lang, cl.name
// FROM
// 	aalv_category_lang cl
// 	LEFT JOIN aalv_category c ON cl.id_category = c.id_category
// WHERE
// 	c.id_category IS NULL
// 	and cl.id_category != 0
// GROUP BY cl.id_category
// order by cl.id_category ASC

/* FIN */

/*
* Saber que productos ya no tienen categorias que no existan
*/

// SELECT
// 	cp.id_category,
// 	cp.id_product
// FROM
// 	aalv_category_product cp
// 	LEFT JOIN aalv_category c ON cp.id_category = c.id_category
// WHERE
// 	c.id_category IS NULL
// 	and cp.id_category NOT IN (0,1)
// GROUP BY cp.id_category
// ORDER BY cp.id_category ASC

/* FIN */

/*
**********   VER CATEGORIAS HIJAS Y PRODUCTOS QUE TIENEN   **********
*/
// Función recursiva para obtener subcategorías y productos asociados
// function obtenerProductosPorCategoria($idCategoria) {
//      // Obtener la categoría actual
//      $categoria = new Category($idCategoria);

//      // Inicializar el array para almacenar resultados
//      $resultado = array(
//          'id_padre' => $categoria->id,
//          'name_padre' => $categoria->name,
//          'productos' => array()
//      );

//     // $productosCategoria = $categoria->getProducts(Context::getContext()->language->id, 1,1);
//     // var_dump($productosCategoria); die();
//     $productosCategoria = Db::getInstance()->ExecuteS('
//                                              SELECT
//                                                  cp.`id_product`
//                                              FROM
//                                                  `' . _DB_PREFIX_ . 'category_product` cp
//                                              WHERE
//                                                  cp.`id_category` = ' . $idCategoria . '
//                                              ORDER BY `position` ASC');

//     // Agregar los productos al resultado
//     foreach ($productosCategoria as $producto) {
//         $resultado['productos'][] = array('id_producto' => $producto['id_product']);
//     }
//     // Obtener subcategorías de la categoría dada
//     $subcategorias = $categoria->getSubCategories(Context::getContext()->language->id);

//     foreach ($subcategorias as $subcategoria) {
//         // Obtener los productos asociados a esta subcategoría de forma recursiva
//         $productosSubcategoria = obtenerProductosPorCategoria($subcategoria['id_category']);

//         // Si hay subcategorías con productos, agregarlas al resultado de la categoría actual
//         if (!empty($productosSubcategoria)) {
//             if (!isset($resultado['hijas'])) {
//                 $resultado['hijas'] = array();
//             }
//             $resultado['hijas'][] = array(
//                 'id_padre' => $subcategoria['id_category'],
//                 'name_padre' => $subcategoria['name'],
//                 'productos' => $productosSubcategoria
//             );
//         }
//     }

//     return $resultado;
// }

// // Ejemplo de uso: obtener productos de una categoría y sus subcategorías
// $idCategoria = 63184; // ID de la categoría raíz (cambia esto por el ID de la categoría que desees)
// $resultado = obtenerProductosPorCategoria($idCategoria);

// // Imprimir el resultado
// echo json_encode($resultado, JSON_PRETTY_PRINT);
/*
**********   VER CATEGORIAS HIJAS Y PRODUCTOS QUE TIENEN [ FIN ]  **********
*/

/*
**********   ELIMINAR CATEGORIAS Y SUS HIJAS  **********
*/

// function eliminarCategoriaYHijas($id_categoria) {
//     // Obtener todas las categorías hijas de la categoría especificada
//     $subcategorias = Db::getInstance()->ExecuteS("SELECT id_category FROM aalv_category WHERE id_parent = ".$id_categoria);

//     // Recorrer todas las subcategorías
//     foreach ($subcategorias as $subcategoria) {
//         // Llamar recursivamente a la función para eliminar las subcategorías hijas
//         eliminarCategoriaYHijas($subcategoria['id_category']);
//     }

//     // Obtener todos los productos que pertenecen a la categoría especificada
//     $productos = Db::getInstance()->ExecuteS("SELECT id_category , id_product  from aalv_category_product acp WHERE id_category = ".$id_categoria);

//     // Iterar sobre los productos y eliminar la categoría
//     foreach ($productos as $producto) {
//         $producto_obj = new Product($producto['id_product']);

//         // Obtener las categorías actuales del producto
//         $categorias_producto = $producto_obj->getCategories();

//         // Verificar si el producto pertenece a la categoría especificada
//         if (in_array($id_categoria, $categorias_producto)) {
//             // Eliminar la categoría especificada del producto
//             $producto_obj->deleteCategory($id_categoria);
//             echo "Categoría eliminada del producto con ID: " . $producto['id_product'] . "<br>";
//         } else {
//             echo "El producto con ID: " . $producto['id_product'] . " no pertenece a la categoría especificada <br>";
//         }
//     }

//     // Eliminar la categoría
//     $categoria = new Category($id_categoria);
//     if ($categoria->delete()) {
//         echo "Categoría eliminada exitosamente.<br>";
//     } else {
//         echo "Error al eliminar la categoría.<br>";
//     }
// }
// //
// //
// // ID de la categoría que deseas eliminar de los productos
// $id_categoria_array = [

// ]; // Reemplaza esto con el ID de la categoría que deseas eliminar

// foreach ($id_categoria_array as  $id_categoria) {
//     eliminarCategoriaYHijas($id_categoria);
//     echo "<hr>";
// }

// echo "Proceso completado";

/*
**********   ELIMINAR CATEGORIAS Y SUS HIJAS [ FIN ]  **********
*/

// Función para desactivar una categoría y sus subcategorías de forma recursiva
// function desactivarCategoriasRecursivamente($idCategoria) {
//     // Desactivar la categoría
//     $category = new Category($idCategoria);
//     $category->active = false;
//     $category->update();

//     // Obtener las subcategorías de la categoría actual
//     $subcategorias = $category->getSubCategories(Context::getContext()->language->id);

//     // Recorrer las subcategorías y desactivarlas recursivamente
//     foreach ($subcategorias as $subcategoria) {
//         desactivarCategoriasRecursivamente($subcategoria['id_category']);
//     }
// }

// ID de la categoría que deseas desactivar (cambia esto por el ID de la categoría que desees)
// desactivarCategoriasRecursivamente(366);
// echo "Proceso completado";

// $dbcon = connectBD();
// $GLOBALS['EXCLUIR_CATEGORY'] = "0,1,2,3,4,5,6,7,8,9,10,11";

// $GLOBALS['EXCLUIR_CATEGORY'] .= ','.implode(",", obtenerIdsCategoriasHijas(2821));
// $GLOBALS['EXCLUIR_CATEGORY'] .= ','.implode(",", obtenerIdsCategoriasHijas(2820));

// // Verificar si la última letra es una coma
// if (substr($GLOBALS['EXCLUIR_CATEGORY'], -1) === ',') {
//     // Eliminar la coma
//     $GLOBALS['EXCLUIR_CATEGORY'] = substr($GLOBALS['EXCLUIR_CATEGORY'], 0, -1);
// }

// $GLOBALS['EXCLUIR_CATEGORY'] = explode(",",$GLOBALS['EXCLUIR_CATEGORY']);
// $GLOBALS['EXCLUIR_CATEGORY'] = array_unique($GLOBALS['EXCLUIR_CATEGORY']);
// $GLOBALS['EXCLUIR_CATEGORY'] = implode(",", $GLOBALS['EXCLUIR_CATEGORY']);

// dump($GLOBALS);die();

function ProcesarPerfilesNav($data, $fila, $tipo)
{

    if ($tipo <= 2) {

        if (! $data) {
            // sendmail(__FUNCTION__.": Dato nulo en data para fila ".$fila." tipo ".$tipo);
            return 1;
        }

        $idmodelo = $data['id_modelo'];
        $idvalor = $data['id_valor'];
        $principal = $data['principal'];

        $idprodps = ''.Db::getInstance()->getValue('SELECT id_product FROM aalv_product_import WHERE id_modelo='.$idmodelo);

        if ($idprodps != '') {

            $catimport = Db::getInstance()->ExecuteS('SELECT * FROM aalv_category_import WHERE id_origen='.$data['id_valor']);

            foreach ($catimport as $catim) {
                $idcatps = $catim['id_cat'];
                $idnav = $catim['id_nav'];

                $existe = ''.Db::getInstance()->getValue('SELECT id_category FROM aalv_category_product WHERE id_category = '.$idcatps.' and id_product='.$idprodps);

                if ($existe != '') {
                    // update, no hacer nada ya que está, pero mirar si cambia principal

                    if (ExistePathCategory($idprodps, $idnav)) {
                        if ($principal) {
                            // Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            // Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            if (escomunrec($idcatps)) {

                                $cat = new Category($idcatps);
                                if ($cat->sport == 5) {

                                    Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                    Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                }
                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                            }
                        }
                    }
                } else {

                    Db::getInstance()->Execute('INSERT INTO aalv_category_product(id_category, id_product, position) VALUES ('.$idcatps.','.$idprodps.',0)');

                    if (ExistePathCategory($idprodps, $idnav)) {

                        if ($principal) {

                            // Db::getInstance()->Execute("UPDATE aalv_product SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);
                            // Db::getInstance()->Execute("UPDATE aalv_product_shop SET id_category_default=".$idcatps." WHERE id_product=".$idprodps);

                            if (escomunrec($idcatps)) {

                                $cat = new Category($idcatps);
                                if ($cat->sport == 5) {

                                    Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                    Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                }
                            } else {
                                Db::getInstance()->Execute('UPDATE aalv_product SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                                Db::getInstance()->Execute('UPDATE aalv_product_shop SET id_category_default='.$idcatps.' WHERE id_product='.$idprodps);
                            }
                        }
                    }
                }

                $product = new Product($idprodps);

                $product->id_category_default = Db::getInstance()->getValue('select id_category_default from aalv_product WHERE id_product='.$idprodps);
                $product->update();

                Db::getInstance()->Execute('REPLACE INTO aalv_category_product_import(id_category, id_product, fila) VALUES ('.$idcatps.','.$idprodps.','.$data['id'].')');
            }

            return 1;
        }
    }
}

function escomunrec($id)
{

    $padre = Db::getInstance()->getValue('SELECT id_parent FROM '._DB_PREFIX_.'category WHERE id_category='.$id);
    $escomun = ''.Db::getInstance()->getValue('SELECT id_cat FROM aalv_categorias_comunes_import WHERE id_cat= '.$id);

    if ($escomun == '') {

        if ($padre <= 2) {
            return false;
        } else {
            return escomunrec($padre);
        }
    } else {
        return true;
    }
}

function ExistePathCategory($producto, $id_nav)
{

    if (! escomun($id_nav) && ($id_nav != 0)) {

        $elemento = Db::getInstance()->getValue('SELECT id_origen FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_padre = Db::getInstance()->getValue('SELECT id_padre FROM aalv_category_import WHERE id_nav='.$id_nav);
        $id_cat = Db::getInstance()->getValue('SELECT id_cat FROM aalv_category_import WHERE id_nav='.$id_nav);

        // ver si existe id_cat  y producto en  category_import

        if (''.$id_cat != '') {
            $existe = ''.Db::getInstance()->getValue('select id_category from aalv_category_product where id_category='.$id_cat.' and id_product='.$producto);

            if ($existe != '') {
                return ExistePathCategory($producto, (int) $id_padre);
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return true;
    }
}

function connectBD()
{

    return $dbcon;
}

function closeBD($dbcon)
{
    mysqli_close($dbcon);
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, 'alsernet:May.8006763');
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

function obtenerIdsCategoriasHijas($id_categoria)
{
    $ids = [$id_categoria]; // Incluir el ID de la categoría padre
    $sql = Db::getInstance()->ExecuteS('SELECT id_category FROM aalv_category WHERE id_parent = '.$id_categoria);
    foreach ($sql as $re) {
        $ids[] = $re['id_category'];

        // Recursivamente obtener las categorías hijas de esta categoría
        $sub_ids = obtenerIdsCategoriasHijas($re['id_category']);
        // Fusionar los IDs de las categorías hijas encontradas con los IDs actuales
        $ids = array_merge($ids, $sub_ids);
    }

    return $ids;
}

// ///////////////////////////////////////////////////////////////////////////////////////////

// // Función recursiva para contar categorías hijas
// function contarCategoriasHijas($id_padre) {
//     $db = Db::getInstance();

//     $query = 'SELECT id_category FROM '._DB_PREFIX_.'category WHERE id_parent = '.(int)$id_padre;
//     $categorias = $db->executeS($query);

//     $conteo = 0;

//     foreach ($categorias as $categoria) {
//         $conteo += 1; // Contar la categoría actual
//         $conteo += contarCategoriasHijas($categoria['id_category']); // Contar recursivamente las categorías hijas
//     }

//     return $conteo;
// }

// // ID del padre para el que quieres contar las categorías
// $id_padre = 2821; // Cambia esto al ID del padre que necesitas

// $conteo_total = contarCategoriasHijas($id_padre);

// echo "El ID padre $id_padre tiene un total de $conteo_total categorías hijas.\n";

// ///////////////////////////////////////////////////////////////////////////////////////////
// $datos = Db::getInstance()->executeS("select id_product from aalv_combinacionunica_import where etiqueta LIKE '%HOC24%'");

// $datos = Db::getInstance()->executeS("SELECT
//                                                                 pa.id_product
//                                                             FROM
//                                                                 aalv_product_attribute pa
//                                                                 left join aalv_combinaciones_import aci on aci.id_product_attribute = pa.id_product_attribute
//                                                             where pa.id_product != 0 and pa.id_product is not NULL and aci.etiqueta LIKE '%HOC24%'");

// $categoryId = 104649; // Reemplaza con el ID de la categoría que quieres agregar

// foreach ($datos as $value) {
//     # code...
//     // Cargar el producto
//     $product = new Product($value['id_product']);

//     if (!Validate::isLoadedObject($product)) {
//         die('Producto no encontrado.');
//     }
//     // Añadir la categoría al producto si no está ya asociada
//     if (!in_array($categoryId, $product->getCategories())) {
//         $product->addToCategories([$categoryId]);

//         // Guardar los cambios
//         if ($product->update()) {
//             echo "Categoría agregada correctamente al producto.\n";
//         } else {
//             echo "Error al actualizar el producto.\n";
//         }

//     } else {
//         echo "El producto ya está asociado a esta categoría.\n";
//     }

//     // dump($value['id_product']);die();
// }

// // ID del producto y de la categoría a asociar
// $productId = 1;  // Reemplaza con el ID de tu producto
