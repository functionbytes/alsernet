<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

// Array de Familias ya seleccionadas y no seleccionadas
$selectLista = [
    'listCategorySelected' => [],
    'listCategoryUnselected' => explode(',',unSelected($_POST['id_feature_value'],"Categoria")),

    'listFamilySelected' => [],
    'listFamilyUnselected' => explode(',',unSelected($_POST['id_feature_value'],"Familia")),

    'listSubFamilySelected' => [],
    'listSubFamilyUnselected' => explode(',',unSelected($_POST['id_feature_value'],"SubFamilia")),

    'listGroupsSelected' => [],
    'listGroupsUnselected' => explode(',',unSelected($_POST['id_feature_value'],"Grupo"))
];

/* Estos datos seran recorridos en base de datos para traer la informacion necesaria
*/
$datos = [
    [
        "columna" => "categoria",
        "selected" => "listCategorySelected",
        "exclude_product_type" => "Categoria"
    ],
    [
        "columna" => "familia",
        "selected" => "listFamilySelected",
        "exclude_product_type" => "Familia"
    ],
    [
        "columna" => "subfamilia",
        "selected" => "listSubFamilySelected",
        "exclude_product_type" => "SubFamilia"
    ],
    [
        "columna" => "grupo",
        "selected" => "listGroupsSelected",
        "exclude_product_type" => "Grupo"
    ]
];

/* Recorrer los datos para insertarlos en el front
*/
for ($i=0; $i <count($datos); $i++) {
    $sql = 'SELECT DISTINCT
                '.$datos[$i]['columna'].'
            FROM (
                    SELECT
                        '.$datos[$i]['columna'].'
                    FROM
                        aalv_combinaciones_import
                    GROUP BY '.$datos[$i]['columna'].'
                UNION ALL
                    SELECT
                        '.$datos[$i]['columna'].'
                    FROM
                        aalv_combinacionunica_import
                    GROUP BY '.$datos[$i]['columna'].'
                ) AS tablas_aalv
            WHERE
                '.$datos[$i]['columna'].' != 0';

    $selec = unSelected($_POST['id_feature_value'],$datos[$i]['exclude_product_type']);

    if($selec != ''){
        $sql .= ' and '.$datos[$i]['columna'].' not in ('.$selec.')';
    }

    $sql .= ' GROUP BY '.$datos[$i]['columna'].'
            ORDER BY '.$datos[$i]['columna'].' ASC';

    $db = Db::getInstance()->executeS($sql);

    foreach ($db as $lista_selected) {
        $selectLista[$datos[$i]['selected']][] = $lista_selected[$datos[$i]['columna']];
    }
}


function unSelected($var,$procedencia)
{
    $db = Db::getInstance()->executeS('select
                                            c_f_sf_g_asignado
                                        from
                                            aalv_alsernet_exclude_product_type
                                        where
                                            id_feature_value = '.$var.'
                                            and procedencia = "'.$procedencia.'"');
    return $db[0]['c_f_sf_g_asignado'];
}


echo json_encode($selectLista);
?>