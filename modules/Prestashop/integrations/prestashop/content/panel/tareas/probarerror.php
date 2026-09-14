<?php

if (! defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', __DIR__);
}
include _PS_ADMIN_DIR_.'/../config/config.inc.php';
include _PS_ADMIN_DIR_.'/../init.php';

$content = 'ERROR 20451: No es posible insertar dos pedidos con el mismo identificador. (identificador_origen = 27687653481, idpedidocli = 1907675)';

$pospedcli = strpos($content, 'idpedidocli');
if ($pospedcli > 0) {
    $idpedidocli = substr($content, $pospedcli);

    $idpedidoclifinal = str_replace('=', '', str_replace(')', '', str_replace(' ', '', str_replace('idpedidocli', '', $idpedidocli))));

    echo $idpedidoclifinal;
}
