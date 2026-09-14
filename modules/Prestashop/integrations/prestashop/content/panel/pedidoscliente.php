<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';

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

// $url =  URL_ERP."pedido-cliente/?identificadororigen=652069";
$url = URL_ERP.'pedido-cliente/?serie=2024&npedidocli=7701';

// $content = peticionget($url);
// dump($content);

/*
$idcliente=AlvarezERP::recuperaridclienteerp(Tools::getValue("id"));
dump($idcliente);

        if ($idcliente != '') {
            $url =  URL_ERP.'pedido-cliente/?idcliente='.$idcliente;
            $content = peticionget($url);

            dump($content);

        }

*/

// $pedidos = AlvarezERP::recuperarpedidoscliente(Tools::getValue("id"));
// dump($pedidos);
