<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../../config/config.inc.php';

$accion_ejecutar = 'todos';

if (is_array($argv)) {
    foreach ($argv as $parametro) {

        if (strpos($parametro, 'accion=') !== false) {
            $accion_ejecutar = str_replace('accion=', '', $parametro);

        }
    }
}
switch ($accion_ejecutar) {
    case 'todos':
        // desactivarProductos();
        agregarCategorias();
        agregarCategoriasDefault();
        break;
    case 'desactivar_productos':
        desactivarProductos();
        break;
    case 'agregar_categorias':
        agregarCategorias();
        agregarCategoriasDefault();
        break;
}
echo 'FIN'."\n";
exit();

function desactivarProductos()
{
    /* PRODUCTOS QUE ESTAN DESACTIVADOS EN GESTION Y ACTIVOS EN PRESTASHOP */

    $sql_inactive = 'SELECT DISTINCT ap.id_product FROM aalv_product ap WHERE ap.active = 1 AND ap.id_product IN (
            SELECT DISTINCT apa.id_product FROM aalv_combinaciones_import aci
            JOIN aalv_product_attribute apa ON aci.id_product_attribute = apa.id_product_attribute
            GROUP BY apa.id_product
            HAVING sum(aci.estado_gestion) = 0)';

    $sql_inactive_simple = 'SELECT DISTINCT ap.id_product FROM aalv_product ap WHERE ap.active = 1 AND ap.id_product IN (
            SELECT DISTINCT aci1.id_product FROM aalv_combinacionunica_import aci1
            GROUP BY aci1.id_product
            HAVING sum(aci1.estado_gestion) = 0)';

    $products = Db::getInstance()->executeS($sql_inactive);
    $products_simple = Db::getInstance()->executeS($sql_inactive_simple);
    $cont = 0;

    foreach ($products_simple as $product) {
        $product_obj = new Product($product['id_product']);

        if (Validate::isLoadedObject($product_obj)) {
            $product_obj->active = false;
            $product_obj->update();

            echo 'PRODUCTO INACTIVADO => '.$product['id_product']."\n";
            $cont++;
        }
    }

    foreach ($products as $product) {
        $product_obj = new Product($product['id_product']);

        if (Validate::isLoadedObject($product_obj)) {

            $product_obj->active = false;
            $product_obj->update();

            echo 'PRODUCTO INACTIVADO => '.$product['id_product']."\n";
            $cont++;
        }
    }
    if ($cont == 0) {
        echo 'NO SE ENCONTRARON PRODUCTOS A DESACTIVAR'."\n";
    }
}

function agregarCategorias()
{
    /* PRODUCTOS QUE NO TIENEN CATEGORIAS ASIGNADAS PERO SI UNA CATEGORIA Por defecto */

    $sql_no_categories = 'SELECT DISTINCT ap.id_product FROM aalv_product ap WHERE ap.active = 1 AND
                                    ap.id_category_default  > 0  AND
                                    ap.id_product NOT IN (SELECT id_product FROM aalv_category_product acp )';

    $products_no_categories = Db::getInstance()->executeS($sql_no_categories);
    $cont = 0;

    foreach ($products_no_categories as $product) {
        $product_obj = new Product($product['id_product']);
        if (Validate::isLoadedObject($product_obj)) {

            // Añade las nuevas categorías sin eliminar las anteriores
            $current_categories = $product_obj->getCategories(); // obtiene las actuales
            $category_default = $product_obj->getDefaultCategory();

            $all_categories = array_unique(array_merge($current_categories, [$category_default]));

            // Asigna todas las categorías al producto
            $product_obj->updateCategories($all_categories);
            $product_obj->save();

            peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&category='.$category_default);

            echo 'CATEGORIA ASIGNADA AL PRODUCTO => '.$product['id_product']."\n";
            $cont++;
        }

    }
    if ($cont == 0) {
        echo 'NO SE ENCONTRARON CATEGORIAS A AGREGAR'."\n";
    }

}

function agregarCategoriasDefault()
{
    /* PRODUCTOS QUE NO TIENEN CATEGORIAS DEFAULT ASIGNADAS PERO NO ESTA EN LA TABLA DE RELACION */

    $sql_no_categories = 'SELECT DISTINCT ap.id_product FROM aalv_product ap
                            LEFT JOIN aalv_category_product acp  ON ap.id_product = acp.id_product AND ap.id_category_default = acp.id_category
                            WHERE acp.id_product  IS NULL AND ap.id_category_default > 0 AND ap.active =1';

    $products_no_categories = Db::getInstance()->executeS($sql_no_categories);
    $cont = 0;

    foreach ($products_no_categories as $product) {
        $product_obj = new Product($product['id_product'], false, 1, 1);
        if (Validate::isLoadedObject($product_obj)) {

            // Añade las nuevas categorías sin eliminar las anteriores
            $current_categories = $product_obj->getCategories(); // obtiene las actuales
            $category_default = $product_obj->getDefaultCategory();

            $all_categories = array_unique(array_merge($current_categories, [$category_default]));

            // Asigna todas las categorías al producto
            $product_obj->updateCategories($all_categories);
            $product_obj->save();

            peticionget('https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&category='.$category_default);

            echo 'CATEGORIA DEFAULT AGREGADA A LA LISTA ASIGNADA AL PRODUCTO => '.$product['id_product']."\n";
            $cont++;
        }

    }
    if ($cont == 0) {
        echo 'NO SE ENCONTRARON CATEGORIAS DEFAULT A AGREGAR'."\n";
    }

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
