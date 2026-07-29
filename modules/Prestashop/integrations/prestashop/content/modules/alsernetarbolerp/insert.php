<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

$id_feature_value = $_POST['id_feature_value'];

EliminarFeature_product($id_feature_value);

$cate = insertar_datos($_POST['categoria'],$id_feature_value,'Categoria');
$fami = insertar_datos($_POST['familia'],$id_feature_value,'Familia');
$subF = insertar_datos($_POST['subfamilias'],$id_feature_value,'SubFamilia');
$grup = insertar_datos($_POST['grupos'],$id_feature_value,'Grupo');

$todos = array_merge($cate, $fami, $subF, $grup);

$todos = Repetidos($todos,"id_product");
$array = array();

for ($i=0; $i <count($todos); $i++) {

    $db = Db::getInstance()->executeS(" select
                                            prod.id_feature_value,
                                            lang.name,
                                            val.value
                                        from
                                            aalv_feature_product prod
                                            inner join aalv_product_lang lang on lang.id_product = prod.id_product
                                            inner join aalv_feature_value_lang val on val.id_feature_value = prod.id_feature_value
                                        where
                                            prod.id_product = ".$todos[$i]['id_product']."
                                            and lang.id_lang = 1
                                            and val.id_lang = 1
                                        order by id_feature_value asc
                                        LIMIT 1");
    // $x = 0;
    // for ($q=0; $q <count($db); $q++) {
        // if($db[$q]['id_feature_value'] == $id_feature_value){
            $array[] = [
                "nombre" => $db[0]['name'],
                // "Tipo de grupo" => $db[0]['value'],
                "id_feature_value" => $id_feature_value,
                "id_product" => $todos[$i]['id_product']
            ];
        //     $x = 1;
        // }
    // }

    // if($x == 0){
    //     $array['error'][] = [
    //         "nombre" => $db[0]['name'],
    //         "Tipo de grupo" => $db[0]['value'],
    //         "id_feature_value" => $id_feature_value,
    //         "id_product" => $todos[$i]['id_product']
    //     ];
    // }

}

echo json_encode($array);

function insertar_datos($data,$id_feature_value,$procedencia)
{
    $delete = Db::getInstance()->executeS("delete from
                                                aalv_alsernet_exclude_product_type
                                            where
                                                id_feature_value = ".$id_feature_value."
                                                and procedencia = '".$procedencia."'");
    $datos      = '';
    $id_product = array();
    $nn         = 0;

    for ($i=0; $i <count($data); $i++) {

        $datos .= $data[$i].',';

        $db = Db::getInstance()->executeS(" select
                                                prod.id_product
                                            from
                                                aalv_feature_value_lang lang
                                                inner join aalv_feature_product prod on prod.id_feature_value = lang.id_feature_value
                                            where
                                                lang.value = ".$data[$i]."
                                                and lang.id_lang = 1");

        for ($q=0; $q <count($db); $q++) {
            $id_product[$nn] = $db[$q];
            $nn++;
        }


    }


    // Obtener el primer parámetro
    $primerParametro = substr($datos, 0, 1);

    if($primerParametro == ','){
        $datos      = substr($datos, 1);
    }

    // Obtener la longitud del string
    $longitud = strlen($datos);

    // Obtener el último parámetro
    $ultimoParametro = substr($datos, $longitud - 1);

    if($ultimoParametro == ','){
        $datos      = substr($datos, 0, -1);
    }

    if($datos != ''){
        $db = Db::getInstance()->executeS("insert into aalv_alsernet_exclude_product_type
        (id_feature_value,procedencia,c_f_sf_g_asignado)
        value
        (".$id_feature_value.",'".$procedencia."','".$datos."');");
    }
    return Repetidos($id_product,"id_product");
}

function Repetidos($datos,$columna)
{
    // Obtener la columna
    $idProducts = array_column($datos, $columna);

    // Obtener valores únicos
    $idProductsUnicos = array_values(array_unique($idProducts));

    // Construir el nuevo array sin duplicados
    $arraySinDuplicados = array_map(function ($id) {
        return array("id_product" => $id);
    }, $idProductsUnicos);

    return $arraySinDuplicados;
}


function EliminarFeature_product($id_feature_value)
{
    Db::getInstance()->executeS("delete from aalv_feature_product where id_feature_value = ".$id_feature_value);
}