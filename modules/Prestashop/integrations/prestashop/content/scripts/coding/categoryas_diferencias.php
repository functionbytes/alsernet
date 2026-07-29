<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';
include ('/var/www/clients/client1/web1/home/alvarezadmin/dbantigua.php');

$datos = Db::getInstance()->executeS("select api.id_modelo, api.id_product, ap.reference from aalv_product_import api
left join aalv_product ap on ap.id_product = api.id_product
where ap.active = 1 order by id_product asc");

$dbcon = connectBD();

$id_categoria_inicial = 2821; // Por ejemplo, "Inicio"
$ids_hijos = getAllChildrenCategoryIds($id_categoria_inicial);

// dump($ids_hijos);die();

foreach ($datos as $value) {
    # code...

    $sql_antigua = "select GROUP_CONCAT(id_valor) as id_valor from perfiles_nav where id_modelo = ".$value['id_modelo'];

    $result_antigua = mysqli_query($dbcon, $sql_antigua);

    $re_antigua = mysqli_fetch_array($result_antigua);

    $id_category = Db::getInstance()->executeS("select GROUP_CONCAT(acp.id_category) as id_category from aalv_category_product acp where acp.id_category != 0 and id_product = ".$value['id_product']);

    $category_import = Db::getInstance()->executeS("select GROUP_CONCAT(aci.id_cat) as id_cat from aalv_category_import aci where aci.id_origen in (".$re_antigua['id_valor'].")");

    // Convertir strings en arrays
    $id_category[0]['id_category'] = explode(",", $id_category[0]['id_category']);
    $category_import[0]['id_cat'] = explode(",", $category_import[0]['id_cat']);

    // Obtener los valores comunes (repetidos)
    $repetidos = array_diff($id_category[0]['id_category'], $category_import[0]['id_cat']);

    $resultado = array_filter($repetidos, function($valuess) use ($ids_hijos) {
        return !in_array((int)$valuess, $ids_hijos);
    });

    // Quitamos cualquier valor igual a 2
    $resultado = array_filter($resultado, function($valueee) {
        return (int)$valueee !== 2;
    });

    if(!is_null($resultado) && count($resultado) > 0){
        // dump($resultado);
        dump($value['reference']);die();
        // dump('');
        // dump($re_antigua['id_valor']);
        // dump($id_category[0]['id_category']);
        // dump($category_import[0]['id_cat']);
        // dump($repetidos);
        // dump($resultado);die();
    }

}
dump('Listo');


function getAllChildrenCategoryIds($id_parent_category)
{
    $children_ids = [];

    // Obtener los hijos directos
    $children = Category::getChildren((int)$id_parent_category, 1, true);

    foreach ($children as $child) {
        $id_child = (int)$child['id_category'];
        $children_ids[] = $id_child;

        // Llamada recursiva para buscar hijos del hijo
        $children_ids = array_merge($children_ids, getAllChildrenCategoryIds($id_child));
    }

    return $children_ids;
}