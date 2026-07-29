<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../../config/config.inc.php');
//include (dirname(__FILE__).'/../init.php');

// Stop
die();


//Etiqueta de la revaja
$etiqueta = 'CORTA'; // ESCOPETA // RIFLE // CORTA

// ID del Feature
$feature = 23;

// ID de Valor del evento
$valor = 263661;

$datos =  DB::getInstance()->executeS(" SELECT
                                            id_product
                                        FROM
                                        ( 	SELECT
                                                aci2.id_product
                                            FROM
                                                aalv_combinacionunica_import aci2
                                            WHERE
                                                aci2.etiqueta LIKE '%".$etiqueta."%'
                                                AND aci2.id_product != 0
                                            UNION
                                            SELECT
                                                apa.id_product
                                            FROM
                                                aalv_combinaciones_import aci
                                                LEFT JOIN aalv_product_attribute apa ON apa.id_product_attribute = aci.id_product_attribute
                                            WHERE
                                                aci.etiqueta LIKE '%".$etiqueta."%'
                                                AND apa.id_product IS NOT NULL
                                                AND apa.id_product != 0
                                        ) AS etiqueta
                                        GROUP BY id_product");

// Crear un array auxiliar para guardar los IDs únicos
$ids = [];

// Iterar sobre el array original y guardar los IDs únicos
foreach ($datos as $item) {

    if (!in_array($item["id_product"], $ids)) {
        $ids[] = $item["id_product"];

        // Comprobar si el producto ya tiene la característica
        if (!productHasFeature($item["id_product"], $feature, $valor)) {
            // dump($item["id_product"]);die();

            //Insertamos lo caracteristicas a los productos
            $product = new Product($item["id_product"]);

            $product->addFeaturesToDB((int)$feature, (int)$valor);
            $product->update();

            $row = ['id_feature' => (int) $feature, 'id_product' => (int) $item["id_product"], 'id_feature_value' => (int) $valor];
            Db::getInstance()->insert('feature_product', $row);
            // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$item["id_product"]);
            dump($item["id_product"]);
            // die();

        }
    }

}

// Buscamos todos los id_product que tienen el mismo feature
$buscar = DB::getInstance()->executeS("SELECT id_product FROM aalv_feature_product afp WHERE id_feature = ".$feature." AND id_feature_value = ".$valor." AND id_product != 0 GROUP BY id_product");

$idfeature = [];

// Iterar sobre el array original y guardar los IDs únicos
foreach ($buscar as $ite) {
    if (!in_array($ite["id_product"], $idfeature)) {
        $idfeature[] = $ite["id_product"];
    }
}

// Elementos en $array1 que no están en $array2
$notInArray2 = array_diff($ids, $idfeature);

// Elementos en $array2 que no están en $array1
$notInArray1 = array_diff($idfeature, $ids);


// Unir los resultados en un solo array
$notInBothArrays = array_merge($notInArray1, $notInArray2);

if(count($notInBothArrays) > 0){
    // dump($notInBothArrays);die();
    foreach ($notInBothArrays as $value) {
        removeFeatureFromProduct($value, $feature, $valor);
        // peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=".$value);
        $product = new Product($value);
        $product->save();
        dump($value);
        // die();
    }
}else{
    echo "listo";
}


// Función para comprobar si el producto ya tiene la característica
function productHasFeature($id_product, $feature_id, $feature_value_id) {
    $sql = 'SELECT * FROM `'._DB_PREFIX_.'feature_product`
            WHERE `id_product` = '.(int)$id_product.'
            AND `id_feature` = '.(int)$feature_id.'
            AND `id_feature_value` = '.(int)$feature_value_id;
    $result = Db::getInstance()->getRow($sql);
    return !empty($result);
}



// Función para eliminar una característica de un producto
function removeFeatureFromProduct($id_product, $feature_id, $feature_value_id) {
    // Eliminar la característica del producto
    $sql = 'DELETE FROM `'._DB_PREFIX_.'feature_product`
            WHERE `id_product` = '.(int)$id_product.'
            AND `id_feature` = '.(int)$feature_id.'
            AND `id_feature_value` = '.(int)$feature_value_id;
    Db::getInstance()->execute($sql);
    return Db::getInstance()->Affected_Rows() > 0;
}

function peticionget($url){

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "alsernet:May.8006763");
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}