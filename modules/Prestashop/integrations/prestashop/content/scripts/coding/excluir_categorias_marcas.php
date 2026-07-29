<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../config/config.inc.php';
include _PS_ADMIN_DIR_ . '/../init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos = str_replace('[','',$_POST['selectedCategories']);
    $datos = str_replace(']','',$datos);
    $datos = str_replace('"','',$datos);

    Db::getInstance()->execute("DELETE FROM aalv_manufacturer_category_exclude;");
    Db::getInstance()->execute("INSERT INTO aalv_manufacturer_category_exclude VALUES ('".$datos."');");
    header("Location: https://a-alvarez.com/scriptsalsernet/excluir_categorias_marcas.php");
}


function construirArbolCategorias($parentId = 0) {
    $query = "SELECT
                cate.id_category,
                acl.name,
                cate.level_depth,
                IF(cate.active = 0,'No Activo','Activa') AS active
            FROM
                aalv_category cate
                LEFT JOIN aalv_category_lang acl ON acl.id_category = cate.id_category
            WHERE
                acl.id_lang = 1 AND
                cate.id_parent = $parentId";
    $columna = Db::getInstance()->ExecuteS($query);

    $result = array(); // Arreglo para almacenar los resultados

    if (count($columna) != 0) {
        foreach ($columna as $key => $value) {
            if($value['id_category'] == 2821 || $value['id_category'] == 2820 || $value['id_category'] == 12){
                continue;
            }
            $categoryId = $value['id_category'];
            $categoryName = $value['name'];
            $active = $value['active'];

            // Contar la cantidad de productos en esta categoría (deberás ajustar esto según tu esquema de base de datos)
            $queryCount = "SELECT
                                COUNT(*) as productCount
                            FROM
                                aalv_category_product
                            WHERE
                                id_category = $categoryId";
            $resultCount = Db::getInstance()->ExecuteS($queryCount);
            $productCount = 0;

            if (count($columna) != 0) {
                $productCount = $resultCount[0]['productCount'];
            }

            // Agregar los datos de la categoría actual al resultado
            $categoria = array(
                'id' => $categoryId,
                'text' => '['.$categoryId.'] ['.$categoryName.'] ['.$active.'] ['.$productCount.']'
            );

            // Llamar recursivamente para las categorías hijas con un nivel aumentado
            $categoria['children'] = construirArbolCategorias($categoryId);

            if(count($categoria['children']) > 0){
                if(idSeleccionado($categoryId)){
                    $categoria['state'] = ['selected' => true,
                                            'opened' => true];
                    // $categoria['state']['selected'] = [$categoryId => true];
                }else{
                    $categoria['state'] = ['selected' => false,
                                            'opened' => true ];
                }
                // $categoria['state'] = ['opened' => true];
            }else{
                if(idSeleccionado($categoryId)){
                    $categoria['state'] = ['selected' => true,
                                            'opened' => false ];
                }else{
                    $categoria['state'] = ['selected' => false,
                                        'opened' => false ];
                }
            }


            $result[] = $categoria;
        }
    }

    return $result;
}

function idSeleccionado($categoryId){
    $sql = Db::getInstance()->ExecuteS("SELECT * FROM aalv_manufacturer_category_exclude");
    $datos = explode(",",$sql[0]['value']);

    if(in_array($categoryId, $datos)){
        return true;
    }
    return false;
}

// Llamada inicial a la función y convertir el resultado en JSON
$jsonTreeData = json_encode(construirArbolCategorias(1));

// echo $jsonTreeData; die();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Árbol de Categorías</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/themes/default/style.min.css" />
</head>
<body>
    <div id="jstree"></div>
    <button id="submit">Enviar Selección</button>
    <form id="categoriesForm" action="#" method="POST" style="display: none;">
        <input type="hidden" name="selectedCategories" id="selectedCategories">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jstree/3.3.12/jstree.min.js"></script>
    <script>
         $(document).ready(function() {
            var treeData = <?php echo $jsonTreeData; ?>;
            $('#jstree').jstree({
                'plugins' : ["checkbox"],
                'core' : {
                    'data' : treeData,
                    'themes': {
                        'name': 'default',
                        'responsive': true
                    }
                },
                 'checkbox': {
                     'three_state': false,
                     'cascade': 'undetermined'
                 }
                //'checkbox': {
                //    'three_state': true,
                //    'cascade': 'down'
                //}
            });

            $('#submit').click(function() {
                var selectedCategories = $('#jstree').jstree('get_checked', true);
                var selectedIds = selectedCategories.map(function(node) {
                    return node.id;
                });

                $('#selectedCategories').val(JSON.stringify(selectedIds));
                $('#categoriesForm').submit();
            });
        });
    </script>
</body>
</html>
