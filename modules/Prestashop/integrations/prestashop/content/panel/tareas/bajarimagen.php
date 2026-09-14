<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

function recuperarimagen($imagen)
{

    $URL = $imagen;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');

    $content = curl_exec($ch);
    curl_close($ch);

    echo $content;

}

header('Content-Type: image/jpeg');
// recuperarimagen("https://www.a-alvarez.com/productsimages/214-HGS-139_020_1024x1024-1-ConvertImage.jpg/450/fill/ffffff");

recuperarimagen('https://www.addis.es/wp-content/uploads/2018/12/mipounet.jpg');
