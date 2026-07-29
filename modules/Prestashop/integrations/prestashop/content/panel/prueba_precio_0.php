<?php

ini_set('max_execution_time', 36000);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

$datos = [
    // 54626,
    // 56864,
    // 54298,
    // 390,
    // 45934,
    // 9766,
    // 38732,
    // 41950,
    // 64288,
    // 54298,
    // 72,
    // 74,
    // 42799,
    // 49758,
    // 49752,
    // 1798,
    // 39028,
    // 64665,
    // 40993,
    // 65665,
    // 36470,
    // 61215,
    64566,
    18699,
    23892,
    52036,
];

foreach ($datos as $value) {
    // code...
    // dump($value);die();
    $product = new Product($value);
    $product->visibility = 'none';
    $product->update();
    dump($value);
}
dump('listo');
