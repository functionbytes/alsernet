<?php
include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

if($_GET['tipo'] == "borrar"){
    $db = Db::getInstance()->executeS("delete from aalv_feature_product where id_product = ".$_POST['id_product']." and id_feature_value = ".$_POST['id_feature_value']);
}else{
    $buscar_repetido = Db::getInstance()->executeS("SELECT * FROM aalv_feature_product where id_product = ".$_POST['id_product']." and id_feature_value = ".$_POST['id_feature_value']);

    if(count($buscar_repetido) == 0){
        $db = Db::getInstance()->executeS("insert into aalv_feature_product
                                    (id_feature,id_product,id_feature_value)
                                    value
                                    (5,".$_POST['id_product'].",".$_POST['id_feature_value'].");");
    }
        //var_dump($_POST);

}


echo json_encode("Listo");