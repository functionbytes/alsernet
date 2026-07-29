<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../config/config.inc.php');


setlocale(LC_CTYPE, "es.UTF16");
$dbcon = connectBD();

$count = 0;

if ($_GET['id_product']) {
    $WHERE = " AND p.id_product=".$_GET['id_product'];
}else{
    $WHERE = " AND p.active=1";
}

$query = "SELECT pi.id_product, pi.id_modelo, pl.name
            FROM aalv_product_import AS pi
            LEFT JOIN aalv_product AS p ON pi.id_product=p.id_product
            LEFT JOIN aalv_product_lang AS pl ON pl.id_product=p.id_product
            WHERE pl.id_lang=1".$WHERE."
            ORDER BY pi.id_product DESC";

$productos = Db::getInstance()->ExecuteS($query);
echo '<table style="border: 1px solid black;">
        <tr>
            <th>id_product</th>
            <th>id_modelo</th>
            <th>Producto</th>
            <th>Categorías gestión</th>
            <th>Categorías PrestaShop</th>
            <th>Equivalencias</th>
            <th>Cambios</th>
        </tr>';

foreach ($productos as $ps) {
    $categoria_gestion = [];
    $categoria_presta = [];
    $cambios =[];
    $id_cats = [];

    $sql = "SELECT p.id_modelo, v.nombre,v.id AS id_valores
            FROM perfiles_nav p
            LEFT JOIN valores_nav v ON p.id_valor = v.id
            WHERE p.id_modelo = ".$ps['id_modelo']." order by v.id ASC";

    $datos = mysqli_query($dbcon, $sql);
    while ($categorias_gestion = mysqli_fetch_assoc($datos)) {
        $categoria_gestion[$categorias_gestion['id_valores']] = $categorias_gestion['nombre'];
    }

    $query = "SELECT
                    p.id_product, l.id_category, l.name
                FROM
                    aalv_category_product p
                LEFT JOIN aalv_category c ON
                    p.id_category = c.id_category
                LEFT JOIN aalv_category_lang l ON
                    c.id_category = l.id_category
                where
                    id_product = ".$ps['id_product']."
                    AND l.id_lang = 1";

    $categorias = Db::getInstance()->ExecuteS($query);
    foreach ($categorias as $categoria) {
        $categoria_presta[$categoria['id_category']] = $categoria['name'];
    }

    $html = '<tr>
            <td style="border: 1px solid black;">'.$ps['id_product'].'</td>
            <td style="border: 1px solid black;">'.$ps['id_modelo'].'</td>
            <td style="border: 1px solid black;">'.$ps['name'].'</td>
            <td style="border: 1px solid black;">';
    foreach ($categoria_gestion as $id => $cat) {
        $datos = mysqli_query($dbcon, 'select id from navegacion where elemento = '.$id);
        $elementos = [];
        while ($elemento = mysqli_fetch_assoc($datos)) {
            $elementos[] = $elemento['id'];
        }
        $cats = Db::getInstance()->ExecuteS("SELECT id_cat, id_padre FROM aalv_category_import WHERE id_nav IN (".implode(',',$elementos).")");
        foreach($cats as $id_cat) {
            $id_cats[$id][] = $id_cat['id_cat'];
        }
        $html .= '<li>'.$cat.' => '.$id.'</li>';
    }
    $html .= '</td>
    <td style="border: 1px solid black;">';
    foreach ($categoria_presta as $id => $cat) {
        $html .= '<li>'.$cat.' => '.$id.' ('.ruta($id).')</li>';
    }
    $html .= '</td>';
    $html .= '<td style="border: 1px solid black;"><ul>';

    foreach ($categoria_gestion as $id => $cat) {
        $datos = mysqli_query($dbcon, 'select id from navegacion where elemento = '.$id);
        $elementos = [];
        while ($elemento = mysqli_fetch_assoc($datos)) {
            $elementos[] = $elemento['id'];
        }
        $cats = Db::getInstance()->ExecuteS("SELECT id_cat, id_padre FROM aalv_category_import WHERE id_nav IN (".implode(',',$elementos).")");
        foreach($cats as $id_cat) {
            $categorias_elemento[] = $id_cat['id_cat'];
            $id_padre_presta = Db::getInstance()->ExecuteS("SELECT id_parent FROM aalv_category WHERE id_category =".$id_cat['id_cat'])[0]['id_parent'];
            if (!array_key_exists($id_padre_presta, $categoria_presta) &&
                $id_padre_presta>2 &&
                array_key_exists($id_cat['id_cat'], $categoria_presta) &&
                !buscaCategoria($id_padre_presta, $id_cats)
               )
            {
                $cambios[] = "[Categoría sin padre] ".$id_cat['id_cat']." (Padre Presta=> ".$id_padre_presta.")";
                if ($_GET['accion']=="borrar") {
                    /*
                    Db::getInstance()->Execute("DELETE FROM aalv_category_product_import WHERE id_category=".$id_cat['id_cat']." AND id_product=".$ps['id_product']);
                    Db::getInstance()->Execute("DELETE FROM aalv_category_product WHERE id_category=".$id_cat['id_cat']." AND id_product=".$ps['id_product']);
                    dump('Eliminada la categoría: '.$id_cat['id_cat']);
                    $producto = new Product($ps['id_product']);
                    $producto->update();
                    peticionget("https://preproduccion.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $ps['id_product']);
                    */
                }
            }elseif(!array_key_exists($id_padre_presta, $categoria_presta) &&
                $id_padre_presta>2 &&
                buscaCategoria($id_padre_presta, $id_cats))
            {
                $cambios[] = "[Falta categoría] ".$id_padre_presta;
            }

            if (!array_key_exists($id_cat['id_padre'], $categoria_gestion) && $id_cat['id_padre']>0) {
                    $cambios[] = "[Falta padre en gestión] ".$id_cat['id_cat']." (Padre Gestión=> ".$id_cat['id_padre'].")";
                if ($_GET['accion']=="borrar") {
                    /*
                    Db::getInstance()->Execute("DELETE FROM aalv_category_product_import WHERE id_category=".$id_cat['id_cat']." AND id_product=".$ps['id_product']);
                    Db::getInstance()->Execute("DELETE FROM aalv_category_product WHERE id_category=".$id_cat['id_cat']." AND id_product=".$ps['id_product']);
                    dump('Eliminada la categoría: '.$id_cat['id_cat']);
                    peticionget("https://preproduccion.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $ps['id_product']);
                    */
                }
            }
        }
        if (!$id_cats[$id]) {
            $cambios[] = "[No existe en Prestashop] ".$cat;
        }
        $html .= '<li>'.$id.'=>'.implode(', ',$id_cats[$id]).'</li>';

    }
    $html .= '</ul></td>';
    $html .= '<td style="border: 1px solid black;">'.implode('<br>', $cambios).'</td>';
    //$html .= '<td style="border: 1px solid black;">'.implode('<br>', $falta).'</td>';
    $html .= '</tr>';

    if ($count==100) {
        $html .= '</table>';
        die;
    }
    if (!$cambios && !$_GET['debug']) continue;
    echo $html;
    $count++;
}

echo '</table>';

function connectBD() {

    return $dbcon;
}

function closeBD($dbcon) {
    mysqli_close($dbcon);
}

function ruta($id_cat) {
    $ruta = $id_cat;
    $id_padre = Db::getInstance()->ExecuteS("SELECT id_parent FROM aalv_category WHERE id_category =".$id_cat);
    if(count($id_padre)>1) dump("Más de un padre: ".$id_cat);
    if ($id_padre[0]) {
        $ruta .= " | ".ruta($id_padre[0]['id_parent']);
    }

    return $ruta;
}

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}

function buscaCategoria($id, $array) {
    $return = false;
    foreach($array as $value) {
        if (is_array($value)) {
            $return = buscaCategoria($id, $value);
            if($return) return true;
        }else{
            if ($value==$id) {
                return true;
            }
        }
    }
    return $return;

}