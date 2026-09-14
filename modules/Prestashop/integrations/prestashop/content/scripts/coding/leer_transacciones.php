<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include dirname(__FILE__).'/../config/config.inc.php';

$trasaccion = '2.5.1323370';

$contentitem = peticionget('http://127.0.0.1:59000/integracion/TransaccionPendiente/?destino=2&transaccion='.$trasaccion.'&limit=5000');

$jsonDataitem = trim(stripslashes($contentitem), '"');
$objitem = json_decode($jsonDataitem, true);
// $nn = 0;
foreach ($objitem['results'] as $valoresitem) {
    if ($valoresitem['tabla'] == 'v_sinc_tarifa_linea') {
        // if($valoresitem["data"] != null){
        //     // if($nn == 0){
        //     //     $nn++;
        //     //     continue;
        //     // }
        //     $data = $valoresitem["data"];
        //     if ($data["baseimp_anterior"]) {

        //         if ($data["baseimp_anterior"] > $data["baseimp"]) {

        //             //if ((($data["baseimp_anterior"]-$data["baseimp"])>=2.479338) || ((($data["baseimp_anterior"]-$data["baseimp"])/100)>0.1)) {
        //             if ((($data["baseimp_anterior"] - $data["baseimp"]) >= 2.479338) ||  ((1 - ($data["baseimp"] / $data["baseimp_anterior"])) > 0.1)) {
        //                 $miprice = round($data["baseimp_anterior"], 6);
        //                 $mireduction = round((float)$data["baseimp_anterior"] - (float)$data["baseimp"], 6);
        //             } else {
        //                 $miprice = round($data["baseimp"], 6);
        //                 $mireduction = 0;
        //             }
        //         } else {
        //             $miprice = round($data["baseimp"], 6);
        //             $mireduction = 0;
        //         }
        //     } else {
        //         $miprice = round($data["baseimp"], 6);
        //         $mireduction = 0;
        //     }
        //     var_dump($miprice);
        //     var_dump($mireduction);
        //     var_dump($valoresitem["data"]);die();
        // }

        // echo "SELECT id FROM ps_tax_rules_group atrg WHERE active = 1 AND deleted = 0 AND name LIKE '%".getIdIva()."%'<br>";

    }
}

echo getIdIva();

function getIdIva()
{
    return Db::getInstance()->getValue("SELECT id_tax_rules_group FROM aalv_tax_rules_group atrg WHERE active = 1 AND deleted = 0 AND `name` LIKE '%21%'");
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
