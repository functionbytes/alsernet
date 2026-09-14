<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

/* DESACTIVAR TODOS LOS LOTES EN PRESTASHOP */
/*$productos_lotes = Db::getInstance()->ExecuteS("SELECT ap.id_product FROM aalv_product ap INNER JOIN aalv_alsernet_lotes_copia aalc ON ap.id_product = aalc.id_ps_product");
$total_productos = 0;
foreach ($productos_lotes as $producto) {
    $id_product = $producto['id_product'];
    dump("PRODUCTO A DESACTIVAR ------ $id_product");
    $product = new Product($id_product);
    $product->active = false;
    $product->visibility = 'none';
    $product->update();
    $total_productos ++;
}
dump("TOTAL DE PRODUCTOS DESACTIVADOS ------ $total_productos");*/

// $order = new Order(739173);

// dump($order);

// $tablas = Db::getInstance()->ExecuteS("SHOW TABLES;");

// $buscar_columna = "id_product";
// $id_product = 63760;

// $where = true;
// $sql = ' LIMIT 1';
// foreach ($tablas as $key => $value) {
//     $columna = Db::getInstance()->ExecuteS("SHOW COLUMNS FROM ".$value['Tables_in_alvarez_db']." LIKE '".$buscar_columna."'");
//     if(count($columna) != 0){
//         $stmt = Db::getInstance()->query("DESCRIBE ".$value['Tables_in_alvarez_db']);
//         $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

//         echo "<h2>Tabla: ".$value['Tables_in_alvarez_db']."</h2>";
//         echo "<table border='1'><tr>";
//         foreach ($columns as $column) {
//             echo "<th>$column</th>";
//         }
//         echo "</tr>";

//         if($where){
//             $sql = " WHERE ".$buscar_columna." = ".$id_product;
//         }

//         $stmt = Db::getInstance()->query("SELECT * FROM ".$value['Tables_in_alvarez_db'].$sql);
//         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

//         foreach ($data as $row) {
//             echo "<tr>";
//             foreach ($row as $value) {
//                 echo "<td>$value</td>";
//             }
//             echo "</tr>";
//         }
//         echo "</table><hr>";

//     }
// }
// die();

// $columna = Db::getInstance()->ExecuteS("select id_category from aalv_category ac where id_category not in (1,2) and active = 1");
// foreach ($columna as $key => $value) {
//     # code...
//     $langs = [
//         ['id_lang' => 1, 'iso_code' => 'es'],
//         ['id_lang' => 2, 'iso_code' => 'en'],
//         ['id_lang' => 3, 'iso_code' => 'fr'],
//         ['id_lang' => 4, 'iso_code' => 'pt'],
//         ['id_lang' => 5, 'iso_code' => 'de'],
//     ];
//     foreach ($langs as $val) {
//         # code...
//         $category = new Category($value['id_category'], $val['id_lang']);
//         $link = new Link();
//         $url = $link->getCategoryLink($value['id_category'], NULL, $val['id_lang']);
//         if($url != null){
//             dump($url);die();
//             peticionget($url);
//             echo $url.'\n';
//         }
//     }
// }

// $columna = Db::getInstance()->ExecuteS("select id_product from aalv_product ap where active = 1 order by id_product desc");
// foreach ($columna as $value) {
//     peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$value['id_product']);
//     echo "ID-product => ".$value['id_product']."\n";
//     $idiomas = Db::getInstance()->ExecuteS("select link_rewrite,id_lang from aalv_product_lang apl where id_product = ".$value['id_product']);
//     foreach ($idiomas as $val) {
//         # code..
//         peticionget('https://www.a-alvarez.com'.lang_id($val['id_lang']).$value['id_product'].'-'.$val['link_rewrite']);
//         echo 'Iidoma => https://www.a-alvarez.com'.lang_id($val['id_lang']).$value['id_product'].'-'.$val['link_rewrite']."\n";
//     }
//     echo "----------------------------------------\n";
// }

// https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=64409

// function lang_id($id) {
//     switch ($id) {
//         case '2':
//             return '/en/';
//             break;
//         case '3':
//             return '/fr/';
//             break;
//         case '4':
//             return '/pt/';
//             break;
//         case '5':
//             return '/de/';
//             break;
//         default:
//             return '/';
//             break;
//     }
// }

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

// Ejecuta la consulta SQL
// $query = "  SELECT
//                 c1.id_category AS child_id,
//                 c1.id_parent AS parent_id,
//                 acl.name
//             FROM
//                 aalv_category c1
//                 LEFT JOIN aalv_category_product cp ON c1.id_category = cp.id_category
//                 INNER JOIN aalv_category_lang acl on c1.id_category = acl.id_category
//             WHERE
//                 c1.active = 0
//                 AND cp.id_product IS NULL
//                 AND acl.id_lang = 1
//             ORDER BY c1.id_category DESC";

// $result = Db::getInstance()->ExecuteS($query);

// if (count($result) > 0) {

//     foreach ($result as $value) {
//         $childId = $value['child_id'];
//         $parentIds = array();

//         // Llama a la función recursiva para obtener los padres
//         obtenerCategoriasPadres($childId, $parentIds);
//         $parentIds = array_reverse($parentIds);
//         echo implode(", ", $parentIds) . "<br>";
//     }
// } else {
//     echo "No se encontraron categorías hijas desactivadas sin productos.";
// }

// function obtenerCategoriasPadres($categoryId, &$categoriaPadreNombres) {
//     $query = "  SELECT
//                     cat.id_parent,
//                     acl.name
//                 FROM
//                     aalv_category cat
//                     INNER JOIN aalv_category_lang acl on cat.id_category = acl.id_category
//                 WHERE
//                     acl.id_lang = 1 AND
//                     cat.id_category = $categoryId";
//     $result = Db::getInstance()->ExecuteS($query);

//     if (count($result) > 0) {
//         $parentId = '['.$result[0]['name'].'] ';
//         $parentId = $result[0]['id_parent'];
//         $categoriaPadreNombres[] = '['.$result[0]['name'].'] ';

//         if ($parentId > 0) {
//             obtenerCategoriasPadres($parentId, $categoriaPadreNombres);
//         }
//     }
// }
