<?php

use Symfony\Component\Validator\Constraints\IsTrue;

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_ . '/../../config/config.inc.php';

$sql = Db::getInstance()->executeS("SELECT id_cart_rule, code, reduction_amount FROM aalv_cart_rule WHERE quantity != 0 ORDER BY id_cart_rule DESC");

foreach ($sql as $value) {
    $explode = explode("-",$value['code']);
    if(count($explode) > 1){
        dump($value['code']);
        dump($value['id_cart_rule']);

        if(count($explode) > 3){
            dump($explode);
            dump("ERRORRR");die();
        }

        $datos = peticionget("http://127.0.0.1:58002/api-gestion/bono/".$explode[0]."/?codigo_verificacion=".$explode[1]."&importe_venta=".$value['reduction_amount']);
        $xml = simplexml_load_string($datos, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);
        $array = json_decode($json, TRUE);

        if(count($array) == 1){
            continue;
        }

        $cartRule = new CartRule($value['id_cart_rule']);
        $cartRule->date_from = $array['fvalidez_desde'].' 00:00:00';
        $cartRule->date_to = $array['fvalidez_hasta'].' 23:59:59';
        $cartRule->name[1] = $array['descripcion_tipo'];
        $cartRule->name[2] = $array['descripcion_tipo'];
        $cartRule->name[3] = $array['descripcion_tipo'];
        $cartRule->name[4] = $array['descripcion_tipo'];
        $cartRule->name[5] = $array['descripcion_tipo'];
        $activo = true;
        if($array['estado_extendido'] != 1){
            $activo = false;
            $cartRule->quantity = 0;
        }elseif(date("Y-m-d") > $array['fvalidez_hasta']){
            dump("seeeee");die();
        }
        $cartRule->active = $activo;
        $cartRule->update();
        dump("----------------------------------");
    }
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