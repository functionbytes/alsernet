<?php
ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include (dirname(__FILE__).'/../config/config.inc.php');

// $productos = [19628,
// 63471,
// 13099,
// 58928,
// 64259,
// 17397,
// 6947,
// 42895,
// 64256,
// 61529,
// 5544,
// 60909,
// 64543,
// 13100,
// 37729,
// 46649,
// 60464,
// 52055
// ];




// foreach ($productos as $id_producto) {
//     echo "Vaciamos la cahe del id_product: ".$id_producto."\n";
//     peticionget("https://www.a-alvarez.com/?fc=module&module=pagecache&controller=clearcache&token=ApbUf8KuFaGPBhAk&product=" . $id_producto);
// }

function peticionget($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}
