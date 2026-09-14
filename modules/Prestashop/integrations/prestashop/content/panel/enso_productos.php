<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

$EXCLUIR_CATEGORY = '0,1,2,12';

$EXCLUIR_CATEGORY .= ','.implode(',', obtenerIdsCategoriasHijas(2820));
$EXCLUIR_CATEGORY .= ','.implode(',', obtenerIdsCategoriasHijas(2821));

// Verificar si la última letra es una coma
if (substr($EXCLUIR_CATEGORY, -1) === ',') {
    // Eliminar la coma
    $EXCLUIR_CATEGORY = substr($EXCLUIR_CATEGORY, 0, -1);
}

$EXCLUIR_CATEGORY = explode(',', $EXCLUIR_CATEGORY);
$EXCLUIR_CATEGORY = array_unique($EXCLUIR_CATEGORY);
$EXCLUIR_CATEGORY = implode(',', $EXCLUIR_CATEGORY);

$lotes = '12024,17398,24764,24767,39470,51525,55762,52930,52938,55050,56764,19141,44689,57934,57946,57950,57952,57954,61373';

$exepcion = ',61492,61580,62204,62420';

$yaseenviaran = '';

$limit = '40000,10000';

// Traemos todo los productos activos
// $todos = Db::getInstance()->ExecuteS("SELECT * FROM aalv_product_shop WHERE active = true AND id_product NOT IN (".$lotes.$exepcion.$yaseenviaran.") ORDER BY id_product asc limit ".$limit);
$todos = Db::getInstance()->ExecuteS('SELECT * FROM aalv_product_shop WHERE active = true AND id_product IN (49974) ORDER BY id_product asc');
$array = [];
$contador = 0;
foreach ($todos as $value) {

    $datos = Db::getInstance()->ExecuteS("SELECT meta_title AS titulo, `description`,CONCAT('".$_SERVER['HTTP_HOST']."/',al.iso_code,'/',id_product,'-',link_rewrite) AS url FROM aalv_product_lang apl LEFT JOIN aalv_lang al ON al.id_lang = apl.id_lang WHERE apl.id_product = ".$value['id_product']);
    foreach ($datos as $va) {
        $array[$contador]['titulo'][] = $va['titulo'];
        // $array[$contador]['description'][] = $va['description'];
        $array[$contador]['url'][] = $va['url'];
    }

    $datos = Db::getInstance()->getValue('SELECT id_image FROM aalv_image ai WHERE cover = 1 AND id_product = '.$value['id_product']);
    $array[$contador]['imagen'] = '';
    if ($datos) {
        $array[$contador]['imagen'] = $_SERVER['HTTP_HOST'].'/img/p/'.urlImagen($datos);
    }

    $datos = Db::getInstance()->getValue('SELECT GROUP_CONCAT(id_category) FROM aalv_category_product acp WHERE id_category NOT IN ('.$EXCLUIR_CATEGORY.') AND id_product = '.$value['id_product']);
    $array[$contador]['categorias'] = $datos;

    $datos = Db::getInstance()->ExecuteS('SELECT apa.reference, apa.quantity, asp.id_country, asp.price, asp.reduction FROM aalv_product_attribute apa LEFT JOIN aalv_specific_price asp ON asp.id_product_attribute = apa.id_product_attribute WHERE apa.id_product = '.$value['id_product'].' AND asp.`to` IS NULL ORDER BY apa.reference DESC');

    if (count($datos) == 0) {
        $datos = Db::getInstance()->ExecuteS('  SELECT ap.reference,asa.quantity,asp.id_country,asp.price,asp.reduction FROM aalv_product ap LEFT JOIN aalv_stock_available asa ON asa.id_product = ap.id_product LEFT JOIN aalv_specific_price asp ON asp.id_product = ap.id_product WHERE ap.id_product = '.$value['id_product'].' AND asp.`to` IS NULL');
    }
    foreach ($datos as $key => $va) {
        $stock = true;
        if ($va['quantity'] == 0) {
            $stock = false;
        }
        $precio = saberprecio($va['id_country'], $va['price']);
        $descuento = saberprecio($va['id_country'], $va['reduction']);
        $total = $precio - $descuento;
        $total = number_format($total, 2, '.', '');
        if (array_key_exists($va['reference'], $array[$contador]['combinaciones'])) {
            $ddato = [
                saberCountry($va['id_country']) => [
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'total' => $total,
                ],
            ];
            $array[$contador]['combinaciones'][$va['reference']] = array_merge($array[$contador]['combinaciones'][$va['reference']], $ddato);
        } else {
            $ddatos = Db::getInstance()->ExecuteS("SELECT
                                                        GROUP_CONCAT(aal.name) AS name
                                                    FROM
                                                        aalv_product_attribute apa
                                                        LEFT JOIN aalv_product_attribute_combination apac2 ON apa.id_product_attribute = apac2.id_product_attribute
                                                        LEFT JOIN aalv_attribute_lang aal ON aal.id_attribute = apac2.id_attribute
                                                    WHERE
                                                        apa.reference = '".$va['reference']."'
                                                    GROUP BY aal.id_lang ");

            $array[$contador]['combinaciones'][$va['reference']] = [
                'stock' => $stock,
                'atributos' => $ddatos,
                saberCountry($va['id_country']) => [
                    'precio' => $precio,
                    'descuento' => $descuento,
                    'total' => $total,
                ],
            ];
        }
    }
    $contador++;
}

// foreach ($array as $value) {
//     foreach ($value['combinaciones'] as $key => $val) {
//         if($val['stock']){
//             if($val['error']){
//                 echo "referencia => ".$key."<br>";
//                 echo "URL => ".$value['url'][0]."<br><hr>";

//             }
//         }
//     }
// }

// if(count($array) == 0){
//     echo "listo => ".$limit;
//     die();
// }

// echo json_encode($array);

/*************************************************** CODIGO ***************************************************/

function urlImagen($idImage)
{
    $imagePath = $rutaImagenes;

    // Separar cada dígito del id_image en una carpeta
    $idImageStr = strval($idImage);
    for ($i = 0; $i < strlen($idImageStr); $i++) {
        $imagePath .= $idImageStr[$i].'/';
    }

    $imagePath .= $idImage.'.jpg';

    return $imagePath;
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

function saberCountry($id_country)
{
    switch ($id_country) {
        case '0':
            return 'es';
            break;
        case '15':
            return 'pt';
            break;

        default:
            return 'error';
            break;
    }
}

function saberprecio($id_country, $price)
{
    switch ($id_country) {
        case '0':
            return number_format(round($price * 1.21, 2), 2, '.', '');
            break;
        case '15':
            return number_format(round($price * 1.23, 2), 2, '.', '');
            break;

        default:
            return 'error';
            break;
    }
}
