<?php

include dirname(__FILE__).'/../config/config.inc.php';
include dirname(__FILE__).'/../init.php';

// dump(AlvarezERP::construirdatospedido(Tools::getValue("id"),""));

const URL_ERP = 'http://127.0.0.1:58002/api-gestion/';

function peticionget($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);

    $content = curl_exec($ch);
    curl_close($ch);

    return $content;

}

$url = URL_ERP.'pedido-cliente/?identificadororigen='.Tools::getValue('id');
$content = peticionget($url);

dump($content);

echo 'sale';
