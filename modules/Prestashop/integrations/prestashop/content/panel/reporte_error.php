<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// Array
$arreglo = [];

$productos_a_excliur = '56764,55050,57934,57946,57950,57952,57954,61373,61569,52930,52938,61789,61863,62667,63543,63957,62752,64923,
64924,64979';

/********************************* ARTICULOS QUE EXISTAN EN SHOP PERO NO EN ATTRIBUTE **********************************/
$arreglo[] = [
    'titulo' => 'ARTICULOS QUE EXISTAN EN SHOP PERO NO EN ATTRIBUTE',
    'sql' => '  SELECT
                    att.id_product,
                    att.id_product_attribute,
                    shop.id_product_attribute
                FROM
                    '._DB_PREFIX_.'product_attribute_shop att
                    LEFT JOIN '._DB_PREFIX_.'product_attribute shop ON shop.id_product_attribute  = att.id_product_attribute
                WHERE
                    shop.id_product_attribute IS NULL
                    GROUP BY att.id_product
                    ORDER BY att.id_product DESC',
];
/********************************* FIN **********************************/

/********************************* ARTICULOS QUE NO TENGAN STOCK **********************************/
$arreglo[] = [
    'titulo' => 'ARTICULOS QUE NO TENGAN STOCK',
    'sql' => '  SELECT
                    ava.id_product,
                    ava.id_product_attribute
                FROM
                    '._DB_PREFIX_.'stock_available  ava
                    LEFT JOIN '._DB_PREFIX_.'product_attribute att ON att.id_product_attribute = ava.id_product_attribute
                WHERE
                    ava.id_product_attribute != 0
                    AND att.id_product_attribute IS null
                GROUP BY ava.id_product
                ORDER BY ava.id_product DESC',
];
/********************************* FIN **********************************/

/********************************* CANTIDAD DE PRECIOS QUE TIENE UN ARTICULO **********************************/
// $arreglo[] = [
//     'titulo' => 'CANTIDAD DE PRECIOS QUE TIENE UN ARTICULO',
//     'sql' => '  SELECT
//                     id_product_attribute,
//                     COUNT(*) AS cantidad_repeticiones,
//                     id_product
//                 FROM
//                     ' . _DB_PREFIX_ . 'specific_price
//                 GROUP BY id_product_attribute
//                 HAVING COUNT(*) > 2
//                 ORDER BY id_product DESC'
// ];
/********************************* FIN **********************************/

/********************************* PRODUCTOS QUE NO TENGAN MODELO **********************************/
$arreglo[] = [
    'titulo' => 'PRODUCTOS QUE NO TENGAN MODELO',
    'sql' => '  SELECT
                    id_product
                FROM
                    '._DB_PREFIX_.'product ap
                    LEFT JOIN '._DB_PREFIX_.'product_import api ON api.id_product = ap.id_product
                WHERE
                    ap.id_product NOT IN ('.$productos_a_excliur.')
                    AND api.id_product IS NULL',
];
/********************************* FIN **********************************/

/********************************* MODELOS QUE NO TENGAN PRODUCTOS **********************************/
$arreglo[] = [
    'titulo' => 'MODELOS QUE NO TENGAN PRODUCTOS',
    'sql' => '  SELECT
                    id_product
                FROM
                    '._DB_PREFIX_.'product_import ap
                    LEFT JOIN '._DB_PREFIX_.'product api ON api.id_product = ap.id_product
                WHERE
                    ap.id_product NOT IN ('.$productos_a_excliur.')
                    AND api.id_product IS NULL',
];
/********************************* FIN **********************************/

/********************************* PRODUCTOS QUE NO TENGAN REFERENCIAS **********************************/
$arreglo[] = [
    'titulo' => 'PRODUCTOS QUE NO TENGAN REFERENCIAS',
    'sql' => '  SELECT
                    api.id_modelo,
                    ap.id_product,
                    ap.reference,
                    ap.product_type
                FROM
                    '._DB_PREFIX_.'product ap
                    left JOIN '._DB_PREFIX_.'product_attribute apa ON apa.id_product = ap.id_product
                    left JOIN '._DB_PREFIX_.'product_import api ON api.id_product = ap.id_product
                WHERE
                    apa.id_product IS NULL
                    AND ap.id_product NOT IN ('.$productos_a_excliur.')
                    AND ap.reference = ""',
];
/********************************* FIN **********************************/

/********************************* PRODUCTOS QUE TIENEN DIFERENCIA EN ACTIVO CON SHOP **********************************/
$arreglo[] = [
    'titulo' => 'PRODUCTOS QUE TIENEN DIFERENCIA EN ACTIVO CON SHOP',
    'sql' => '  SELECT
                    ap.id_product,
                    ap.active,
                    aps.active
                FROM
                    '._DB_PREFIX_.'product ap
                    INNER JOIN '._DB_PREFIX_.'product_shop aps ON ap.id_product = aps.id_product
                WHERE
                    ap.active != aps.active
                ORDER BY id_product DESC',
];
/********************************* FIN **********************************/

/********************************* ARTICULOS QUE EXISTEN EN COMBINACIONES PERO NO EN ATTRIBUTE **********************************/
$arreglo[] = [
    'titulo' => 'ARTICULOS QUE EXISTEN EN COMBINACIONES PERO NO EN ATTRIBUTE',
    'sql' => '  SELECT
                    com.id_product_attribute
                FROM
                    '._DB_PREFIX_.'combinaciones_import com
                    LEFT JOIN '._DB_PREFIX_.'product_attribute att ON att.id_product_attribute = com.id_product_attribute
                WHERE
                    att.id_product_attribute IN NULL',
];
/********************************* FIN **********************************/

/********************************* CACHE DEL ATRIBUTO Y ESTE ATRIBUTO NO EXISTE **********************************/
$arreglo[] = [
    'titulo' => 'CACHE DEL ATRIBUTO Y ESTE ATRIBUTO NO EXISTE',
    'sql' => '  SELECT
                    apa.cache_default_attribute,
                    apa.id_product
                FROM
                    '._DB_PREFIX_.'product apa
                    LEFT JOIN '._DB_PREFIX_.'product_attribute apa2 ON apa2.id_product = apa.id_product
                WHERE
                    apa.cache_default_attribute != 0
                    AND apa2.id_product_attribute IS NULL
                ORDER BY apa.id_product DESC',
];
/********************************* FIN **********************************/

/********************************* REFERENCIA REPETIDAS PRODUCTO SIMPLE **********************************/
$arreglo[] = [
    'titulo' => 'REFERENCIA REPETIDAS PRODUCTO SIMPLE',
    'sql' => '  SELECT
                    ap.id_product,
                    ap.reference,
                    COUNT(*) AS cantidad
                FROM
                    '._DB_PREFIX_.'product ap
                GROUP BY ap.reference
                HAVING COUNT(*) > 1',
];
/********************************* FIN **********************************/

/********************************* REFERENCIA REPETIDAS PRODUCTO COMBINACION **********************************/
$arreglo[] = [
    'titulo' => 'REFERENCIA REPETIDAS PRODUCTO COMBINACION',
    'sql' => '  SELECT
                    apa.id_product,
                    apa.reference,
                    COUNT(*) AS cantidad
                FROM
                    '._DB_PREFIX_.'product_attribute apa
                GROUP BY apa.reference
                HAVING COUNT(*) > 1',
];
/********************************* FIN **********************************/

/********************************* PRECIOS QUE NO EXISTEN EN IMPORT **********************************/
$arreglo[] = [
    'titulo' => 'PRECIOS QUE NO EXISTEN EN IMPORT',
    'sql' => '  SELECT
                    pri.id_product
                FROM
                    '._DB_PREFIX_.'specific_price pri
                    LEFT JOIN '._DB_PREFIX_.'specific_price_import imp ON imp.id_specific_price = pri.id_specific_price
                WHERE
                    imp.id_specific_price IS NULL
                    AND pri.id_product NOT IN ('.$productos_a_excliur.')',
];
/********************************* FIN **********************************/

/********************************* PRECIOS QUE NO EXISTEN EN SPECIFIC_PRICE **********************************/
$arreglo[] = [
    'titulo' => 'PRECIOS QUE NO EXISTEN EN SPECIFIC_PRICE',
    'sql' => '  SELECT
                    imp.id_product
                FROM
                    '._DB_PREFIX_.'specific_price_import pri
                    LEFT JOIN '._DB_PREFIX_.'specific_price imp ON imp.id_specific_price = pri.id_specific_price
                WHERE
                    imp.id_specific_price IS NULL
                    AND imp.id_product NOT IN ('.$productos_a_excliur.')',
];
/********************************* FIN **********************************/

/********************************* MODELOS REPETIDOS EN IMPORT **********************************/
$arreglo[] = [
    'titulo' => 'PRECIOS QUE NO EXISTEN EN SPECIFIC_PRICE',
    'sql' => '  SELECT
                    api.id_modelo,
                    COUNT(*) AS cantidad
                FROM
                    '._DB_PREFIX_.'product_import api
                GROUP BY api.id_modelo
                HAVING COUNT(*) > 1',
];
/********************************* FIN **********************************/

// foreach ($arreglo as $value) {
//     htmlResultado($value['titulo'],$value['sql']);
// }

function htmlResultado($titulo, $sql)
{
    $result = Db::getInstance()->executeS($sql);
    if (count($result) == 0) {
        return '';
    }
    $columnNames = array_keys($result[0]);
    echo '<h2>'.$titulo.'</h2>';
    echo '  <table border="1">';
    echo '      <tr>';
    foreach ($columnNames as $columnName) {
        echo "<th>$columnName</th>";
    }
    echo '      </tr>';
    foreach ($result as $row) {
        echo '<tr>';
        foreach ($row as $value) {
            echo '<td>'.$value.'</td>';
        }
        echo '</tr>';
    }
    echo '  </table><hr>';
}
