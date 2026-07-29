<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
// include _PS_ADMIN_DIR_.'/../init.php';

function recuperapassword($data)
{

    $data2 = 'pw='.$data;
    $url = 'https://alvarez2.addisnetwork.es/panel/pruebaspass.php';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

echo 'llega: '.recuperapassword('c366690b88b4f134');
// echo recuperapassword("a716533f9b3b2447a13fb12999bf76c5");
